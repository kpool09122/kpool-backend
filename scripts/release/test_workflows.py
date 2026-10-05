import re
import unittest
from pathlib import Path
import yaml

BASE=Path(__file__).resolve().parents[2]/'.github/workflows'

def workflow(name): return yaml.safe_load((BASE/name).read_text())

class WorkflowTests(unittest.TestCase):
    def test_aws_reusables_validate_trusted_control_sha_before_checkout(self):
        for name in ('backend-validation.yml','backend-publication.yml','backend-deployment.yml'):
            data=workflow(name)
            for job in data['jobs'].values():
                first=job['steps'][0]
                self.assertIn('run',first)
                self.assertIn('github.workflow_sha',str(first))
                self.assertIn('CONTROL_SHA',first['run'])
    def test_release_order_replay_graph_and_permissions(self):
        data=workflow('release.yml'); jobs=data['jobs']
        self.assertFalse(data['concurrency']['cancel-in-progress'])
        self.assertEqual(jobs['publish']['needs'],['plan','validate-build'])
        self.assertEqual(jobs['deploy']['needs'],['plan','validate-build','publish'])
        self.assertIn("needs.publish.result == 'success'",jobs['deploy']['if'])
        self.assertIn("needs.publish.result == 'skipped'",jobs['deploy']['if'])
        self.assertEqual(jobs['record']['if'],'always()')
        for name in ('release.yml','backend-validation.yml','backend-publication.yml','backend-deployment.yml'):
            data=workflow(name)
            self.assertNotIn('id-token',data['permissions'])
            for job_name, job in data['jobs'].items():
                for step in job.get('steps',[]):
                    if 'uses' in step:
                        self.assertRegex(step['uses'],r'@[a-f0-9]{40}$')
                        if step['uses'].startswith('actions/checkout@'):
                            self.assertFalse(step['with']['persist-credentials'])
                    if 'run' in step:
                        self.assertNotRegex(step['run'],r'\$\{\{\s*(inputs|github.event.inputs)\.')
                if job.get('permissions',{}).get('id-token')=='write':
                    if 'uses' not in job: self.assertEqual(job['environment'],'production')
                self.assertNotEqual(job.get('secrets'),'inherit')
