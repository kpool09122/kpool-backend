import json
import re
import unittest
from pathlib import Path
from unittest.mock import mock_open, patch
import yaml
from fixtures import fixture

BASE=Path(__file__).resolve().parents[2]/'.github/workflows'

def workflow(name): return yaml.safe_load((BASE/name).read_text())

class WorkflowTests(unittest.TestCase):
    def test_validation_checks_main_ancestry_before_checkout_and_execution(self):
        steps = workflow('backend-validation.yml')['jobs']['validate-build']['steps']
        check_index = next(i for i, step in enumerate(steps) if 'resolve_source' in step.get('run', ''))
        source_index = next(i for i, step in enumerate(steps) if step.get('with', {}).get('path') == 'source')
        self.assertLess(check_index, source_index)
        for step in steps[:source_index]:
            self.assertNotEqual(step.get('working-directory'), 'source')
        control = next(step for step in steps if step.get('with', {}).get('path') == 'control')
        self.assertEqual(control['with']['ref'], '${{ github.workflow_sha }}')
        script = '\n'.join(steps[check_index]['run'].splitlines()[1:-1])
        plan = fixture()[3]
        environment = {'SOURCE_SHA': plan['backend_sha'], 'CONTROL_SHA': plan['workflow_sha']}
        cases = [('ahead', plan['backend_sha'], None), ('identical', plan['backend_sha'], None),
                 ('behind', plan['backend_sha'], ValueError), ('diverged', plan['backend_sha'], ValueError),
                 ('ahead', 'f'*40, ValueError)]
        for status, resolved_sha, error in cases:
            with self.subTest(status=status, resolved_sha=resolved_sha), patch.dict('os.environ', environment), \
                    patch('builtins.open', mock_open(read_data=json.dumps(plan))), \
                    patch('github_source.gh', side_effect=[{'sha': resolved_sha}, {'status': status}]) as api:
                if error:
                    with self.assertRaises(error): exec(compile(script, '<source-check>', 'exec'), {})
                else:
                    exec(compile(script, '<source-check>', 'exec'), {})
                    self.assertEqual(api.call_count, 2)
        with patch.dict('os.environ', environment), patch('builtins.open', mock_open(read_data=json.dumps(plan))), \
                patch('github_source.gh', side_effect=RuntimeError('GitHub read failed')):
            with self.assertRaises(RuntimeError): exec(compile(script, '<source-check>', 'exec'), {})
        with patch.dict('os.environ', dict(environment, SOURCE_SHA='f'*40)), \
                patch('builtins.open', mock_open(read_data=json.dumps(plan))), patch('github_source.gh') as api:
            with self.assertRaisesRegex(ValueError, 'immutable plan'):
                exec(compile(script, '<source-check>', 'exec'), {})
            api.assert_not_called()
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
