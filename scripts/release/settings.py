"""Check required production settings before expensive builds."""
import json
import os
import re
import sys

from contract import SHA, validate_ca


def validate_settings(environment):
    target = environment.get('RELEASE_TARGET')
    mode = environment.get('RELEASE_MODE')
    if target not in ('backend', 'frontend', 'all'):
        raise ValueError('Unknown release target')
    required = {'LIVE_FRONTEND_SHA'}
    if target != 'frontend':
        required.update(('AWS_ACCOUNT_ID', 'AWS_DEPLOY_ROLE_ARN', 'BACKEND_RELEASE_CONFIGURATION'))
        if mode == 'deploy':
            required.update(('RDS_CA_PEM', 'RDS_CA_SHA256'))
    if target != 'backend':
        required.add('FRONTEND_RELEASE_CONFIGURATION')
        if environment.get('HAS_CLOUDFLARE_API_TOKEN') != 'true':
            required.add('CLOUDFLARE_API_TOKEN')
    if target == 'frontend':
        required.add('LIVE_BACKEND_SHA')
    missing = sorted(key for key in required if not environment.get(key))
    if missing:
        raise ValueError('Missing production Environment settings: ' + ', '.join(missing))
    for key in ('LIVE_FRONTEND_SHA', 'LIVE_BACKEND_SHA'):
        if key in required and not SHA.fullmatch(environment[key]):
            raise ValueError(key + ' must be a full commit SHA')
    if target != 'frontend':
        account = environment['AWS_ACCOUNT_ID']
        role = environment['AWS_DEPLOY_ROLE_ARN']
        if (not re.fullmatch(r'[0-9]{12}', account)
                or not re.fullmatch('arn:aws:iam::' + account + r':role/[A-Za-z0-9_+=,.@/-]+', role)):
            raise ValueError('AWS account and deployment role must match')
        try:
            config = json.loads(environment['BACKEND_RELEASE_CONFIGURATION'])
        except (ValueError, TypeError):
            raise ValueError('BACKEND_RELEASE_CONFIGURATION must be valid JSON') from None
        if (not isinstance(config, dict) or config.get('paused') is not False
                or config.get('region') != 'ap-northeast-1'
                or config.get('account_id') != account or config.get('deployment_role_arn') != role):
            raise ValueError('Backend configuration is paused or differs from the AWS settings')
        if mode == 'deploy':
            validate_ca(environment['RDS_CA_PEM'], environment['RDS_CA_SHA256'])


if __name__ == '__main__':
    try:
        validate_settings(os.environ)
        print('Required production settings are present; live resources are checked during deployment.')
    except ValueError as error:
        print(str(error), file=sys.stderr)
        sys.exit(1)
