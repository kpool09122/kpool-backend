import io
import json
from pathlib import Path
import tempfile
import unittest
import warnings
import zipfile
from unittest.mock import patch
from contract import BACKEND
from github_source import recorded_artifacts, resolve_source
from cli import plan_release, validate_plan, validate_manifest
from fixtures import fixture, FakeAws

class SourceAndRecordTests(unittest.TestCase):
    def read_record_zip(self, archive):
        run={'repository':{'full_name':BACKEND},'event':'workflow_dispatch','head_branch':'main','workflow_id':1,'status':'completed'}
        artifacts=[{'id':1,'name':'backend-record-1','expired':False}]
        with patch('github_source.gh',side_effect=[run,{'path':'.github/workflows/release.yml'},{'artifacts':artifacts},archive]):
            return recorded_artifacts('42')[2]

    def record_zip(self, entries):
        buffer=io.BytesIO()
        with warnings.catch_warnings(),zipfile.ZipFile(buffer,'w') as archive:
            warnings.simplefilter('ignore',UserWarning)
            for name,value in entries: archive.writestr(name,value)
        return buffer.getvalue()

    def test_record_zip_matches_release_uploader_tree(self):
        with tempfile.TemporaryDirectory() as directory:
            release=Path(directory)/'release'; records=release/'records'; records.mkdir(parents=True)
            for name in ('journal.json','deployment-manifest.json','manifest.json'):
                (records/name).write_text(json.dumps({'name':name}))
            (release/'failure.json').write_text('{}')
            buffer=io.BytesIO()
            with zipfile.ZipFile(buffer,'w') as archive:
                for path in [*records.glob('*.json'),release/'failure.json']:
                    archive.write(path,path.relative_to(release))
            self.assertEqual(self.read_record_zip(buffer.getvalue()),
                             {name:{'name':name} for name in ('journal.json','deployment-manifest.json','manifest.json')})

    def test_record_zip_supports_legacy_root_and_ignores_untrusted_paths(self):
        archive=self.record_zip([(name,'{}') for name in ('journal.json','../manifest.json','records/../manifest.json','/manifest.json','other/manifest.json','records/nested/manifest.json')])
        self.assertEqual(self.read_record_zip(archive),{'journal.json':{}})

    def test_record_zip_rejects_duplicate_names_and_prefix_collisions(self):
        for names in (('journal.json','journal.json'),('records/journal.json','records/journal.json'),('journal.json','records/journal.json')):
            with self.subTest(names=names),self.assertRaisesRegex(ValueError,'Duplicate'):
                self.read_record_zip(self.record_zip([(name,'{}') for name in names]))

    def test_record_zip_rejects_oversized_files_and_archives(self):
        for name in ('journal.json','records/journal.json'):
            with self.subTest(name=name),self.assertRaisesRegex(ValueError,'Record file is too large'):
                self.read_record_zip(self.record_zip([(name,' '*(1024*1024+1))]))
        with self.assertRaisesRegex(ValueError,'Record archive is too large'):
            self.read_record_zip(self.record_zip([('ignored.txt','x'*(10*1024*1024))]))

    def test_source_is_frozen_and_must_be_reachable_from_main(self):
        sha='a'*40
        with patch('github_source.gh',side_effect=[{'sha':sha},{'status':'ahead'}]) as api:
            self.assertEqual(resolve_source(BACKEND,'main'),sha)
            self.assertEqual(api.call_args_list[1].args[0],f'repos/{BACKEND}/compare/{sha}...main')
        with patch('github_source.gh',side_effect=[{'sha':sha},{'status':'diverged'}]):
            with self.assertRaises(ValueError): resolve_source(BACKEND,sha)
    def test_injection_ref_never_calls_github(self):
        with patch('github_source.gh') as api:
            for ref in ('feature/test','main;echo secret','$(id)','a'*39):
                with self.assertRaises(ValueError): resolve_source(BACKEND,ref)
            api.assert_not_called()
    def test_record_selection_is_exact_attempt_never_latest_attempt(self):
        run={'repository':{'full_name':BACKEND},'event':'workflow_dispatch','head_branch':'main','workflow_id':1,'status':'completed'}
        artifacts=[{'id':1,'name':'release-plan-1','expired':False},{'id':2,'name':'release-plan-2','expired':False}]
        buffer=io.BytesIO()
        with zipfile.ZipFile(buffer,'w') as archive: archive.writestr('plan.json',json.dumps({'fixture_attempt':1}))
        with patch('github_source.gh',side_effect=[run,{'path':'.github/workflows/release.yml'},{'artifacts':artifacts},buffer.getvalue()]) as api:
            plan,image,records=recorded_artifacts('42',attempt=1)
            self.assertEqual(plan['plan.json']['fixture_attempt'],1)
            self.assertIn('/artifacts/1/zip',api.call_args_list[-1].args[0])
    def test_foreign_workflow_run_is_rejected(self):
        run={'repository':{'full_name':BACKEND},'event':'pull_request','head_branch':'main','workflow_id':1,'status':'completed'}
        with patch('github_source.gh',side_effect=[run,{'path':'.github/workflows/release.yml'}]):
            with self.assertRaises(ValueError): recorded_artifacts('42')
    def test_manifest_environment_owner_sha_digest_and_release_id_are_validated(self):
        _,_,_,plan,manifest=fixture()
        validate_plan(plan); validate_manifest(manifest,plan)
        for key,value in [('environment','staging'),('repository','attacker/backend'),('backend_sha','b'*40),('image_digest','latest'),('release_id','43-1')]:
            with self.subTest(key=key),self.assertRaises(ValueError): validate_manifest(dict(manifest,**{key:value}),plan)
    def test_replay_orchestration_never_repeats_migration(self):
        from test_runner import FakeBackend
        from records import Journal
        from runner import execute
        from pathlib import Path
        import tempfile
        for mode in ('redeploy','resume-worker','rollback-api','rollback-worker'):
            with self.subTest(mode=mode),tempfile.TemporaryDirectory() as directory:
                backend=FakeBackend(); execute(backend,Journal(Path(directory),{'release_id':'fixture'}),mode)
                self.assertNotIn('migration',backend.calls)
                if mode=='resume-worker': self.assertNotIn('api',backend.calls)
                if mode.startswith('rollback'): self.assertNotIn('register',backend.calls)

