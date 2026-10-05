"""Fake collaborators only; exercising actual release orchestration."""
import tempfile
import unittest
from pathlib import Path
from records import Journal
from runner import execute

class FakeBackend:
    def __init__(self, fail=None): self.calls=[]; self.fail=fail
    def stage(self, name):
        self.calls.append(name)
        if self.fail == name: raise ValueError('fixture secret must not be recorded')
    def preflight(self): self.stage('preflight')
    def register(self): self.stage('register')
    def migration(self): self.stage('migration')
    def api(self): self.stage('api')
    def worker(self): self.stage('worker')
    def smoke(self): self.stage('smoke')
    def observe(self): self.calls.append('observe')

class OrchestrationTests(unittest.TestCase):
    def test_failure_stops_next_stage_and_always_observes(self):
        for failure in ('preflight','register','migration','api','worker','smoke',None):
            with self.subTest(failure=failure), tempfile.TemporaryDirectory() as directory:
                backend=FakeBackend(failure)
                journal=Journal(Path(directory), {'release_id':'fixture'})
                if failure:
                    with self.assertRaises(ValueError): execute(backend,journal,'deploy')
                    expected=['preflight','register','migration','api','worker','smoke']
                    self.assertEqual(backend.calls,expected[:expected.index(failure)+1]+['observe'])
                else:
                    execute(backend,journal,'deploy')
                    self.assertEqual(backend.calls,['preflight','register','migration','api','worker','smoke','observe'])
