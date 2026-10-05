"""Offline behavioral tests; identifiers in fixtures are never runtime defaults."""
import unittest
from contract import validate_source

class SourceTests(unittest.TestCase):
    def test_only_backend_repository_and_full_sha_are_accepted(self):
        with self.assertRaises(ValueError):
            validate_source('attacker/backend', 'a' * 40)
        with self.assertRaises(ValueError):
            validate_source('kpool09122/kpool-backend', 'main; touch /tmp/injection')
        self.assertEqual(validate_source('kpool09122/kpool-backend', 'a' * 40), 'a' * 40)

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

class CaTests(unittest.TestCase):
    def test_public_ca_requires_exact_checksum_and_pem_shape(self):
        from contract import validate_ca
        import hashlib
        pem='-----BEGIN CERTIFICATE-----\nfixture-not-a-live-certificate\n-----END CERTIFICATE-----\n'
        self.assertEqual(validate_ca(pem,hashlib.sha256(pem.encode()).hexdigest()),pem.encode())
        for value,digest in [(pem,'0'*64),('not a pem','0'*64)]:
            with self.assertRaises(ValueError): validate_ca(value,digest)
