"""Offline behavioral tests; identifiers in fixtures are never runtime defaults."""
import copy
import unittest
from contract import validate_source

class SourceTests(unittest.TestCase):
    def test_only_backend_repository_and_full_sha_are_accepted(self):
        with self.assertRaises(ValueError):
            validate_source('attacker/backend', 'a' * 40)
        with self.assertRaises(ValueError):
            validate_source('kpool09122/kpool-backend', 'main; touch /tmp/injection')
        self.assertEqual(validate_source('kpool09122/kpool-backend', 'a' * 40), 'a' * 40)

class ExternalServiceConfigurationTests(unittest.TestCase):
    def test_oauth_public_settings_are_plain_environment_but_secrets_are_rejected(self):
        from contract import runtime_environment
        settings = {}
        for provider in ('GOOGLE', 'LINE', 'KAKAO'):
            settings[provider + '_CLIENT_ID'] = 'fixture-client'
            settings[provider + '_REDIRECT_URI'] = 'https://api.example.invalid/oauth/callback'
        settings['STRIPE_PUBLISHABLE_KEY'] = 'pk_test_fixture'
        self.assertEqual({item['name']: item['value'] for item in runtime_environment(settings)}, settings)
        for name in ('GOOGLE_CLIENT_SECRET', 'LINE_CLIENT_SECRET', 'KAKAO_CLIENT_SECRET',
                     'GENERATIVE_LANGUAGE_API_KEY', 'YOUTUBE_API_KEY', 'STRIPE_SECRET_KEY', 'STRIPE_WEBHOOK_SECRET'):
            with self.subTest(name=name), self.assertRaises(ValueError):
                runtime_environment({name: 'fixture-never-export'})

class DefinitionTests(unittest.TestCase):
    def test_template_is_preserved_and_runtime_config_is_nonsecret(self):
        from contract import task_definition
        template = {'family': 'fixture-api-bootstrap', 'taskRoleArn': 'fixture-api-role',
                    'executionRoleArn': 'fixture-execution-role', 'networkMode': 'awsvpc',
                    'requiresCompatibilities': ['FARGATE'], 'cpu': '512', 'memory': '1024',
                    'runtimePlatform': {'cpuArchitecture': 'ARM64', 'operatingSystemFamily': 'LINUX'},
                    'containerDefinitions': [{'name': 'api', 'image': 'bootstrap',
                        'readonlyRootFilesystem': True, 'stopTimeout': 120}]}
        outputs = {'ApiReleaseFamily': 'fixture-api-release', 'ApiTaskRoleArn': 'fixture-api-role',
                   'AppExecutionRoleArn': 'fixture-execution-role', 'RepositoryUri': 'fixture-registry/repo'}
        definition = task_definition('Api', template, outputs, {'APP_ENV': 'production'},
                                     [{'name': 'APP_KEY', 'valueFrom': 'arn:aws:secretsmanager:ap-northeast-1:123456789012:secret:fixture:APP_KEY::'}], 'sha256:' + 'a'*64)
        self.assertEqual(definition['family'], 'fixture-api-release')
        self.assertEqual(definition['containerDefinitions'][0]['image'], 'fixture-registry/repo@sha256:'+'a'*64)
        self.assertEqual({m['containerPath'] for m in definition['containerDefinitions'][0]['mountPoints']},
                         {'/tmp', '/var/www/html/storage', '/var/www/html/bootstrap/cache'})
        self.assertNotIn('environment', template['containerDefinitions'][0])
        with self.assertRaises(ValueError):
            task_definition('Api', template, outputs, {'DB_PASSWORD': 'never-log-me'}, [], 'sha256:'+'a'*64)

    def test_aws_empty_host_defaults_are_accepted_without_mutating_bootstrap(self):
        from contract import task_definition
        from fixtures import fixture, FakeAws
        outputs, environment, _, _, manifest = fixture()
        aws = FakeAws(outputs, environment)
        for kind in ('Api', 'Worker', 'Migration'):
            template = aws('ecs', 'describe-task-definition', taskDefinition=outputs[kind+'BootstrapTaskDefinitionArn'])['taskDefinition']
            original = copy.deepcopy(template)
            actual = task_definition(kind, template, outputs, environment, [], manifest['image_digest'])
            bare = copy.deepcopy(template)
            for volume in bare['volumes']: volume.pop('host')
            expected = task_definition(kind, bare, outputs, environment, [], manifest['image_digest'])
            with self.subTest(kind=kind):
                self.assertEqual(actual, expected)
                self.assertEqual(template, original)

    def test_required_writable_volumes_reject_storage_changes_and_duplicates(self):
        from contract import task_definition
        from fixtures import fixture, FakeAws
        outputs, environment, _, _, manifest = fixture()
        template = FakeAws(outputs, environment)('ecs', 'describe-task-definition',
                         taskDefinition=outputs['ApiBootstrapTaskDefinitionArn'])['taskDefinition']
        for replacement in ({'host': {'sourcePath': '/host'}},
                            {'efsVolumeConfiguration': {'fileSystemId': 'fs-unapproved'}},
                            {'dockerVolumeConfiguration': {'driver': 'local'}}):
            changed = copy.deepcopy(template)
            changed['volumes'][0].update(replacement)
            with self.subTest(replacement=replacement), self.assertRaises(ValueError):
                task_definition('Api', changed, outputs, environment, [], manifest['image_digest'])
        changed = copy.deepcopy(template)
        changed['volumes'].append(copy.deepcopy(changed['volumes'][0]))
        with self.assertRaises(ValueError):
            task_definition('Api', changed, outputs, environment, [], manifest['image_digest'])

class CaTests(unittest.TestCase):
    def test_public_ca_requires_exact_checksum_and_pem_shape(self):
        from contract import validate_ca
        import hashlib
        pem='-----BEGIN CERTIFICATE-----\nfixture-not-a-live-certificate\n-----END CERTIFICATE-----\n'
        self.assertEqual(validate_ca(pem,hashlib.sha256(pem.encode()).hexdigest()),pem.encode())
        for value,digest in [(pem,'0'*64),('not a pem','0'*64)]:
            with self.assertRaises(ValueError): validate_ca(value,digest)
