import copy
import hashlib
import io
import json
import os
from pathlib import Path
import tarfile
import tempfile
import unittest
from unittest.mock import patch

import cli
from contract import BACKEND, FRONTEND
from frontend import (API_KEYS, build_record, configuration_digest, deployed_version, observe,
                      preflight, restore, validate_configuration)


def fixture():
    plan = dict(schema=1, repository=BACKEND, environment='production', target='all', mode='deploy',
                workflow_sha='c' * 40, frontend_sha='a' * 40, backend_sha='b' * 40,
                live_frontend_sha='d' * 40, release_id='42-1', initial=False)
    config = dict(paused=False, smoke_url='https://frontend.example.invalid/en',
                  compatibility=dict(approved=True, frontend_sha=plan['frontend_sha'],
                                     backend_sha=plan['backend_sha'], evidence='TEST ONLY compatibility review'),
                  wrangler=dict(name='fixture-frontend', account_id='e' * 32, main='.open-next/worker.js',
                                workers_dev=False, preview_urls=False,
                                assets=dict(directory='.open-next/assets', binding='ASSETS'),
                                vars={key: 'https://api.example.invalid' for key in API_KEYS},
                                r2_buckets=[dict(bucket_name='fixture-cache')],
                                routes=[dict(pattern='frontend.example.invalid', custom_domain=True)]))
    plan['frontend_configuration_sha256'] = configuration_digest(config)
    return plan, config


