"""Initial secrets and DB setup behavior at a fake AWS boundary."""
import copy
import json
import io
import os
from pathlib import Path
import subprocess
import time
import tempfile
import unittest
import uuid
from unittest.mock import patch

import configuration
from cfnlint.decode import decode


class InitialAws:
    def __init__(self):
        self.values = {'app': None, 'migration': None, 'cache': {'password': 'cache-test-password'}}
        self.calls = []
        self.active = False
        self.fail_secret = None
        self.exit_code = 0

    def __call__(self, service, operation, *args):
        self.calls.append((service, operation, args))
        if operation == 'describe-services':
            return {'services': [dict(serviceName=name, status='ACTIVE', desiredCount=int(self.active),
                                     runningCount=int(self.active), pendingCount=0) for name in ('api', 'worker')]}
        if operation == 'describe-secret':
            return {'VersionIdsToStages': {'v': ['AWSCURRENT']} if self.values[args[1]] is not None else {}}
        if operation == 'get-secret-value':
            return {'SecretString': json.dumps(self.values[args[1]])}
        if operation == 'put-secret-value':
            if args[1] == self.fail_secret:
                raise configuration.AwsError(service, operation, 'TEST ONLY failure')
            self.values[args[1]] = json.loads(Path(args[3].removeprefix('file://')).read_text())
            self.file_mode = Path(args[3].removeprefix('file://')).stat().st_mode & 0o777
            return {}
        if operation == 'run-task':
            return {'tasks': [{'taskArn': 'setup-task'}]}
        if operation == 'describe-tasks':
            container = {'name': 'database-setup'}
            if self.exit_code is not None:
                container['exitCode'] = self.exit_code
            return {'tasks': [{'taskArn': 'setup-task', 'lastStatus': 'STOPPED', 'containers': [container]}]}
        raise AssertionError(operation)


