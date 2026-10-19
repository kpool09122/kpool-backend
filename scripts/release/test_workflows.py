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
    def test_settings_are_checked_before_plan_without_exposing_cloudflare_token(self):
        steps = workflow('release.yml')['jobs']['plan']['steps']
        settings = next(i for i, step in enumerate(steps)
                        if step.get('run') == 'python3 -B scripts/release/settings.py')
        plan = next(i for i, step in enumerate(steps) if step.get('id') == 'plan')
        self.assertLess(settings, plan)
        self.assertFalse(any(step.get('uses', '').startswith('actions/create-github-app-token@') for step in steps))
        for key in ('CLOUDFLARE_API_TOKEN',):
            self.assertEqual(steps[settings]['env']['HAS_' + key], "${{ secrets." + key + " != '' }}")
            self.assertNotIn(key, steps[settings]['env'])

    def test_production_guards_accept_release_tags_and_reject_branches(self):
        data = workflow('release.yml')
        inputs = data.get('on', data.get(True))['workflow_dispatch']['inputs']
        self.assertNotIn('backend_ref', inputs)
        self.assertEqual(inputs['frontend_ref']['default'], '')
        jobs = [data['jobs'][name] for name in ('plan', 'frontend-build', 'frontend-deploy')]
        for name in ('backend-validation.yml', 'backend-publication.yml', 'backend-deployment.yml'):
            jobs.extend(workflow(name)['jobs'].values())
        for job in jobs:
            guard = job['steps'][0]
            script = '\n'.join(guard['run'].splitlines()[1:-1])
            for ref in ('refs/tags/v1.0.0', 'refs/heads/main', 'refs/heads/release/v1.0', 'refs/heads/v1.0.0'):
                environment = dict(GITHUB_REPOSITORY='kpool09122/kpool-backend', GITHUB_REF=ref,
                                   CONTROL_SHA='a' * 40, EXPECTED_CONTROL_SHA='a' * 40)
                with self.subTest(job=job, ref=ref), patch.dict('os.environ', environment, clear=True):
                    if ref == 'refs/tags/v1.0.0':
                        exec(compile(script, '<tag-guard>', 'exec'), {})
                    else:
                        with self.assertRaises(ValueError):
                            exec(compile(script, '<tag-guard>', 'exec'), {})
        steps = data['jobs']['plan']['steps']
        ancestry = next(i for i, step in enumerate(steps) if 'verify_control' in step.get('run', ''))
        settings = next(i for i, step in enumerate(steps) if step.get('run') == 'python3 -B scripts/release/settings.py')
        self.assertLess(ancestry, settings)

    def test_validation_checks_pinned_identity_before_checkout_and_execution(self):
        steps = workflow('backend-validation.yml')['jobs']['validate-build']['steps']
        check_index = next(i for i, step in enumerate(steps) if 'resolve_source' in step.get('run', ''))
        source_index = next(i for i, step in enumerate(steps) if step.get('with', {}).get('path') == 'source')
        self.assertLess(check_index, source_index)
        for step in steps[:source_index]:
            self.assertNotEqual(step.get('working-directory'), 'source')
        control = next(step for step in steps if step.get('with', {}).get('path') == 'control')
        self.assertEqual(control['with']['ref'], '${{ github.workflow_sha }}')
        self.assertEqual(steps[source_index]['with']['ref'], 'main')
        self.assertEqual(steps[source_index]['with']['fetch-depth'], 0)
        select_index = next(i for i, step in enumerate(steps) if 'select-source.sh' in step.get('run', ''))
        self.assertLess(source_index, select_index)
        self.assertEqual(steps[select_index]['run'], 'bash control/scripts/release/select-source.sh source')
        validation_index = next(i for i, step in enumerate(steps) if 'task test' in step.get('run', ''))
        build_index = next(i for i, step in enumerate(steps) if 'docker build ' in step.get('run', ''))
        self.assertLess(select_index, validation_index)
        self.assertLess(validation_index, build_index)
        self.assertEqual(set(workflow('backend-validation.yml')['jobs']), {'validate-build'})
        script = '\n'.join(steps[check_index]['run'].splitlines()[1:-1])
        plan = fixture()[3]
        environment = {'SOURCE_SHA': plan['backend_sha'], 'CONTROL_SHA': plan['workflow_sha']}
        cases = [('pinned', plan['backend_sha'], None), ('mismatch', 'f'*40, ValueError)]
        for status, resolved_sha, error in cases:
            with self.subTest(status=status, resolved_sha=resolved_sha), patch.dict('os.environ', environment), \
                    patch('builtins.open', mock_open(read_data=json.dumps(plan))), \
                    patch('github_source.gh', side_effect=[{'sha': resolved_sha}, {'status': status}]) as api:
                if error:
                    with self.assertRaises(error): exec(compile(script, '<source-check>', 'exec'), {})
                else:
                    exec(compile(script, '<source-check>', 'exec'), {})
                    self.assertEqual(api.call_count, 1)
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
        self.assertEqual(jobs['publish']['needs'],['plan','validate-build','frontend-build'])
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

    def test_frontend_build_gates_publication_and_deploy_has_only_cloudflare_credentials(self):
        jobs = workflow('release.yml')['jobs']
        self.assertEqual(jobs['frontend-build']['needs'], 'plan')
        self.assertEqual(jobs['frontend-deploy']['needs'], ['plan', 'frontend-build', 'deploy'])
        self.assertIn("needs.frontend-build.result == 'success'", jobs['publish']['if'])
        self.assertIn("inputs.target == 'all' && needs.deploy.result == 'success'", jobs['frontend-deploy']['if'])
        self.assertIn("inputs.target == 'frontend' && needs.deploy.result == 'skipped'", jobs['frontend-deploy']['if'])
        self.assertIn("inputs.target != 'frontend'", jobs['deploy']['if'])
        self.assertIn("inputs.target != 'frontend'", jobs['publish']['if'])
        self.assertIn("inputs.target != 'frontend'", jobs['validate-build']['if'])
        for name in ('publish', 'deploy', 'frontend-deploy'):
            self.assertIn('!cancelled()', jobs[name]['if'])
        for name in ('frontend-build', 'frontend-deploy'):
            job = jobs[name]
            self.assertEqual(job['environment'], 'production')
            self.assertNotIn('id-token', job['permissions'])
            self.assertNotIn('AWS_', json.dumps(job))
            steps = job['steps']
            guard = next(i for i, step in enumerate(steps) if 'resolve_source' in step.get('run', ''))
            checkout = next(i for i, step in enumerate(steps) if step.get('with', {}).get('path') == 'source')
            install = next(i for i, step in enumerate(steps) if step.get('run') == 'pnpm install --frozen-lockfile')
            self.assertLess(guard, checkout)
            self.assertLess(checkout, install)
            self.assertFalse(steps[checkout]['with']['persist-credentials'])
            self.assertEqual(steps[checkout]['with']['ref'], 'main')
            self.assertEqual(steps[checkout]['with']['fetch-depth'], 0)
            self.assertNotIn('SOURCE_APP', json.dumps(job))
            self.assertTrue(any(step.get('env', {}).get('GH_TOKEN') == '${{ github.token }}' for step in steps))
        self.assertNotIn('CLOUDFLARE_API_TOKEN', json.dumps(jobs['frontend-build']))
        build = next(step['run'] for step in jobs['frontend-build']['steps']
                     if 'pnpm workers:build' in step.get('run', ''))
        self.assertLess(build.index('pnpm workers:build'), build.index('pnpm workers:smoke'))
        steps = jobs['frontend-deploy']['steps']
        self.assertFalse(any('workers:build' in step.get('run', '') for step in steps))
        self.assertTrue(any(step.get('run', '').endswith('frontend.py restore') for step in steps))
        observed = next(step for step in steps if step.get('run', '').endswith('frontend.py observe'))
        self.assertIn('always()', observed['if'])
