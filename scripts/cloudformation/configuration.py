"""Create or reconcile operator configuration while preserving live credentials."""
import base64
import hashlib
import json
import os
from pathlib import Path
import secrets
import tempfile
import time
from urllib.parse import quote, unquote, urlsplit
from urllib.request import urlopen

from environment import load_contract, validate_config
from operations import Aws, AwsError, output_values, read_stack

ROOT = Path(__file__).resolve().parents[2] / 'infra/cloudformation'
CA_URL = 'https://truststore.pki.rds.amazonaws.com/ap-northeast-1/ap-northeast-1-bundle.pem'


def infrastructure(aws, config):
    templates, contract = load_contract(ROOT)
    validate_config(config, templates, contract)
    if aws('sts', 'get-caller-identity')['Account'] != config['accountId']:
        raise ValueError('AWS account mismatch')
    outputs = {}
    for key in contract['deploymentOrder']:
        stack = read_stack(aws, config['stacks'][key])
        if not stack or stack['StackStatus'] not in ('CREATE_COMPLETE', 'UPDATE_COMPLETE'):
            raise ValueError('Apply all four parent stacks before initial setup')
        outputs.update(output_values(stack))
    return outputs


def services_are_inactive(aws, outputs):
    services = aws('ecs', 'describe-services', '--cluster', outputs['ClusterArn'],
                   '--services', outputs['ApiServiceName'], outputs['WorkerServiceName'])
    found = services.get('services', [])
    if (services.get('failures') or len(found) != 2
            or {s['serviceName'] for s in found} != {outputs['ApiServiceName'], outputs['WorkerServiceName']}
            or any(s.get('status') != 'ACTIVE' for s in found)):
        raise ValueError('API/worker service lookup is incomplete')
    return all(all(s.get(k) == 0 for k in ('desiredCount', 'runningCount', 'pendingCount')) for s in found)


def inactive_services(aws, outputs):
    if not services_are_inactive(aws, outputs):
        raise ValueError('Initial database setup requires zero API/worker tasks')



def secret_value(aws, arn):
    metadata = aws('secretsmanager', 'describe-secret', '--secret-id', arn)
    if not metadata.get('VersionIdsToStages'):
        return None
    response = aws('secretsmanager', 'get-secret-value', '--secret-id', arn)
    value = json.loads(response['SecretString'])
    if not isinstance(value, dict):
        raise ValueError('Expected a JSON Secret; use a separate migration for existing string secrets')
    return value


def new_database_secret(outputs, user, app_key):
    password = secrets.token_urlsafe(32)
    url = ('postgresql://' + user + ':' + quote(password, safe='') + '@'
           + outputs['DatabaseEndpoint'] + ':5432/' + outputs['DatabaseName']
           + '?sslmode=verify-full&sslrootcert=/etc/ssl/certs/kpool-rds-ca.pem')
    return {'APP_KEY': app_key, 'DATABASE_URL': url, 'DB_USERNAME': user, 'DB_PASSWORD': password}


def validate_database_secret(value, outputs, user):
    url = urlsplit(value.get('DATABASE_URL', ''))
    if (value.get('DB_USERNAME') != user or not value.get('DB_PASSWORD')
            or not value.get('APP_KEY', '').startswith('base64:')
            or url.scheme != 'postgresql' or url.port != 5432
            or url.hostname != outputs['DatabaseEndpoint'] or url.username != user
            or unquote(url.password or '') != value['DB_PASSWORD'] or url.path != '/' + outputs['DatabaseName']
            or url.query != 'sslmode=verify-full&sslrootcert=/etc/ssl/certs/kpool-rds-ca.pem'):
        raise ValueError('Existing database Secret differs from the initial setup contract')


