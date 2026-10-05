import json
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch
from aws_backend import Backend
from deployment import poll
from records import Journal
from runner import execute
from fixtures import fixture, FakeAws

# Time and HTTP are external boundaries. Fixture AWS validates every request using real botocore shapes.
def fast_poll(check,timeout=1,**kwargs):
    clock=iter(range(30))
    return poll(check,timeout=5,interval=0,clock=lambda:next(clock),sleep=lambda _:None)

class HttpResponse:
    status=200
    def __enter__(self): return self
    def __exit__(self,*args): pass
    def geturl(self): return 'https://api.example.invalid/up'
    def read(self,size): return b'healthy fixture'

class BackendTests(unittest.TestCase):
    def setUp(self):
        self.directory=tempfile.TemporaryDirectory(); self.addCleanup(self.directory.cleanup)
        self.o,self.env,self.config,self.plan,self.manifest=fixture()
        self.aws=FakeAws(self.o,self.env)
        self.journal=Journal(Path(self.directory.name),self.plan)
        self.backend=Backend(self.aws,self.plan,self.manifest,self.journal,self.config)
        self.poll=patch('aws_backend.poll',fast_poll); self.poll.start(); self.addCleanup(self.poll.stop)
        self.http=patch('aws_backend.urllib.request.urlopen',return_value=HttpResponse()); self.http.start(); self.addCleanup(self.http.stop)
    def run_release(self): execute(self.backend,self.journal,'deploy')
    def test_full_release_uses_one_digest_distinct_roles_and_native_deployment(self):
        self.run_release()
        definitions=[a for _,op,a in self.aws.calls if op=='register-task-definition']
        self.assertEqual(len(definitions),3)
        self.assertEqual({d['containerDefinitions'][0]['image'] for d in definitions},{self.o['RepositoryUri']+'@'+self.manifest['image_digest']})
        self.assertEqual(len({d['taskRoleArn'] for d in definitions}),3)
        operations=[op for _,op,_ in self.aws.calls]
        self.assertLess(operations.index('run-task'),operations.index('update-service'))
        self.assertIn('describe-service-deployments',operations)
        self.assertIn('describe-service-revisions',operations)
        record=json.loads((Path(self.directory.name)/'journal.json').read_text())
        self.assertNotIn('fixture-never-log',json.dumps(record))
        self.assertNotIn('fixture-never-log',''.join(p.read_text() for p in Path(self.directory.name).glob('*.json')))
    def test_migration_nonzero_blocks_api(self):
        self.aws.migration_exit=1
        with self.assertRaises(ValueError): self.run_release()
        self.assertFalse(any(op=='update-service' for _,op,_ in self.aws.calls))
    def test_missing_migration_exit_blocks_api(self):
        self.aws.migration_exit=None
        with self.assertRaises(ValueError): self.run_release()
        self.assertFalse(any(op=='update-service' for _,op,_ in self.aws.calls))
    def test_migration_timeout_blocks_api(self):
        self.aws.migration_status='RUNNING'
        with self.assertRaises(TimeoutError): self.run_release()
        self.assertFalse(any(op=='update-service' for _,op,_ in self.aws.calls))
    def test_launch_transport_failure_blocks_api(self):
        self.aws.failure='run-task'
        with self.assertRaises(RuntimeError): self.run_release()
        self.assertFalse(any(op=='update-service' for _,op,_ in self.aws.calls))
    def test_pretraffic_failure_blocks_worker(self):
        self.aws.api_status='ROLLBACK_REQUESTED'
        with self.assertRaises(ValueError): self.run_release()
        self.assertEqual(len([op for _,op,_ in self.aws.calls if op=='update-service']),1)
    def test_bake_rollback_blocks_worker(self):
        self.aws.api_status='ROLLBACK_SUCCESSFUL'
        with self.assertRaises(ValueError): self.run_release()
        self.assertEqual(len([op for _,op,_ in self.aws.calls if op=='update-service']),1)
    def test_api_timeout_blocks_worker(self):
        self.aws.api_status='IN_PROGRESS'
        with self.assertRaises(TimeoutError): self.run_release()
        self.assertEqual(len([op for _,op,_ in self.aws.calls if op=='update-service']),1)
    def test_wrong_api_revision_blocks_worker(self):
        self.aws.wrong_revision=True
        with self.assertRaises(ValueError): self.run_release()
        self.assertEqual(len([op for _,op,_ in self.aws.calls if op=='update-service']),1)
    def test_worker_failure_records_partial_success(self):
        self.aws.worker_failed=True
        with self.assertRaises(ValueError): self.run_release()
        events=self.journal.value['events']
        self.assertTrue(any(e['stage']=='api' and e['status']=='success' for e in events))
        self.assertTrue(any(e['stage']=='live' and e.get('api')=='fixture-Api-release:7' for e in events))
        self.assertFalse(any(e['stage']=='smoke' for e in events))
    def test_interrupted_api_response_still_records_live_and_intended_revision(self):
        self.aws.failure='update-service'
        with self.assertRaises(RuntimeError): self.run_release()
        self.assertTrue(any(e['stage']=='api' and e['status']=='requesting' for e in self.journal.value['events']))
        self.assertTrue(any(e['stage']=='live' and e['status']=='observed' for e in self.journal.value['events']))
    def test_initial_zero_to_one_is_explicit(self):
        self.plan['initial']=True
        for s in self.aws.services.values(): s.update(desiredCount=0,runningCount=0)
        self.run_release()
        self.assertEqual([a['desiredCount'] for _,op,a in self.aws.calls if op=='update-service'],[1,1])
    def test_normal_release_preserves_desired(self):
        self.run_release()
        self.assertEqual([a['desiredCount'] for _,op,a in self.aws.calls if op=='update-service'],[2,2])
    def test_zero_without_explicit_initial_fails_before_mutation(self):
        for s in self.aws.services.values(): s.update(desiredCount=0,runningCount=0)
        with self.assertRaises(ValueError): self.run_release()
        self.assertFalse(any(op=='register-task-definition' for _,op,_ in self.aws.calls))
    def test_preflight_failure_matrix_has_no_mutation(self):
        cases=[('account','wrong'),('stack_status','UPDATE_IN_PROGRESS'),('alarm_state','INSUFFICIENT_DATA'),('hook_state','Pending'),('ssm_type','SecureString')]
        for key,value in cases:
            with self.subTest(key=key):
                aws=FakeAws(self.o,self.env); setattr(aws,key,value)
                backend=Backend(aws,self.plan,self.manifest,self.journal,self.config)
                with self.assertRaises(ValueError): backend.preflight()
                self.assertFalse(any(op in ('register-task-definition','run-task','update-service') for _,op,_ in aws.calls))
    def test_secret_key_in_ssm_is_rejected_without_recording_value(self):
        self.aws.environment['DB_PASSWORD']='fixture-secret-value'
        with self.assertRaises(ValueError): self.run_release()
        self.assertNotIn('fixture-secret-value',json.dumps(self.journal.value))
    def test_recovery_missing_required_revisions_fails_in_preflight_without_mutation(self):
        cases = [('rollback-api', {}), ('rollback-api', {'Worker': 'unused-worker:7'}),
                 ('rollback-worker', {}), ('rollback-worker', {'Api': 'unused-api:7'}),
                 ('resume-worker', {}), ('resume-worker', {'Api': 'unused-api:7'}),
                 ('resume-worker', {'Worker': 'unused-worker:7'})]
        for mode, tasks in cases:
            with self.subTest(mode=mode, tasks=tasks), tempfile.TemporaryDirectory() as directory:
                plan = dict(self.plan, mode=mode, source_run='41')
                previous = {'events': [{'stage': stage, 'status': 'success'} for stage in ('migration', 'api')]}
                aws = FakeAws(self.o, self.env)
                journal = Journal(Path(directory), plan)
                backend = Backend(aws, plan, dict(self.manifest, task_definitions=tasks), journal, self.config, previous)
                with self.assertRaisesRegex(ValueError, 'Recorded task revisions are required'):
                    execute(backend, journal, mode)
                self.assertFalse(any(op in ('register-task-definition', 'run-task', 'update-service') for _, op, _ in aws.calls))
                events = journal.value['events']
                self.assertTrue(any(e['stage']=='preflight' and e['status']=='failed' and e['error_type']=='ValueError' for e in events))
                self.assertFalse(any(e['stage']=='preflight' and e['status']=='success' for e in events))
                self.assertFalse(any(e['stage']=='migration' and e['status']=='inherited-success' for e in events))
    def test_recovery_accepts_only_mode_required_owned_revisions(self):
        for mode, kinds in [('rollback-api', ('Api',)), ('rollback-worker', ('Worker',)),
                            ('resume-worker', ('Api', 'Worker'))]:
            with self.subTest(mode=mode):
                plan = dict(self.plan, mode=mode, source_run='41')
                tasks = {kind: self.o[kind+'ReleaseFamily']+':7' for kind in kinds}
                definitions = {arn: {'family': self.o[kind+'ReleaseFamily'],
                                    'taskRoleArn': self.o[kind+'TaskRoleArn'],
                                    'executionRoleArn': self.o['AppExecutionRoleArn'],
                                    'containerDefinitions': [{'image': self.o['RepositoryUri']+'@'+self.manifest['image_digest']}]}
                               for kind, arn in tasks.items()}
                aws = FakeAws(self.o, self.env)
                if mode == 'resume-worker': aws.services['Api']['taskDefinition'] = tasks['Api']
                def recorded_aws(aws_service, operation, **arguments):
                    if operation == 'describe-task-definition' and arguments['taskDefinition'] in definitions:
                        return {'taskDefinition': definitions[arguments['taskDefinition']]}
                    return aws(aws_service, operation, **arguments)
                previous = {'events': [{'stage': stage, 'status': 'success'} for stage in ('migration', 'api')]}
                backend = Backend(recorded_aws, plan, dict(self.manifest, task_definitions=tasks), self.journal, self.config, previous)
                backend.preflight()
                for definition in definitions.values(): definition['taskRoleArn'] = 'foreign-role'
                with self.assertRaisesRegex(ValueError, 'not owned'):
                    backend.preflight()
    def test_compatibility_is_required_before_any_mutation(self):
        self.config['compatibility']['approved']=False
        with self.assertRaises(ValueError): self.run_release()
        self.assertFalse(any(op=='register-task-definition' for _,op,_ in self.aws.calls))
    def test_role_swap_fails_before_any_mutation(self):
        self.aws.template_overrides['taskRoleArn']='fixture-migration-role-swapped'
        with self.assertRaises(ValueError): self.run_release()
        self.assertFalse(any(op=='register-task-definition' for _,op,_ in self.aws.calls))
    def test_sqs_probe_does_not_claim_processing(self):
        self.run_release()
        events=self.journal.value['events']
        probe=next(e for e in events if 'queue_processing' in e)
        self.assertIn('NOT_VERIFIED',probe['queue_processing'])