class InitializationTests(unittest.TestCase):
    def setUp(self):
        self.aws = InitialAws()
        self.outputs = dict(AppSecretArn='app', MigrationSecretArn='migration', CacheSecretArn='cache',
                            DatabaseEndpoint='db.example.invalid', DatabaseName='kpool',
                            CacheEndpoint='cache.example.invalid', CachePort='6379',
                            ClusterArn='cluster', ApiServiceName='api', WorkerServiceName='worker',
                            PublicSubnetIds='subnet-a,subnet-b', MigrationSecurityGroupId='sg-migration',
                            DatabaseSetupTaskDefinitionArn='database-setup:1')

    def test_secrets_are_consistent_private_and_idempotent(self):
        configuration.initialize_secrets(self.aws, self.outputs)
        app, migration = self.aws.values['app'], self.aws.values['migration']
        self.assertEqual(app['APP_KEY'], migration['APP_KEY'])
        self.assertNotEqual(app['DB_PASSWORD'], migration['DB_PASSWORD'])
        self.assertEqual(app['DB_USERNAME'], 'kpool_app')
        self.assertEqual(migration['DB_USERNAME'], 'kpool_migration')
        self.assertTrue(app['REDIS_URL'].startswith('tls://application:'))
        self.assertTrue(app['REDIS_URL'].endswith('/0'))
        self.assertIn('sslmode=verify-full', app['DATABASE_URL'])
        self.assertEqual(self.aws.file_mode, 0o600)
        self.assertNotIn(app['DB_PASSWORD'], repr(self.aws.calls))
        previous = copy.deepcopy(self.aws.values)
        self.aws.calls.clear()
        configuration.initialize_secrets(self.aws, self.outputs)
        self.assertEqual(self.aws.values, previous)
        self.assertFalse(any(op == 'put-secret-value' for _, op, _ in self.aws.calls))

    def test_partial_write_resumes_without_changing_app_key_or_password(self):
        self.aws.fail_secret = 'migration'
        with self.assertRaises(configuration.AwsError):
            configuration.initialize_secrets(self.aws, self.outputs)
        app = copy.deepcopy(self.aws.values['app'])
        self.aws.fail_secret = None
        configuration.initialize_secrets(self.aws, self.outputs)
        self.assertEqual(self.aws.values['app'], app)
        self.assertEqual(self.aws.values['migration']['APP_KEY'], app['APP_KEY'])

    def test_active_services_and_inconsistent_existing_secrets_are_rejected_before_writes(self):
        self.aws.active = True
        with self.assertRaises(ValueError):
            configuration.initialize_secrets(self.aws, self.outputs)
        self.assertFalse(any(op == 'put-secret-value' for _, op, _ in self.aws.calls))
        self.aws.active = False
        configuration.initialize_secrets(self.aws, self.outputs)
        self.aws.values['migration']['APP_KEY'] = 'base64:wrong'
        self.aws.calls.clear()
        with self.assertRaises(ValueError):
            configuration.initialize_secrets(self.aws, self.outputs)
        self.assertFalse(any(op == 'put-secret-value' for _, op, _ in self.aws.calls))

    def test_admin_task_uses_secret_references_and_requires_explicit_exit_zero(self):
        configuration.initialize_secrets(self.aws, self.outputs)
        with patch('builtins.print'):
            configuration.database_setup(self.aws, self.outputs)
        launch = next(args for _, op, args in self.aws.calls if op == 'run-task')
        self.assertNotIn('--overrides', launch)
        network = json.loads(launch[launch.index('--network-configuration') + 1])
        self.assertEqual(network['awsvpcConfiguration']['securityGroups'], ['sg-migration'])
        for code in (1, None):
            self.aws.exit_code = code
            with patch('builtins.print'), self.assertRaises(ValueError):
                configuration.database_setup(self.aws, self.outputs)

    def test_existing_live_credentials_can_be_reused_without_stopping_services(self):
        configuration.initialize_secrets(self.aws, self.outputs)
        self.aws.active = True
        self.aws.calls.clear()
        configuration.initialize_secrets(self.aws, self.outputs)
        self.assertFalse(any(op == 'put-secret-value' for _, op, _ in self.aws.calls))

    def test_configuration_updates_preserve_operator_settings_without_source_versions(self):
        config = dict(accountId='123456789012', region='ap-northeast-1', stacks={},
                      inputs={'runtime': {'MailDomainName': 'example.invalid'}})
        outputs = dict(ApiUrl='https://api.example.invalid', DeploymentRoleArn='deployment-role')
        ca = b'-----BEGIN CERTIFICATE-----\nTEST ONLY\n-----END CERTIFICATE-----\n'
        with tempfile.TemporaryDirectory() as directory, patch('builtins.print'):
            path = Path(directory)
            with patch.object(configuration, 'urlopen', return_value=io.BytesIO(ca)):
                configuration.write_release_configuration(config, outputs, path)
            target = path / 'backend-release-configuration.json'
            old = json.loads(target.read_text())
            old.update(paused=True, app_secret_keys=['GOOGLE_CLIENT_SECRET'])
            old['runtime_environment']['LOG_LEVEL'] = 'warning'
            old['compatibility'] = {'approved': True, 'backend_sha': 'a' * 40}
            target.write_text(json.dumps(old))
            with patch.object(configuration, 'urlopen', return_value=io.BytesIO(ca)):
                configuration.write_release_configuration(config, outputs, path)
            updated = json.loads(target.read_text())
            self.assertTrue(updated['paused'])
            self.assertEqual(updated['runtime_environment']['LOG_LEVEL'], 'warning')
            self.assertIn('GOOGLE_CLIENT_SECRET', updated['app_secret_keys'])
            self.assertNotIn('compatibility', updated)
            self.assertNotIn('LIVE_FRONTEND_SHA', json.loads((path/'github-variables.json').read_text()))
            self.assertEqual(target.stat().st_mode & 0o777, 0o600)

    def test_missing_mail_domain_fails_before_any_aws_operation(self):
        with tempfile.TemporaryDirectory() as directory, self.assertRaisesRegex(ValueError, 'MailDomainName'):
            configuration.configure(self.aws, {'inputs': {}}, Path(directory))
        self.assertEqual(self.aws.calls, [])

    def test_dns_import_contains_fully_qualified_records_and_preserves_dns_only(self):
        config = dict(accountId='123456789012', region='ap-northeast-1', stacks={},
                      inputs={'runtime': {'MailDomainName': 'example.invalid'}})
        outputs = dict(ApiUrl='https://api.example.invalid', DeploymentRoleArn='deployment-role',
                       LoadBalancerDnsName='fixture.elb.amazonaws.com', ImageBaseUrl='https://images.example.invalid',
                       ImageDistributionDomainName='fixture.cloudfront.net',
                       MailDkimName1='fixture._domainkey.example.invalid.', MailDkimValue1='fixture.dkim.amazonses.com.')
        ca = b'-----BEGIN CERTIFICATE-----\nTEST ONLY\n-----END CERTIFICATE-----\n'
        with tempfile.TemporaryDirectory() as directory, patch('builtins.print'), \
                patch.object(configuration, 'urlopen', return_value=io.BytesIO(ca)):
            path = Path(directory)
            configuration.write_release_configuration(config, outputs, path)
            records = (path / 'dns-records.bind').read_text().splitlines()[1:]
            self.assertEqual(records, [
                'api.example.invalid. 300 IN CNAME fixture.elb.amazonaws.com. ; cf_tags=cf-proxied:false',
                'images.example.invalid. 300 IN CNAME fixture.cloudfront.net. ; cf_tags=cf-proxied:false',
                'fixture._domainkey.example.invalid. 300 IN CNAME fixture.dkim.amazonses.com. ; cf_tags=cf-proxied:false',
            ])
            self.assertEqual((path / 'dns-records.bind').stat().st_mode & 0o777, 0o600)