def initialize_secrets(aws, outputs):
    app = secret_value(aws, outputs['AppSecretArn'])
    migration = secret_value(aws, outputs['MigrationSecretArn'])
    if app is None or migration is None:
        inactive_services(aws, outputs)
    cache = secret_value(aws, outputs['CacheSecretArn'])
    if not cache or not isinstance(cache.get('password'), str) or not cache['password']:
        raise ValueError('Valkey password JSON is unavailable')
    app_key = ((app or migration or {}).get('APP_KEY')
               or 'base64:' + base64.b64encode(secrets.token_bytes(32)).decode())
    app_value = dict(app) if app is not None else new_database_secret(outputs, 'kpool_app', app_key)
    migration_value = migration if migration is not None else new_database_secret(outputs, 'kpool_migration', app_key)
    cache_url = ('tls://application:' + quote(cache['password'], safe='') + '@'
                 + outputs['CacheEndpoint'] + ':' + outputs['CachePort'] + '/0')
    if app is None:
        app_value['REDIS_URL'] = cache_url
    if not app_value.get('WIKI_VISITOR_LOCATION_SECRET'):
        app_value['WIKI_VISITOR_LOCATION_SECRET'] = secrets.token_urlsafe(32)
    for value, user in ((app_value, 'kpool_app'), (migration_value, 'kpool_migration')):
        validate_database_secret(value, outputs, user)
    if app_value['APP_KEY'] != migration_value['APP_KEY'] or app_value.get('REDIS_URL') != cache_url:
        raise ValueError('Existing app/migration/cache Secrets are inconsistent; no rotation performed')
    # Validate both destinations first. Resume after interruption preserves stored
    # APP_KEY and passwords; existing app values gain only a missing Wiki key.
    for old, value, arn in ((app, app_value, outputs['AppSecretArn']),
                            (migration, migration_value, outputs['MigrationSecretArn'])):
        if old == value:
            continue
        with tempfile.TemporaryDirectory(prefix='kpool-secret-') as directory:
            path = Path(directory) / 'value.json'
            fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
            with os.fdopen(fd, 'w') as stream:
                json.dump(value, stream)
            aws('secretsmanager', 'put-secret-value', '--secret-id', arn,
                '--secret-string', 'file://' + str(path))


def database_setup(aws, outputs):
    inactive_services(aws, outputs)
    # Confirm secrets before starting the administrator-only task. No values are
    # sent as task overrides, CLI arguments, records, or application task roles.
    for key, user in (('AppSecretArn', 'kpool_app'), ('MigrationSecretArn', 'kpool_migration')):
        validate_database_secret(secret_value(aws, outputs[key]) or {}, outputs, user)
    response = aws('ecs', 'run-task', '--cluster', outputs['ClusterArn'],
                   '--task-definition', outputs['DatabaseSetupTaskDefinitionArn'],
                   '--launch-type', 'FARGATE', '--count', '1',
                   '--network-configuration', json.dumps({'awsvpcConfiguration': {
                       'subnets': outputs['PublicSubnetIds'].split(','),
                       'securityGroups': [outputs['MigrationSecurityGroupId']], 'assignPublicIp': 'ENABLED'}}))
    if response.get('failures') or len(response.get('tasks', [])) != 1:
        raise ValueError('Database setup task did not launch')
    task_arn = response['tasks'][0]['taskArn']
    print('Database setup task: ' + task_arn, flush=True)
    deadline = time.monotonic() + 600
    while time.monotonic() < deadline:
        result = aws('ecs', 'describe-tasks', '--cluster', outputs['ClusterArn'], '--tasks', task_arn)
        tasks = result.get('tasks', [])
        if result.get('failures') or len(tasks) != 1 or tasks[0]['taskArn'] != task_arn:
            raise ValueError('Database setup task lookup failed; inspect the recorded task ARN')
        task = tasks[0]
        if task['lastStatus'] == 'STOPPED':
            containers = [c for c in task.get('containers', []) if c['name'] == 'database-setup']
            if len(containers) != 1 or containers[0].get('exitCode') != 0:
                raise ValueError('Database setup failed; inspect the task and database-setup log group')
            return
        time.sleep(5)
    raise ValueError('Database setup timed out; inspect task state before rerunning')


def configure(aws, config, path):
    if not path or not path.is_absolute() or path.resolve().is_relative_to(ROOT.parents[1]):
        raise ValueError('Configuration output directory must be absolute and outside the repository')
    if not config['inputs'].get('runtime', {}).get('MailDomainName'):
        raise ValueError('Configure runtime.MailDomainName for SES first')
    existing_path = path / 'backend-release-configuration.json'
    if existing_path.exists():
        existing = json.loads(existing_path.read_text())
        if existing.get('account_id') != config['accountId'] or existing.get('stacks') != config['stacks']:
            raise ValueError('Existing configuration belongs to another environment')
    outputs = infrastructure(aws, config)
    initialize_secrets(aws, outputs)
    if services_are_inactive(aws, outputs):
        database_setup(aws, outputs)
    write_release_configuration(config, outputs, path)
    if not services_are_inactive(aws, outputs):
        configure_wiki_schedule(aws, outputs)


