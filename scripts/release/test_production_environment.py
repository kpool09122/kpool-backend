import json
import os
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

from cli import configuration
from production_environment import load_production_environment


class ProductionEnvironmentTests(unittest.TestCase):
    def load_text(self, text):
        with tempfile.TemporaryDirectory() as temporary:
            path = Path(temporary) / '.env.prod'
            path.write_text(text)
            return load_production_environment(path)

    def test_plain_and_quoted_values_are_read_without_shell_execution(self):
        self.assertEqual(self.load_text(
            '# production\nAPP_ENV=production\nGOOGLE_CLIENT_ID="fixture-client" # comment\n'
            "GEMINI_MODEL='fixture model'\nLOG_LEVEL=\n"),
            {'APP_ENV': 'production', 'GOOGLE_CLIENT_ID': 'fixture-client',
             'GEMINI_MODEL': 'fixture model', 'LOG_LEVEL': ''})
        with patch('subprocess.run') as shell:
            self.assertEqual(self.load_text('GEMINI_MODEL="$(do-not-execute)"')['GEMINI_MODEL'],
                             '$(do-not-execute)')
            shell.assert_not_called()

    def test_secrets_duplicates_placeholders_and_malformed_lines_are_rejected_without_values(self):
        for text in ('GOOGLE_CLIENT_SECRET=private-value', 'UNKNOWN=private-value', 'APP_ENV=production\nAPP_ENV=private-value',
                     'GOOGLE_REDIRECT_URI=https://<backend-domain>/private-value',
                     'GOOGLE_CLIENT_ID=${private-value}', 'source private-value',
                     'GEMINI_MODEL="private-value', 'GEMINI_MODEL=private-value with spaces'):
            with self.subTest(key=text.split('=')[0]), self.assertRaises(ValueError) as error:
                self.load_text(text)
            self.assertNotIn('private-value', str(error.exception))

    def test_release_overrides_win_without_losing_committed_defaults_or_secret_references(self):
        config = {'region': 'ap-northeast-1', 'runtime_environment': {'LOG_LEVEL': 'warning'},
                  'app_secret_keys': ['GOOGLE_CLIENT_SECRET']}
        with patch.dict(os.environ, RELEASE_CONFIGURATION=json.dumps(config)), \
                patch('cli.load_production_environment', return_value={
                    'LOG_LEVEL': 'info', 'GOOGLE_CLIENT_ID': 'fixture-client', 'GEMINI_MODEL': 'fixture-model'}):
            actual = configuration()
        self.assertEqual(actual['runtime_environment'], {
            'LOG_LEVEL': 'warning', 'GOOGLE_CLIENT_ID': 'fixture-client', 'GEMINI_MODEL': 'fixture-model'})
        self.assertEqual(actual['app_secret_keys'], ['GOOGLE_CLIENT_SECRET'])

    def test_committed_production_file_is_complete_and_compatible_with_live_services(self):
        values = load_production_environment(Path(__file__).resolve().parents[2] / '.env.prod')
        self.assertEqual(values['REDIS_CACHE_DB'], '0')
        self.assertEqual(values['MAIL_MAILER'], 'ses')
        self.assertTrue(values['SENTRY_LARAVEL_DSN'].startswith('https://'))
        for provider in ('GOOGLE', 'LINE', 'KAKAO'):
            self.assertTrue(values[provider + '_CLIENT_ID'])
            self.assertTrue(values[provider + '_REDIRECT_URI'].startswith('https://api.k-pool.app/'))
