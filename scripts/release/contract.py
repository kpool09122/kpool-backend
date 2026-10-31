"""Release input and task-definition contracts; no credentials or cloud mutation."""
import copy
import hashlib
import re

BACKEND = 'kpool09122/kpool-backend'
FRONTEND = 'kpool09122/kpool-frontend'
SHA = re.compile(r'[0-9a-f]{40}\Z')
DIGEST = re.compile(r'sha256:[0-9a-f]{64}\Z')
RELEASE_BRANCH = re.compile(r'release/[A-Za-z0-9][A-Za-z0-9._-]*\Z')
RELEASE_TAG = re.compile(r'v[A-Za-z0-9][A-Za-z0-9._-]*\Z')


def validate_source(repository, sha):
    if repository not in (BACKEND, FRONTEND) or not SHA.fullmatch(sha):
        raise ValueError('Repository or full source SHA is not permitted')
    return sha

SECRET_KEYS = set('APP_KEY DATABASE_URL DB_USERNAME DB_PASSWORD REDIS_PASSWORD REDIS_URL MAIL_USERNAME MAIL_PASSWORD STRIPE_SECRET_KEY STRIPE_WEBHOOK_SECRET OPENAI_API_KEY GOOGLE_CLIENT_SECRET LINE_CLIENT_SECRET KAKAO_CLIENT_SECRET GENERATIVE_LANGUAGE_API_KEY GOOGLE_APPLICATION_CREDENTIALS YOUTUBE_API_KEY DISCORD_CLIENT_ID DISCORD_CLIENT_SECRET SENTRY_LARAVEL_DSN WIKI_VISITOR_LOCATION_SECRET'.split())
ENV_KEY = re.compile(r'[A-Z][A-Z0-9_]*\Z')
# The public Sentry DSN may stay in plain configuration; credentials use ECS secrets.
PLAIN_SECRET_KEYS = (SECRET_KEYS - {'SENTRY_LARAVEL_DSN'}) | {'APP_PREVIOUS_KEYS', 'MAIL_URL'}
SECRET_KEY_PATTERN = re.compile(r'(?:^|_)(?:SECRET|PASSWORD|TOKEN|CREDENTIALS?|PRIVATE_KEY|API_KEY|ACCESS_KEY)(?:_|$)')
SECRET_REF = re.compile(r'arn:aws:secretsmanager:ap-northeast-1:[0-9]{12}:secret:[A-Za-z0-9/_+=.@-]+:[A-Za-z0-9_]+::\Z')


def runtime_environment(config):
    if not isinstance(config, dict):
        raise ValueError('Runtime configuration must be an object')
    for key in config:
        if (not isinstance(key, str) or not ENV_KEY.fullmatch(key)
                or key in PLAIN_SECRET_KEYS or SECRET_KEY_PATTERN.search(key)):
            raise ValueError('Invalid or secret runtime configuration key')
    if any(not isinstance(v, str) or '\n' in v or len(v) > 4096 for v in config.values()):
        raise ValueError('Runtime configuration must contain bounded string values')
    return [{'name': k, 'value': v} for k, v in sorted(config.items())]


def task_definition(kind, template, outputs, config, secrets, digest):
    if kind not in ('Api', 'Worker', 'Migration', 'Scheduler') or not DIGEST.fullmatch(digest):
        raise ValueError('Invalid task kind or image digest')
    fields = ('family taskRoleArn executionRoleArn networkMode containerDefinitions volumes placementConstraints requiresCompatibilities cpu memory runtimePlatform ephemeralStorage').split()
    definition = {k: copy.deepcopy(v) for k, v in template.items() if k in fields}
    execution = 'MigrationExecutionRoleArn' if kind == 'Migration' else 'AppExecutionRoleArn'
    role_kind = 'Worker' if kind == 'Scheduler' else kind
    if (template.get('taskRoleArn') != outputs[role_kind+'TaskRoleArn']
            or template.get('executionRoleArn') != outputs[execution]
            or template.get('runtimePlatform', {}).get('cpuArchitecture') != 'ARM64'
            or template.get('networkMode') != 'awsvpc'
            or template.get('requiresCompatibilities') != ['FARGATE']):
        raise ValueError('Bootstrap role/platform contract mismatch')
    containers = definition['containerDefinitions']
    if len(containers) != 1 or containers[0]['name'] != kind.lower() or not containers[0].get('readonlyRootFilesystem'):
        raise ValueError('Bootstrap container/root filesystem contract mismatch')
    if not isinstance(secrets, list) or any(set(s) != {'name', 'valueFrom'} or s['name'] not in SECRET_KEYS or not SECRET_REF.fullmatch(s['valueFrom']) for s in secrets):
        raise ValueError('Only explicit ECS JSON-key secret ARN references are accepted')
    if len({s['name'] for s in secrets}) != len(secrets):
        raise ValueError('Duplicate secret names')
    definition['family'] = outputs[kind+'ReleaseFamily']
    container = containers[0]
    container['image'] = outputs['RepositoryUri'] + '@' + digest
    container['environment'] = runtime_environment(config)
    container['secrets'] = copy.deepcopy(secrets)
    # Fargate volumes copy image directory ownership (UID 1000); /tmp is mode 1777.
    # Preserve template mounts; reject collisions instead of silently replacing them.
    paths = {'release-tmp': '/tmp', 'release-storage': '/var/www/html/storage', 'release-cache': '/var/www/html/bootstrap/cache'}
    mounts = container.setdefault('mountPoints', [])
    volumes = definition.setdefault('volumes', [])
    for name, path in paths.items():
        matching = [m for m in mounts if m['containerPath'] == path]
        if matching:
            named_volumes = [volume for volume in volumes if volume.get('name') == name]
            if (len(matching) != 1
                    or matching[0] != {'sourceVolume': name, 'containerPath': path, 'readOnly': False}
                    or len(named_volumes) != 1
                    or named_volumes[0] not in ({'name': name}, {'name': name, 'host': {}})):
                raise ValueError('Bootstrap writable volume contract mismatch')
            # ECS describes an unnamed ephemeral host volume as host: {}.
            # Keep registration canonical without changing the bootstrap response.
            named_volumes[0].pop('host', None)
        else:
            if any(v['name'] == name for v in volumes):
                raise ValueError('Bootstrap writable volume collision')
            volumes.append({'name': name})
            mounts.append({'sourceVolume': name, 'containerPath': path, 'readOnly': False})
    return definition


def validate_ca(pem, checksum):
    data = pem.encode()
    if (not re.fullmatch('[0-9a-f]{64}', checksum) or hashlib.sha256(data).hexdigest() != checksum
            or len(data) > 48000 or not pem.startswith('-----BEGIN CERTIFICATE-----\n')
            or not pem.rstrip().endswith('-----END CERTIFICATE-----')):
        raise ValueError('Public RDS CA checksum/PEM contract mismatch')
    return data