def configure_wiki_schedule(aws, outputs):
    """Keep the monthly batch on the live API image without deploying the API."""
    service_response = aws('ecs', 'describe-services', '--cluster', outputs['ClusterArn'],
                           '--services', outputs['ApiServiceName'])
    services = service_response.get('services', [])
    if (service_response.get('failures') or len(services) != 1
            or services[0].get('status') != 'ACTIVE'
            or services[0].get('pendingCount') != 0 or services[0].get('desiredCount', 0) < 1
            or services[0].get('runningCount') != services[0]['desiredCount']
            or len(services[0].get('deployments', [])) != 1
            or services[0]['deployments'][0].get('rolloutState') == 'FAILED'):
        raise ValueError('Wait for a stable deployed API before configuring the Wiki schedule')
    source = aws('ecs', 'describe-task-definition', '--task-definition', services[0]['taskDefinition'])['taskDefinition']
    base = aws('ecs', 'describe-task-definition', '--task-definition', outputs['SchedulerBootstrapTaskDefinitionArn'])['taskDefinition']
    if (source['family'] != outputs['ApiReleaseFamily'] or source['taskRoleArn'] != outputs['ApiTaskRoleArn']
            or source['executionRoleArn'] != outputs['AppExecutionRoleArn']
            or len(source['containerDefinitions']) != 1
            or not source['containerDefinitions'][0]['image'].startswith(outputs['RepositoryUri'] + '@sha256:')
            or base['taskRoleArn'] != outputs['WorkerTaskRoleArn']
            or base['executionRoleArn'] != outputs['AppExecutionRoleArn']):
        raise ValueError('Wiki scheduler source roles/image do not belong to this environment')
    fields = 'family taskRoleArn executionRoleArn networkMode containerDefinitions volumes requiresCompatibilities cpu memory runtimePlatform ephemeralStorage'.split()
    definition = {key: base[key] for key in fields if key in base}
    definition['family'] = outputs['SchedulerReleaseFamily']
    container = definition['containerDefinitions'][0]
    if container['name'] != 'scheduler' or not container.get('readonlyRootFilesystem'):
        raise ValueError('Invalid scheduler bootstrap container')
    for key in ('image', 'environment', 'secrets'):
        container[key] = source['containerDefinitions'][0][key]
    container['command'] = ['php', 'artisan', 'wiki:process-role-promotion', '--sync']
    group, name = outputs['SchedulerArn'].split(':schedule/', 1)[1].split('/')
    current = aws('scheduler', 'get-schedule', '--group-name', group, '--name', name)
    current_arn = current['Target']['EcsParameters']['TaskDefinitionArn']
    existing = aws('ecs', 'describe-task-definition', '--task-definition', current_arn)['taskDefinition']
    comparable = {key: existing[key] for key in fields if key in existing}
    if comparable == definition and current.get('ScheduleExpression') == 'cron(0 3 1 * ? *)' and current.get('ScheduleExpressionTimezone') == 'Asia/Tokyo':
        return
    new_arn = current_arn
    if comparable != definition:
        result = aws('ecs', 'register-task-definition', '--cli-input-json', json.dumps(definition))
        new_arn = result['taskDefinition']['taskDefinitionArn']
    selected = 'Name GroupName Description StartDate EndDate ScheduleExpression ScheduleExpressionTimezone State FlexibleTimeWindow Target KmsKeyArn ActionAfterCompletion'.split()
    request = {key: current[key] for key in selected if key in current}
    request.update(Name=name, GroupName=group, ScheduleExpression='cron(0 3 1 * ? *)',
                   ScheduleExpressionTimezone='Asia/Tokyo')
    request['Target']['EcsParameters']['TaskDefinitionArn'] = new_arn
    if current_arn == outputs['SchedulerBootstrapTaskDefinitionArn']:
        request['State'] = 'ENABLED'
    aws('scheduler', 'update-schedule', '--cli-input-json', json.dumps(request))


