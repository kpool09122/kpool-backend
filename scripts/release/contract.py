"""Release input and task-definition contracts; no credentials or cloud mutation."""
import copy
import hashlib
import re

BACKEND = 'kpool09122/kpool-backend'
FRONTEND = 'kpool09122/kpool-frontend'
SHA = re.compile(r'[0-9a-f]{40}\Z')
DIGEST = re.compile(r'sha256:[0-9a-f]{64}\Z')


def validate_source(repository, sha):
    if repository not in (BACKEND, FRONTEND) or not SHA.fullmatch(sha):
        raise ValueError('Repository or full source SHA is not permitted')
    return sha

# Explicit nonsecret allowlist prevents accidental SSM/GitHub-variable secret export.
ENV_KEYS = set('APP_ENV APP_DEBUG APP_URL FRONTEND_URL LOG_CHANNEL LOG_LEVEL AWS_DEFAULT_REGION DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_SSLMODE DB_SSLROOTCERT REDIS_HOST REDIS_PORT REDIS_DB REDIS_CACHE_DB REDIS_USERNAME REDIS_SCHEME QUEUE_CONNECTION SQS_QUEUE_URL IMAGE_STORAGE_DISK VERIFICATION_DOCUMENTS_DRIVER AWS_PUBLIC_IMAGES_BUCKET AWS_PRIVATE_FILES_BUCKET IMAGE_BASE_URL CACHE_STORE SESSION_DRIVER SESSION_DOMAIN SESSION_SECURE_COOKIE SESSION_SAME_SITE MAIL_MAILER MAIL_HOST MAIL_PORT MAIL_FROM_ADDRESS WEBAUTHN_RP_ID WEBAUTHN_ALLOWED_ORIGINS'.split())
SECRET_KEYS = set('APP_KEY DATABASE_URL DB_USERNAME DB_PASSWORD REDIS_PASSWORD REDIS_URL MAIL_USERNAME MAIL_PASSWORD STRIPE_SECRET_KEY STRIPE_WEBHOOK_SECRET OPENAI_API_KEY GOOGLE_CLIENT_ID GOOGLE_CLIENT_SECRET DISCORD_CLIENT_ID DISCORD_CLIENT_SECRET SENTRY_LARAVEL_DSN WIKI_VISITOR_LOCATION_SECRET'.split())
SECRET_REF = re.compile(r'arn:aws:secretsmanager:ap-northeast-1:[0-9]{12}:secret:[A-Za-z0-9/_+=.@-]+:[A-Za-z0-9_]+::\Z')


def runtime_environment(config):
    if not isinstance(config, dict) or not set(config) <= ENV_KEYS:
        raise ValueError('Unknown or secret runtime configuration key')
    if any(not isinstance(v, str) or '\n' in v or len(v) > 4096 for v in config.values()):
        raise ValueError('Runtime configuration must contain bounded string values')
    return [{'name': k, 'value': v} for k, v in sorted(config.items())]


def task_definition(kind, template, outputs, config, secrets, digest):
    if kind not in ('Api', 'Worker', 'Migration') or not DIGEST.fullmatch(digest):
        raise ValueError('Invalid task kind or image digest')
    fields = ('family taskRoleArn executionRoleArn networkMode containerDefinitions volumes placementConstraints requiresCompatibilities cpu memory runtimePlatform ephemeralStorage').split()
    definition = {k: copy.deepcopy(v) for k, v in template.items() if k in fields}
    execution = 'MigrationExecutionRoleArn' if kind == 'Migration' else 'AppExecutionRoleArn'
    if (template.get('taskRoleArn') != outputs[kind+'TaskRoleArn']
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
            if len(matching) != 1 or matching[0] != {'sourceVolume': name, 'containerPath': path, 'readOnly': False} or {'name': name} not in volumes:
                raise ValueError('Bootstrap writable volume contract mismatch')
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