class FrontendReleaseTests(unittest.TestCase):
    def test_cloudflare_metadata_selects_current_deployment_and_rejects_weighted_versions(self):
        _, config = fixture()
        older = dict(id='old', created_on='2026-10-17T00:00:00Z', versions=[dict(percentage=100, version_id='old-version')])
        current = dict(id='new', created_on='2026-10-18T00:00:00Z', versions=[dict(percentage=100, version_id='new-version')])
        with patch('frontend.cloudflare', return_value={'deployments': [older, current]}):
            self.assertEqual(deployed_version(config), dict(deployment_id='new', version_id='new-version'))
        with patch('frontend.cloudflare', return_value={'deployments': []}):
            self.assertIsNone(deployed_version(config))
        current['versions'] = [dict(percentage=50, version_id='one'), dict(percentage=50, version_id='two')]
        with patch('frontend.cloudflare', return_value={'deployments': [current]}):
            with self.assertRaisesRegex(ValueError, 'stable version'): deployed_version(config)

    def test_configuration_must_pin_approved_pair_resources_and_nonsecret_values(self):
        plan, config = fixture()
        validate_configuration(config, plan)
        cases = []
        cases.append(dict(config, CLOUDFLARE_API_TOKEN='must remain in GitHub Secrets'))
        for key, value in [('paused', True), ('smoke_url', 'https://attacker.invalid/en')]:
            changed = copy.deepcopy(config); changed[key] = value; cases.append(changed)
        changed = copy.deepcopy(config); changed['compatibility']['frontend_sha'] = 'f' * 40; cases.append(changed)
        for key, value in [('main', '../worker.js'), ('account_id', 'unknown'), ('name', 'frontend-local'),
                           ('build', {'command': 'echo injected'}), ('workers_dev', True)]:
            changed = copy.deepcopy(config); changed['wrangler'][key] = value; cases.append(changed)
        changed = copy.deepcopy(config); changed['wrangler']['vars']['SECRET'] = 'never-build-me'; cases.append(changed)
        changed = copy.deepcopy(config); changed['smoke_url'] = 'https://frontend.example.invalid/changed'; cases.append(changed)
        for changed in cases:
            with self.assertRaises(ValueError): validate_configuration(changed, plan)

    def test_built_artifact_restores_same_files_and_rejects_other_plan_or_modified_archive(self):
        plan, config = fixture()
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary); source = root / 'source'; output = root / 'release'
            (source / '.open-next/assets').mkdir(parents=True)
            (source / '.open-next/worker.js').write_text('TEST ONLY worker')
            (source / '.open-next/.hidden').write_text('build metadata')
            (source / 'pnpm-lock.yaml').write_text('TEST ONLY lockfile')
            build_record(source, output, config, plan)
            destination = root / 'destination'; destination.mkdir()
            (destination / 'pnpm-lock.yaml').write_text('TEST ONLY lockfile')
            restore(destination, output, config, plan)
            self.assertEqual((destination / '.open-next/worker.js').read_text(), 'TEST ONLY worker')
            self.assertEqual((destination / '.open-next/.hidden').read_text(), 'build metadata')
            for key, value in [('release_id', '43-1'), ('workflow_sha', 'f' * 40)]:
                with self.assertRaisesRegex(ValueError, 'provenance'):
                    restore(destination, output, config, dict(plan, **{key: value}))
            with (output / 'frontend-build.tar.gz').open('ab') as stream: stream.write(b'changed')
            with self.assertRaisesRegex(ValueError, 'checksum'): restore(destination, output, config, plan)

    def test_archive_cannot_restore_paths_or_symlinks_outside_build(self):
        plan, config = fixture()
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary); source = root / 'source'; output = root / 'release'
            source.mkdir(); output.mkdir(); (source / 'pnpm-lock.yaml').write_text('lockfile')
            from frontend import identity
            for name, kind in (('../outside', tarfile.REGTYPE), ('.open-next/../../outside', tarfile.REGTYPE),
                               ('.open-next/link', tarfile.SYMTYPE), ('/tmp/outside', tarfile.REGTYPE)):
                archive = output / 'frontend-build.tar.gz'
                with tarfile.open(archive, 'w:gz') as target:
                    info = tarfile.TarInfo(name); info.type = kind; info.linkname = '/tmp/outside'
                    target.addfile(info)
                record = dict(identity(plan, config), archive_sha256=hashlib.sha256(archive.read_bytes()).hexdigest(),
                              lockfile_sha256=hashlib.sha256(b'lockfile').hexdigest())
                (output / 'frontend-build.json').write_text(json.dumps(record))
                with self.assertRaisesRegex(ValueError, 'Unsafe'): restore(source, output, config, plan)

    def test_preflight_requires_existing_resources_and_signing_secret(self):
        plan, config = fixture()
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            with patch('frontend.cloudflare', side_effect=[{'name': 'fixture-cache'}, []]):
                with self.assertRaisesRegex(ValueError, 'signing Secret'): preflight(directory, config, plan)
            self.assertFalse((directory / 'frontend-before.json').exists())
            with patch('frontend.cloudflare', side_effect=[{'name': 'fixture-cache'}, [{'name': 'WIKI_VISITOR_LOCATION_SECRET'}]]), \
                    patch('frontend.deployed_version', return_value=None):
                preflight(directory, config, plan)
            self.assertIsNone(json.loads((directory / 'frontend-before.json').read_text())['previous'])

    def test_observe_records_partial_failure_and_requires_new_version_and_https(self):
        plan, config = fixture()
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            previous = dict(deployment_id='before', version_id='old')
            current = dict(deployment_id='after', version_id='new')
            (directory / 'frontend-before.json').write_text(json.dumps({'previous': previous}))
            with patch.dict(os.environ, FRONTEND_DEPLOY_OUTCOME='failure'), \
                    patch('frontend.deployed_version', return_value=current), patch('frontend.urllib.request.urlopen') as http:
                with self.assertRaises(ValueError): observe(directory, config, plan)
                http.assert_not_called()
            record = json.loads((directory / 'frontend-deployment.json').read_text())
            self.assertEqual(record['status'], 'unverified')
            self.assertEqual(record['current'], current)
            self.assertEqual(record['previous'], previous)
            (directory / 'wrangler-output.jsonl').write_text(json.dumps(dict(
                type='deploy', version=1, worker_name=config['wrangler']['name'], version_id='new')) + '\n')
            response = io.BytesIO(b'frontend'); response.status = 200; response.geturl = lambda: config['smoke_url']
            with patch.dict(os.environ, FRONTEND_DEPLOY_OUTCOME='success'), \
                    patch('frontend.deployed_version', return_value=current), \
                    patch('frontend.urllib.request.urlopen', return_value=response):
                observe(directory, config, plan)
            self.assertEqual(json.loads((directory / 'frontend-deployment.json').read_text())['status'], 'success')
            with patch.dict(os.environ, FRONTEND_DEPLOY_OUTCOME='success'), \
                    patch('frontend.deployed_version', return_value=previous):
                with self.assertRaisesRegex(ValueError, 'No new'): observe(directory, config, plan)
            with patch.dict(os.environ, FRONTEND_DEPLOY_OUTCOME='success'), \
                    patch('frontend.deployed_version', return_value=dict(current, version_id='foreign')):
                with self.assertRaisesRegex(ValueError, 'differs'): observe(directory, config, plan)