class ReplayPlanTests(unittest.TestCase):
    def setUp(self):
        self.directory=tempfile.TemporaryDirectory(); self.addCleanup(self.directory.cleanup)
        self.root=Path(self.directory.name)/'release'
        self.o,self.runtime_env,self.config,self.source,self.manifest=fixture()
        self.previous={'repository':BACKEND,'environment':'production','release_id':self.source['release_id'],'events':[]}
        self.environment={'GITHUB_REPOSITORY':BACKEND,'GITHUB_REF':'refs/heads/main',
                          'RELEASE_TARGET':'backend','RELEASE_MODE':'redeploy','CONTROL_SHA':'e'*40,
                          'SOURCE_RUN':'42','GITHUB_RUN_ATTEMPT':'1','GITHUB_RUN_ID':'43',
                          'INITIAL_ACTIVATION':'false','GITHUB_SERVER_URL':'https://github.com',
                          'LIVE_FRONTEND_SHA':'f'*40,'GITHUB_OUTPUT':str(Path(self.directory.name)/'output')}

    def create_plan(self, record=None, image=None):
        if record is None: record={'deployment-manifest.json':self.manifest,'journal.json':self.previous}
        with patch('cli.ROOT',self.root),patch.dict('os.environ',self.environment,clear=True),patch('cli.recorded_artifacts',return_value=({'plan.json':self.source},image or {},record)):
            plan_release()
        return json.loads((self.root/'plan.json').read_text())

    def test_replay_uses_current_frontend_sha_and_preserves_source_digest(self):
        for mode in ('redeploy','resume-worker','rollback-api','rollback-worker','deploy'):
            with self.subTest(mode=mode):
                self.root=Path(self.directory.name)/mode
                self.environment['RELEASE_MODE']=mode
                if mode=='deploy':
                    self.environment.update(GITHUB_RUN_ATTEMPT='2',GITHUB_RUN_ID='42',SOURCE_RUN='')
                plan=self.create_plan()
                self.assertEqual(plan['live_frontend_sha'],'f'*40)
                self.assertEqual(plan['backend_sha'],self.source['backend_sha'])
                self.assertEqual(json.loads((self.root/'manifest.json').read_text()),self.manifest)
                if mode=='deploy': self.assertEqual(plan['mode'],'redeploy')

    def test_replay_requires_valid_current_frontend_sha(self):
        for index,value in enumerate((None,'','f'*39,'F'*40,'../historical')):
            with self.subTest(value=value):
                self.root=Path(self.directory.name)/str(index)
                if value is None: self.environment.pop('LIVE_FRONTEND_SHA',None)
                else: self.environment['LIVE_FRONTEND_SHA']=value
                with self.assertRaises(ValueError): self.create_plan()

    def test_replay_compatibility_gate_requires_current_frontend_approval(self):
        from aws_backend import Backend
        from records import Journal
        plan=self.create_plan()
        aws=FakeAws(self.o,self.runtime_env)
        self.previous['events']=[{'stage':'migration','status':'success'}]
        backend=Backend(aws,plan,self.manifest,Journal(Path(self.directory.name)/'records',plan),self.config,self.previous)
        with self.assertRaisesRegex(ValueError,'compatibility'):
            backend.preflight()
        self.assertFalse(any(op in ('register-task-definition','run-task','update-service') for _,op,_ in aws.calls))
        self.config['compatibility']['live_frontend_sha']='f'*40
        backend.preflight()

    def test_replay_from_resume_and_rollback_record_manifest_without_publication(self):
        for source_mode in ('resume-worker','rollback-api','rollback-worker'):
            with self.subTest(source_mode=source_mode):
                self.root=Path(self.directory.name)/source_mode
                self.source.update(mode=source_mode,release_id='43-1',source_release_id='42-1')
                self.previous['release_id']='43-1'
                self.environment.update(SOURCE_RUN='43',GITHUB_RUN_ID='44')
                plan=self.create_plan({'manifest.json':self.manifest,'journal.json':self.previous})
                self.assertEqual(plan['source_release_id'],'42-1')
                self.assertEqual(json.loads((self.root/'manifest.json').read_text()),self.manifest)
                self.assertEqual(json.loads((self.root/'previous.json').read_text()),self.previous)

    def test_replay_manifest_precedence_and_legacy_publication_fallback(self):
        deployment=dict(self.manifest,task_definitions={'Api':'recorded-api:7'})
        cases=[({'deployment-manifest.json':deployment,'manifest.json':self.manifest},deployment),
               ({'manifest.json':self.manifest},self.manifest),({},deployment)]
        for index,(record,expected) in enumerate(cases):
            with self.subTest(index=index):
                self.root=Path(self.directory.name)/str(index)
                self.create_plan(dict(record,**{'journal.json':self.previous}),{'manifest.json':deployment})
                self.assertEqual(json.loads((self.root/'manifest.json').read_text()),expected)

    def test_replay_rejects_invalid_record_manifest_even_with_valid_publication(self):
        for index,(key,value) in enumerate((('environment','staging'),('repository','foreign/backend'),('backend_sha','b'*40),('release_id','99-1'),('image_digest','latest'))):
            with self.subTest(key=key):
                self.root=Path(self.directory.name)/str(index)
                record={'manifest.json':dict(self.manifest,**{key:value}),'journal.json':self.previous}
                with self.assertRaisesRegex(ValueError,'Image record'):
                    self.create_plan(record,{'manifest.json':self.manifest})