class WikiScheduleTests(unittest.TestCase):
    def test_failed_or_inactive_service_cannot_update_schedule(self):
        for status, rollout in (('INACTIVE', 'COMPLETED'), ('ACTIVE', 'FAILED')):
            service = dict(status=status, desiredCount=1, runningCount=1, pendingCount=0,
                           deployments=[{'rolloutState': rollout}])
            def aws(*args):
                self.assertEqual(args[:2], ('ecs', 'describe-services'))
                return {'services': [service]}
            with self.assertRaisesRegex(ValueError, 'stable deployed API'):
                configuration.configure_wiki_schedule(aws, dict(ClusterArn='cluster', ApiServiceName='api'))

    def test_schedule_reuses_live_image_is_repeatable_and_preserves_manual_disable(self):
        outputs = dict(ClusterArn='cluster', ApiServiceName='api', ApiReleaseFamily='project-api-release',
                       ApiTaskRoleArn='api-role', WorkerTaskRoleArn='worker-role', AppExecutionRoleArn='execution',
                       SchedulerBootstrapTaskDefinitionArn='scheduler-bootstrap:1', SchedulerReleaseFamily='project-scheduler-release',
                       RepositoryUri='registry/repo', SchedulerArn='arn:aws:scheduler:ap-northeast-1:123:schedule/project/wiki')
        source = dict(family='project-api-release', taskRoleArn='api-role', executionRoleArn='execution',
                      containerDefinitions=[dict(name='api', image='registry/repo@sha256:' + 'a' * 64,
                                                 environment=[{'name': 'APP_ENV', 'value': 'production'}],
                                                 secrets=[{'name': 'APP_KEY', 'valueFrom': 'secret:APP_KEY::'}])])
        base = dict(family='project-scheduler-bootstrap', taskRoleArn='worker-role', executionRoleArn='execution',
                    containerDefinitions=[dict(name='scheduler', readonlyRootFilesystem=True)])
        definitions = {'api-release:1': source, 'scheduler-bootstrap:1': base}
        current = dict(Name='wiki', GroupName='project', State='DISABLED', FlexibleTimeWindow={'Mode': 'OFF'},
                       ScheduleExpression='cron(0 3 1 * ? *)', ScheduleExpressionTimezone='Asia/Tokyo',
                       Target={'EcsParameters': {'TaskDefinitionArn': 'scheduler-bootstrap:1'}})
        mutations = []
        def aws(service, operation, *args):
            if operation == 'describe-services':
                return {'services': [dict(status='ACTIVE', taskDefinition='api-release:1', desiredCount=1, runningCount=1,
                                          pendingCount=0, deployments=[{'status': 'PRIMARY'}])]}
            if operation == 'describe-task-definition':
                return {'taskDefinition': copy.deepcopy(definitions[args[1]])}
            if operation == 'get-schedule':
                return copy.deepcopy(current)
            if operation == 'register-task-definition':
                mutations.append(operation)
                definitions['scheduler-release:2'] = json.loads(args[1])
                return {'taskDefinition': {'taskDefinitionArn': 'scheduler-release:2'}}
            if operation == 'update-schedule':
                mutations.append(operation)
                current.update(json.loads(args[1]))
                return {}
            raise AssertionError(operation)
        configuration.configure_wiki_schedule(aws, outputs)
        self.assertEqual(current['State'], 'ENABLED')
        container = definitions['scheduler-release:2']['containerDefinitions'][0]
        self.assertEqual(container['command'], ['php', 'artisan', 'wiki:process-role-promotion', '--sync'])
        self.assertEqual(container['image'], source['containerDefinitions'][0]['image'])
        mutations.clear()
        configuration.configure_wiki_schedule(aws, outputs)
        self.assertEqual(mutations, [])
        current['State'] = 'DISABLED'
        source['containerDefinitions'][0]['image'] = 'registry/repo@sha256:' + 'b' * 64
        configuration.configure_wiki_schedule(aws, outputs)
        self.assertEqual(current['State'], 'DISABLED')
        source['taskRoleArn'] = 'foreign-role'
        mutations.clear()
        with self.assertRaises(ValueError):
            configuration.configure_wiki_schedule(aws, outputs)
        self.assertEqual(mutations, [])