class FrontendPlanTests(unittest.TestCase):
    def test_empty_backend_ref_deploys_tag_commit_even_if_main_has_advanced(self):
        plan, config = fixture()
        plan['backend_sha'] = plan['workflow_sha']
        config['compatibility']['backend_sha'] = plan['backend_sha']
        with tempfile.TemporaryDirectory() as temporary:
            environment = dict(GITHUB_REPOSITORY=BACKEND, GITHUB_REF='refs/tags/v1.0.0',
                               RELEASE_TARGET='all', RELEASE_MODE='deploy', CONTROL_SHA=plan['workflow_sha'],
                               LIVE_FRONTEND_SHA=plan['live_frontend_sha'], BACKEND_REF='', FRONTEND_REF='main',
                               GITHUB_RUN_ATTEMPT='1', GITHUB_RUN_ID='42', INITIAL_ACTIVATION='false',
                               GITHUB_SERVER_URL='https://github.com',
                               FRONTEND_RELEASE_CONFIGURATION=json.dumps(config),
                               GITHUB_OUTPUT=str(Path(temporary) / 'output'))
            def resolve(repository, ref):
                self.assertEqual(ref, plan['workflow_sha'] if repository == BACKEND else 'main')
                return plan['backend_sha'] if repository == BACKEND else plan['frontend_sha']
            with patch.dict(os.environ, environment, clear=True), patch.object(cli, 'ROOT', Path(temporary) / 'release'), \
                    patch('cli.verify_control'), patch('cli.resolve_source', side_effect=resolve):
                cli.plan_release()
            result = json.loads((Path(temporary) / 'release/plan.json').read_text())
            self.assertEqual(result['backend_sha'], plan['workflow_sha'])
            self.assertEqual(result['workflow_ref'], 'refs/tags/v1.0.0')

    def test_targets_resolve_once_and_frontend_only_uses_live_backend_without_aws(self):
        plan, config = fixture()
        for target in ('all', 'frontend'):
            with self.subTest(target=target), tempfile.TemporaryDirectory() as temporary:
                environment = dict(GITHUB_REPOSITORY=BACKEND, GITHUB_REF='refs/tags/v1.0.0',
                                   RELEASE_TARGET=target, RELEASE_MODE='deploy', CONTROL_SHA=plan['workflow_sha'],
                                   LIVE_FRONTEND_SHA=plan['live_frontend_sha'], LIVE_BACKEND_SHA=plan['backend_sha'],
                                   BACKEND_REF='main', FRONTEND_REF='main', GITHUB_RUN_ATTEMPT='1', GITHUB_RUN_ID='42',
                                   INITIAL_ACTIVATION='false', GITHUB_SERVER_URL='https://github.com',
                                   FRONTEND_RELEASE_CONFIGURATION=json.dumps(config),
                                   GITHUB_OUTPUT=str(Path(temporary) / 'output'))
                def resolve(repository, ref):
                    self.assertEqual(ref, 'main')
                    return plan['backend_sha'] if repository == BACKEND else plan['frontend_sha']
                with patch.dict(os.environ, environment, clear=True), patch.object(cli, 'ROOT', Path(temporary) / 'release'), \
                        patch.object(cli, 'resolve_source', side_effect=resolve) as sources, \
                        patch('cli.verify_control') as control, patch('cli.Aws') as aws:
                    cli.plan_release()
                result = json.loads((Path(temporary) / 'release/plan.json').read_text())
                self.assertEqual(result['frontend_sha'], plan['frontend_sha'])
                self.assertEqual(result['frontend_configuration_sha256'], configuration_digest(config))
                self.assertEqual(sources.call_count, 1 if target == 'frontend' else 2)
                control.assert_called_once_with('refs/tags/v1.0.0', plan['workflow_sha'])
                aws.assert_not_called()