def write_release_configuration(config, outputs, path):
    if not path or not path.is_absolute():
        raise ValueError('Provide an absolute output directory')
    domain = config['inputs'].get('runtime', {}).get('MailDomainName')
    if not domain:
        raise ValueError('Configure runtime.MailDomainName for SES first')
    # Deployment destinations are stable; source versions belong to each Actions run.
    value = {
        'paused': False, 'region': config['region'], 'account_id': config['accountId'],
        'deployment_role_arn': outputs['DeploymentRoleArn'], 'stacks': config['stacks'],
        'runtime_environment': {
            'APP_URL': outputs['ApiUrl'], 'FRONTEND_URL': 'https://' + domain,
            'CACHE_STORE': 'redis', 'SESSION_DRIVER': 'redis', 'SESSION_SECURE_COOKIE': 'true',
            'SESSION_SAME_SITE': 'lax', 'SESSION_DOMAIN': '.' + domain,
            'MAIL_MAILER': 'ses', 'MAIL_FROM_ADDRESS': 'noreply@' + domain,
            'WEBAUTHN_RP_ID': domain, 'WEBAUTHN_ALLOWED_ORIGINS': 'https://' + domain,
        },
        'secret_contract': {'DATABASE_URL': 'verify-full:/etc/ssl/certs/kpool-rds-ca.pem', 'REDIS_URL': 'tls:db0'},
        'app_secret_keys': ['WIKI_VISITOR_LOCATION_SECRET'],
    }
    path.mkdir(mode=0o700, parents=True, exist_ok=True)
    existing_path = path / 'backend-release-configuration.json'
    if existing_path.exists():
        existing = json.loads(existing_path.read_text())
        value['app_secret_keys'] = sorted(set(value['app_secret_keys']) | set(existing.get('app_secret_keys', [])))
        value['runtime_environment'] = {**value['runtime_environment'], **existing.get('runtime_environment', {}),
                                        'APP_URL': outputs['ApiUrl']}
        value['paused'] = existing.get('paused', False)
        if existing.get('hook_code_sha256'):
            value['hook_code_sha256'] = existing['hook_code_sha256']
    with urlopen(CA_URL, timeout=30) as response:
        ca = response.read(48001)
    if len(ca) > 48000 or not ca.startswith(b'-----BEGIN CERTIFICATE-----'):
        raise ValueError('Unexpected public RDS CA bundle')
    values = {'AWS_ACCOUNT_ID': config['accountId'], 'AWS_DEPLOY_ROLE_ARN': outputs['DeploymentRoleArn'],
              'RDS_CA_SHA256': hashlib.sha256(ca).hexdigest()}
    dns_records = []
    for hostname, target in ((urlsplit(outputs['ApiUrl']).hostname, outputs.get('LoadBalancerDnsName')),
                             (urlsplit(outputs.get('ImageBaseUrl', '')).hostname, outputs.get('ImageDistributionDomainName'))):
        if hostname and target:
            dns_records.append({'type': 'CNAME', 'name': hostname, 'content': target, 'proxied': False})
    for i in range(1, 4):
        if outputs.get('MailDkimName' + str(i)) and outputs.get('MailDkimValue' + str(i)):
            dns_records.append({'type': 'CNAME', 'name': outputs['MailDkimName' + str(i)],
                                'content': outputs['MailDkimValue' + str(i)], 'proxied': False})
    dns_bind = '; API, images and SES DKIM records for Cloudflare import\n'
    for record in dns_records:
        dns_bind += (record['name'].rstrip('.') + '. 300 IN CNAME '
                     + record['content'].rstrip('.') + '. ; cf_tags=cf-proxied:false\n')
    for name, data in (('dns-records.json', json.dumps(dns_records, indent=2)),
                       ('dns-records.bind', dns_bind),
                       ('backend-release-configuration.json', json.dumps(value, indent=2)),
                       ('github-variables.json', json.dumps(values, indent=2)),
                       ('rds-ca.pem', ca.decode())):
        fd, temporary = tempfile.mkstemp(prefix='.configure-', dir=path)
        try:
            with os.fdopen(fd, 'w') as stream:
                stream.write(data)
            os.replace(temporary, path / name)
        finally:
            if os.path.exists(temporary):
                os.unlink(temporary)
    print('Release configuration written to ' + str(path))

