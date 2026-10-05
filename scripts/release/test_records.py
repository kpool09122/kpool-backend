import json
import tempfile
import unittest
from pathlib import Path
from records import Journal

class RecordTests(unittest.TestCase):
    def test_partial_failure_retains_live_revision_and_redacts_errors(self):
        with tempfile.TemporaryDirectory() as directory:
            journal = Journal(Path(directory), {'release_id':'fixture-release'})
            journal.event('api', 'success', task_definition='fixture-api-new')
            journal.event('worker', 'failed', error_type='TimeoutError')
            journal.event('live', 'observed', api='fixture-api-new', worker='fixture-worker-old')
            result = json.loads((Path(directory)/'journal.json').read_text())
            self.assertEqual(result['events'][0]['task_definition'],'fixture-api-new')
            self.assertEqual(result['events'][-1]['worker'],'fixture-worker-old')
            self.assertEqual(len(list(Path(directory).glob('event-*.json'))),3)