@unittest.skipUnless(os.environ.get('RUN_DATABASE_SETUP_INTEGRATION') == '1', 'Optional isolated PostgreSQL 18 integration test')
class DatabasePrivilegesTests(unittest.TestCase):
    def test_real_postgres_role_creation_repeat_and_dml_ddl_separation(self):
        name = 'kpool-database-setup-test-' + uuid.uuid4().hex[:12]
        subprocess.run(['docker', 'run', '--detach', '--rm', '--network', 'none', '--name', name,
                        '-e', 'POSTGRES_PASSWORD=fixture-master', 'postgres:18-alpine'],
                       check=True, capture_output=True, timeout=90)
        self.addCleanup(lambda: subprocess.run(['docker', 'rm', '-f', name], capture_output=True, timeout=30))
        for _ in range(100):
            ready = subprocess.run(['docker', 'exec', name, 'pg_isready', '-h', '127.0.0.1', '-U', 'postgres'], capture_output=True)
            if ready.returncode == 0:
                break
            time.sleep(0.2)
        else:
            self.fail('Disposable PostgreSQL did not start')
        def sql(user, text, password=None):
            args = ['docker', 'exec', '-i', '-e', 'PGPASSWORD=' + (password or 'fixture-master'),
                    '-e', 'APP_DB_PASSWORD=fixture-app', '-e', 'MIGRATION_DB_PASSWORD=fixture-migration', name,
                    'psql', '--no-psqlrc', '--quiet', '--set=ON_ERROR_STOP=1', '-h', '127.0.0.1',
                    '-U', user, '-d', 'postgres' if user == 'postgres' else 'kpool']
            return subprocess.run(args, input=text, text=True, capture_output=True, timeout=30)
        bootstrap = sql('postgres', "CREATE ROLE kpooladmin LOGIN CREATEROLE CREATEDB PASSWORD 'fixture-master';\nCREATE DATABASE kpool OWNER kpooladmin;")
        self.assertEqual(bootstrap.returncode, 0, bootstrap.stderr)
        template, errors = decode(str(configuration.ROOT / 'runtime.yaml'))
        self.assertFalse(errors)
        script = template['Resources']['DatabaseInitializationTaskDefinition']['Properties']['ContainerDefinitions'][0]['Command'][0]
        body = script.split("<<'SQL'\n", 1)[1].split('\nSQL', 1)[0]
        for _ in range(2):
            result = sql('kpooladmin', body)
            self.assertEqual(result.returncode, 0, result.stderr)
        migration = sql('kpool_migration', 'CREATE TABLE fixture_items (id bigserial PRIMARY KEY, title text);', 'fixture-migration')
        self.assertEqual(migration.returncode, 0, migration.stderr)
        dml = sql('kpool_app', "INSERT INTO fixture_items(title) VALUES ('works'); SELECT * FROM fixture_items;", 'fixture-app')
        self.assertEqual(dml.returncode, 0, dml.stderr)
        self.assertNotEqual(sql('kpool_app', 'CREATE TABLE forbidden(id int);', 'fixture-app').returncode, 0)
        self.assertNotEqual(sql('kpool_app', 'DROP TABLE fixture_items;', 'fixture-app').returncode, 0)


if __name__ == '__main__':
    unittest.main()
