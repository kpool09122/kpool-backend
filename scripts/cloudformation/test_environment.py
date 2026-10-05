"""Environment input is an operator contract, not a copy of stack Outputs."""
import copy
import json
from pathlib import Path
import unittest

import environment

ROOT = Path(__file__).resolve().parents[2] / 'infra/cloudformation'


class EnvironmentTests(unittest.TestCase):
    def test_resolves_outputs_without_transcription_and_preserves_update_settings(self):
        config = json.loads((ROOT / 'environment.example.json').read_text())
        templates, contract = environment.load_contract(ROOT)
        outputs = {'root': {link['output']: 'live-' + link['output']
                            for link in contract['links'] if link['from'] == 'root'}}
        outputs['root']['QueueUrl'] = 'https://sqs.ap-northeast-1.amazonaws.com/123456789012/work'
        outputs['integration'] = {'AppSecretArn': 'app', 'MigrationSecretArn': 'migration'}
        result = environment.parameters(config, 'integration', templates, contract, outputs,
                                        {'MonthlyBudgetUSD': '250'})
        self.assertEqual(result['DatabaseEndpoint'], 'live-DatabaseEndpoint')
        self.assertEqual(result['MonthlyBudgetUSD'], '250')
        self.assertEqual(result['ProjectName'], config['projectName'])

    def test_rejects_unknown_secret_derived_and_pipeline_owned_inputs(self):
        templates, contract = environment.load_contract(ROOT)
        for stack, name in [('runtime', 'ApiDesiredCount'), ('runtime', 'VpcId'),
                            ('runtime', 'DB_PASSWORD'), ('root', 'Typo')]:
            config = json.loads((ROOT / 'environment.example.json').read_text())
            config['inputs'][stack][name] = 'unexpected'
            with self.subTest(name=name), self.assertRaises(ValueError):
                environment.validate_config(config, templates, contract)

    def test_rejects_invalid_runtime_input_offline(self):
        config = json.loads((ROOT / 'environment.example.json').read_text())
        config['inputs']['runtime']['ApiCpu'] = 'invalid'
        templates, contract = environment.load_contract(ROOT)
        with self.assertRaises(ValueError):
            environment.validate_config(config, templates, contract)

    def test_runtime_metadata_uses_real_application_environment_names(self):
        templates, _ = environment.load_contract(ROOT)
        value = templates['integration']['Resources']['RuntimeConfiguration']['Properties']['Value']['Fn::Sub']
        env = json.loads(value)
        self.assertEqual(env['IMAGE_STORAGE_DISK'], 's3')
        self.assertEqual(env['VERIFICATION_DOCUMENTS_DRIVER'], 's3')
        self.assertEqual(env['AWS_PUBLIC_IMAGES_BUCKET'], '${ImagesBucketName}')
        self.assertEqual(env['AWS_PRIVATE_FILES_BUCKET'], '${FilesBucketName}')
        self.assertEqual(env['IMAGE_BASE_URL'], '${ImageBaseUrl}')
        self.assertEqual(env['QUEUE_CONNECTION'], 'sqs')
        self.assertNotIn('IMAGE_BUCKET', env)
        self.assertNotIn('FILE_BUCKET', env)

    def test_missing_required_operator_input_is_rejected_offline(self):
        templates, contract = environment.load_contract(ROOT)
        for stack, name in [('runtime', 'ApiDomainName'), ('runtime', 'NotificationEmail'),
                            ('integration', 'BudgetAlertEmail')]:
            config = json.loads((ROOT / 'environment.example.json').read_text())
            del config['inputs'][stack][name]
            with self.subTest(name=name), self.assertRaisesRegex(ValueError, 'Missing operator input'):
                environment.validate_config(config, templates, contract)


if __name__ == '__main__':
    unittest.main()
