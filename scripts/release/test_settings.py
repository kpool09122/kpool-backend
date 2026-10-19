import copy
import hashlib
import json
import unittest

from settings import validate_settings


def fixture(target='all'):
    account = '123456789012'
    role = 'arn:aws:iam::' + account + ':role/fixture-deployment'
    ca = '-----BEGIN CERTIFICATE-----\nTEST ONLY\n-----END CERTIFICATE-----\n'
    return dict(RELEASE_TARGET=target, RELEASE_MODE='deploy', AWS_ACCOUNT_ID=account,
                AWS_DEPLOY_ROLE_ARN=role, LIVE_FRONTEND_SHA='a' * 40, LIVE_BACKEND_SHA='b' * 40,
                BACKEND_RELEASE_CONFIGURATION=json.dumps(dict(paused=False, region='ap-northeast-1',
                                                             account_id=account, deployment_role_arn=role)),
                RDS_CA_PEM=ca, RDS_CA_SHA256=hashlib.sha256(ca.encode()).hexdigest(),
                FRONTEND_RELEASE_CONFIGURATION='{}', HAS_CLOUDFLARE_API_TOKEN='true')


class SettingsTests(unittest.TestCase):
    def test_missing_settings_are_listed_together_without_values(self):
        environment = fixture()
        environment.update(AWS_ACCOUNT_ID='', RDS_CA_PEM='', HAS_CLOUDFLARE_API_TOKEN='false')
        with self.assertRaises(ValueError) as failure:
            validate_settings(environment)
        message = str(failure.exception)
        for key in ('AWS_ACCOUNT_ID', 'RDS_CA_PEM', 'CLOUDFLARE_API_TOKEN'):
            self.assertIn(key, message)
        self.assertNotIn(environment['AWS_DEPLOY_ROLE_ARN'], message)

    def test_target_only_requires_its_own_credentials(self):
        for target in ('backend', 'frontend', 'all'):
            environment = fixture(target)
            if target == 'backend':
                for key in ('FRONTEND_RELEASE_CONFIGURATION', 'LIVE_BACKEND_SHA', 'HAS_CLOUDFLARE_API_TOKEN'):
                    environment.pop(key)
            if target == 'frontend':
                for key in ('AWS_ACCOUNT_ID', 'AWS_DEPLOY_ROLE_ARN', 'BACKEND_RELEASE_CONFIGURATION',
                            'RDS_CA_PEM', 'RDS_CA_SHA256'):
                    environment.pop(key)
            validate_settings(environment)

    def test_recovery_does_not_require_build_ca(self):
        environment = fixture('backend')
        environment.update(RELEASE_MODE='rollback-api', RDS_CA_PEM='', RDS_CA_SHA256='')
        validate_settings(environment)

    def test_wrong_account_paused_configuration_and_corrupt_ca_stop_before_build(self):
        for change in ({'account_id': '999999999999'}, {'paused': True}, {'region': 'us-east-1'}):
            environment = fixture()
            config = json.loads(environment['BACKEND_RELEASE_CONFIGURATION'])
            environment['BACKEND_RELEASE_CONFIGURATION'] = json.dumps(dict(config, **change))
            with self.assertRaises(ValueError):
                validate_settings(environment)
        for key, value in (('LIVE_FRONTEND_SHA', 'main'), ('RDS_CA_SHA256', 'c' * 64),
                           ('BACKEND_RELEASE_CONFIGURATION', '[]'),
                           ('BACKEND_RELEASE_CONFIGURATION', 'sensitive-invalid-json')):
            environment = copy.deepcopy(fixture())
            environment[key] = value
            with self.assertRaises(ValueError) as failure:
                validate_settings(environment)
            self.assertNotIn('sensitive-invalid-json', str(failure.exception))


if __name__ == '__main__':
    unittest.main()
