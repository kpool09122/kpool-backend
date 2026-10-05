"""AWS calls are faked here; these tests do not claim a real cloud deployment."""
import copy
import json
from pathlib import Path
import tempfile
import unittest

import operations
from test_runtime_state import fixture

ROOT = Path(__file__).resolve().parents[2] / 'infra/cloudformation'


class FakeAws:
    def __init__(self):
        self.calls = []
        self.stacks = {}
        self.account = '123456789012'
        self.after_create = lambda: None
        self.after_wait = lambda: None
        self.runtime_data = None
        self.change = {'Id': 'arn:aws:cloudformation:ap-northeast-1:123456789012:changeSet/test/id',
                       'Status': 'CREATE_COMPLETE', 'ExecutionStatus': 'AVAILABLE', 'Changes': []}

    def __call__(self, service, operation, *args):
        self.calls.append((service, operation, args))
        if operation == 'get-caller-identity':
            return {'Account': self.account}
        if operation == 'describe-stacks':
            name = args[args.index('--stack-name') + 1]
            if name not in self.stacks:
                raise operations.AwsError(service, operation, f'ValidationError: Stack with id {name} does not exist')
            return {'Stacks': [copy.deepcopy(self.stacks[name])]}
        if operation == 'create-change-set':
            name = args[args.index('--stack-name') + 1]
            self.change['StackId'] = f'arn:aws:cloudformation:ap-northeast-1:123456789012:stack/{name}/id'
            self.change['Parameters'] = json.loads(args[args.index('--parameters') + 1])
            if name not in self.stacks:
                self.stacks[name] = {'StackStatus': 'REVIEW_IN_PROGRESS', 'StackId': self.change['StackId']}
            self.after_create()
            return {'Id': self.change['Id']}
        if operation == 'describe-change-set':
            return copy.deepcopy(self.change)
        if operation == 'wait' and args[0] in {'stack-create-complete', 'stack-update-complete'}:
            name = args[args.index('--stack-name') + 1]
            self.stacks[name]['StackStatus'] = 'CREATE_COMPLETE'
            self.after_wait()
        if self.runtime_data:
            _, services, rules, schedule = self.runtime_data
            if operation == 'describe-services':
                return copy.deepcopy(services)
            if operation == 'list-service-deployments':
                return {'serviceDeployments': []}
            if operation == 'describe-rules':
                return copy.deepcopy(rules)
            if operation == 'get-schedule':
                return copy.deepcopy(schedule)
        return {}


class OperationsTests(unittest.TestCase):
    def setUp(self):
        self.config = json.loads((ROOT / 'environment.example.json').read_text())
        self.aws = FakeAws()

    def test_plan_then_execute_uses_exact_reviewed_changeset(self):
        with tempfile.TemporaryDirectory() as directory:
            plan = operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
            operations.execute(self.aws, plan, ROOT, True)
        writes = [call for call in self.aws.calls if call[1] == 'execute-change-set']
        self.assertEqual(len(writes), 1)
        self.assertIn(plan['changeSetArn'], writes[0][2])

    def test_wrong_account_cannot_create_changeset(self):
        self.aws.account = '999999999999'
        with tempfile.TemporaryDirectory() as directory, self.assertRaisesRegex(ValueError, 'account mismatch'):
            operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
        self.assertFalse(any(call[1] == 'create-change-set' for call in self.aws.calls))

    def test_access_denied_is_not_treated_as_missing_stack(self):
        def denied(*args):
            raise operations.AwsError('cloudformation', 'describe-stacks', 'AccessDenied: not authorized')
        with self.assertRaises(operations.AwsError):
            operations.read_stack(denied, 'stack')

    def test_stack_change_after_review_cannot_execute(self):
        with tempfile.TemporaryDirectory() as directory:
            plan = operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
            self.aws.stacks[self.config['stacks']['bootstrap']] = {'StackStatus': 'CREATE_COMPLETE'}
            with self.assertRaisesRegex(ValueError, 'Stale plan'):
                operations.execute(self.aws, plan, ROOT, True)
        self.assertFalse(any(call[1] == 'execute-change-set' for call in self.aws.calls))

    def test_replacement_or_deletion_cannot_execute(self):
        for resource in ({'Action': 'Remove'}, {'Action': 'Modify', 'Replacement': 'True'},
                         {'Action': 'Modify', 'Replacement': 'Conditional'}):
            with self.subTest(resource=resource), tempfile.TemporaryDirectory() as directory:
                self.aws = FakeAws()
                plan = operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
                self.aws.change['Changes'] = [{'ResourceChange': resource}]
                with self.assertRaisesRegex(ValueError, 'migration plan'):
                    operations.execute(self.aws, plan, ROOT, True)
                self.aws.change['Changes'] = []
        self.assertFalse(any(call[1] == 'execute-change-set' for call in self.aws.calls))

    def test_different_stack_changeset_cannot_execute_even_with_identical_parameters(self):
        with tempfile.TemporaryDirectory() as directory:
            plan = operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
            self.aws.change['StackId'] = 'arn:aws:cloudformation:ap-northeast-1:123456789012:stack/other/id'
            with self.assertRaisesRegex(ValueError, 'stack identity'):
                operations.execute(self.aws, plan, ROOT, True)

    def test_plan_preserves_custom_foundation_queue_for_bootstrap_permissions(self):
        name = self.config['stacks']['root']
        self.aws.stacks[name] = {'StackStatus': 'CREATE_COMPLETE', 'Parameters': [
            {'ParameterKey': 'WorkQueueName', 'ParameterValue': 'custom-work-v9'}]}
        with tempfile.TemporaryDirectory() as directory:
            plan = operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
        values = {item['ParameterKey']: item['ParameterValue'] for item in plan['parameters']}
        self.assertEqual(values['FoundationWorkQueueName'], 'custom-work-v9')

    def test_target_stack_changed_during_planning_is_not_adopted_as_reviewed_state(self):
        name = self.config['stacks']['bootstrap']
        self.aws.stacks[name] = {'StackStatus': 'UPDATE_COMPLETE', 'LastUpdatedTime': 'before'}
        self.aws.after_create = lambda: self.aws.stacks[name].update(LastUpdatedTime='concurrent-update')
        with tempfile.TemporaryDirectory() as directory, self.assertRaisesRegex(ValueError, 'Target stack changed'):
            operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)

    def test_parameter_change_after_review_cannot_execute(self):
        with tempfile.TemporaryDirectory() as directory:
            plan = operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
            self.aws.change['Parameters'][0]['ParameterValue'] = 'different'
            with self.assertRaisesRegex(ValueError, 'parameters differ'):
                operations.execute(self.aws, plan, ROOT, True)
        self.assertFalse(any(call[1] == 'execute-change-set' for call in self.aws.calls))

    def test_running_stack_update_is_rejected(self):
        self.aws.stacks['stack'] = {'StackStatus': 'UPDATE_IN_PROGRESS'}
        with self.assertRaisesRegex(ValueError, 'not ready'):
            operations.read_stack(self.aws, 'stack')

    def test_operator_record_is_private_and_cannot_overwrite_existing_file(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / 'record.json'
            operations.save(path, {'nonsecret': 'value'})
            self.assertEqual(path.stat().st_mode & 0o777, 0o600)
            with self.assertRaises(FileExistsError):
                operations.save(path, {'different': 'value'})
            self.assertEqual(json.loads(path.read_text()), {'nonsecret': 'value'})

    def test_missing_stack_after_wait_is_not_reported_verified(self):
        with tempfile.TemporaryDirectory() as directory:
            plan = operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
            self.aws.after_wait = lambda: self.aws.stacks.pop(self.config['stacks']['bootstrap'])
            with self.assertRaisesRegex(ValueError, 'Target stack disappeared'):
                operations.execute(self.aws, plan, ROOT, True)

    def test_active_native_deployment_is_rejected_before_rules_or_scheduler_reads(self):
        outputs, services, rules, schedule = fixture()
        outputs.update(ClusterArn='cluster', SchedulerArn='arn:aws:scheduler:ap-northeast-1:123456789012:schedule/default/name')
        calls = []
        def aws(service, operation, *args):
            calls.append(operation)
            if operation == 'describe-services':
                return services
            if operation == 'list-service-deployments':
                return {'serviceDeployments': [{'status': 'IN_PROGRESS'}]}
            self.fail('Must abort before reading rules or scheduler')
        with self.assertRaisesRegex(ValueError, 'Active deployment'):
            operations.runtime_snapshot(aws, outputs)
        self.assertEqual(calls, ['describe-services', 'list-service-deployments'])

    def test_completed_native_snapshot_reads_scheduler_by_arn_and_preserves_capacity(self):
        outputs, services, rules, schedule = fixture()
        outputs.update(ClusterArn='cluster', SchedulerArn='arn:aws:scheduler:ap-northeast-1:123456789012:schedule/group/name')
        def aws(service, operation, *args):
            if operation == 'describe-services':
                return services
            if operation == 'list-service-deployments':
                return {'serviceDeployments': []}
            if operation == 'describe-rules':
                return rules
            if operation == 'get-schedule':
                self.assertEqual(args, ('--group-name', 'group', '--name', 'name'))
                return schedule
            self.fail('Unexpected AWS request')
        snapshot = operations.runtime_snapshot(aws, outputs)
        self.assertEqual(snapshot['parameters']['WorkerDesiredCount'], '2')
        self.assertEqual(snapshot['parameters']['ProductionTargetGroup'], 'Green')

    def test_nested_unavailable_changeset_is_reviewable_only_through_its_root(self):
        root_arn, child_arn = 'root-change', 'child-change'
        responses = {
            root_arn: {'Status': 'CREATE_COMPLETE', 'ExecutionStatus': 'AVAILABLE',
                       'Changes': [{'ResourceChange': {'Action': 'Modify', 'Replacement': 'False', 'ChangeSetId': child_arn}}]},
            child_arn: {'Status': 'CREATE_COMPLETE', 'ExecutionStatus': 'UNAVAILABLE',
                        'RootChangeSetId': root_arn, 'ParentChangeSetId': root_arn, 'Changes': []},
        }
        def aws(service, operation, *args):
            return responses[args[1]]
        self.assertEqual(operations.change_set(aws, root_arn)['Status'], 'CREATE_COMPLETE')
        responses[child_arn]['RootChangeSetId'] = 'different-root'
        with self.assertRaisesRegex(ValueError, 'hierarchy'):
            operations.change_set(aws, root_arn)

    def test_unrelated_parent_changes_during_execution_are_not_verified(self):
        name = self.config['stacks']['root']
        self.aws.stacks[name] = {'StackStatus': 'CREATE_COMPLETE', 'LastUpdatedTime': 'before'}
        with tempfile.TemporaryDirectory() as directory:
            plan = operations.prepare(self.aws, self.config, 'bootstrap', ROOT, Path(directory), True)
            self.aws.after_wait = lambda: self.aws.stacks.pop(name)
            with self.assertRaisesRegex(ValueError, 'Untouched parent changed'):
                operations.execute(self.aws, plan, ROOT, True)

    def test_initial_runtime_verification_rejects_unexpected_activation(self):
        from environment import RUNTIME_STATE
        outputs = {'ApiBootstrapTaskDefinitionArn': 'api-bootstrap:1',
                   'WorkerBootstrapTaskDefinitionArn': 'worker-bootstrap:1',
                   'SchedulerBootstrapTaskDefinitionArn': 'scheduler-bootstrap:1'}
        values = {name: '' for name in RUNTIME_STATE}
        values.update(ApiDesiredCount='0', WorkerDesiredCount='0', SchedulerState='DISABLED',
                      PrimaryTargetGroup='Blue', ProductionTargetGroup='Blue', TestTargetGroup='Green')
        live = {**values, 'ApiTaskDefinitionArn': 'api-bootstrap:1',
                'WorkerTaskDefinitionArn': 'worker-bootstrap:1', 'SchedulerTaskDefinitionArn': 'scheduler-bootstrap:1'}
        operations.verify_initial_runtime(values, outputs, {'parameters': live})
        for change in ({'ApiDesiredCount': '1'}, {'SchedulerState': 'ENABLED'}, {'ApiTaskDefinitionArn': 'unexpected-release:2'}):
            with self.subTest(change=change), self.assertRaisesRegex(ValueError, 'Initial runtime'):
                operations.verify_initial_runtime(values, outputs, {'parameters': {**live, **change}})

    def test_terminal_native_rollback_identity_invalidates_old_snapshot(self):
        outputs, services, rules, schedule = fixture()
        outputs.update(ClusterArn='cluster', SchedulerArn='arn:aws:scheduler:ap-northeast-1:123456789012:schedule/group/name')
        history = []
        def aws(service, operation, *args):
            if operation == 'describe-services':
                return services
            if operation == 'list-service-deployments':
                return {'serviceDeployments': copy.deepcopy(history)}
            if operation == 'describe-rules':
                return rules
            return schedule
        before = operations.runtime_snapshot(aws, outputs)
        history.append({'serviceDeploymentArn': 'native-failed-release', 'status': 'ROLLBACK_SUCCESSFUL'})
        after = operations.runtime_snapshot(aws, outputs)
        self.assertEqual(before['parameters'], after['parameters'])
        self.assertNotEqual(before, after)

    def test_initial_runtime_create_with_and_without_pause_verifies_zero_task_state(self):
        import environment
        _, contract = environment.load_contract(ROOT)
        for paused in (False, True):
            self.aws = FakeAws()
            for predecessor in ('bootstrap', 'root', 'integration'):
                outputs = {link['output']: 'fixture-' + link['output'] for link in contract['links']
                           if link['from'] == predecessor}
                if predecessor == 'bootstrap':
                    outputs['CloudFormationExecutionRoleArn'] = 'fixture-cfn-role'
                self.aws.stacks[self.config['stacks'][predecessor]] = {
                    'StackStatus': 'CREATE_COMPLETE', 'Outputs': [
                        {'OutputKey': key, 'OutputValue': value} for key, value in outputs.items()]}
            outputs, services, rules, schedule = fixture()
            outputs.update(ClusterArn='cluster', SchedulerArn='arn:aws:scheduler:ap-northeast-1:123456789012:schedule/group/name',
                           ApiBootstrapTaskDefinitionArn='api-bootstrap:1', WorkerBootstrapTaskDefinitionArn='worker-bootstrap:1',
                           SchedulerBootstrapTaskDefinitionArn='scheduler-bootstrap:1')
            for prefix, value in zip(('Api', 'Worker'), services['services']):
                value.update(desiredCount=0, runningCount=0, taskDefinition=outputs[prefix + 'BootstrapTaskDefinitionArn'])
            services['services'][0]['loadBalancers'][0].update(targetGroupArn='blue',
                advancedConfiguration={'alternateTargetGroupArn': 'green', 'productionListenerRule': 'prod', 'testListenerRule': 'test'})
            rules['Rules'][0]['Actions'][0]['ForwardConfig']['TargetGroups'][0]['Weight'] = 1
            rules['Rules'][0]['Actions'][0]['ForwardConfig']['TargetGroups'][1]['Weight'] = 0
            schedule.update(State='DISABLED', Target={'EcsParameters': {'TaskDefinitionArn': 'scheduler-bootstrap:1'}})
            self.aws.runtime_data = outputs, services, rules, schedule
            def created():
                self.aws.stacks[self.config['stacks']['runtime']]['Outputs'] = [
                    {'OutputKey': key, 'OutputValue': value} for key, value in outputs.items()]
            self.aws.after_wait = created
            with self.subTest(paused=paused), tempfile.TemporaryDirectory() as directory:
                plan = operations.prepare(self.aws, self.config, 'runtime', ROOT, Path(directory), paused)
                self.assertEqual(operations.execute(self.aws, plan, ROOT, paused)['status'], 'verified')

    def test_all_native_pages_are_read_before_certifying_snapshot(self):
        outputs, services, rules, schedule = fixture()
        outputs.update(ClusterArn='cluster', SchedulerArn='arn:aws:scheduler:ap-northeast-1:123456789012:schedule/group/name')
        later_status = 'ROLLBACK_SUCCESSFUL'
        def aws(service, operation, *args):
            if operation == 'describe-services':
                return services
            if operation == 'list-service-deployments':
                if '--next-token' not in args:
                    return {'serviceDeployments': [{'serviceDeploymentArn': 'old', 'status': 'SUCCESSFUL'}], 'nextToken': 'second'}
                self.assertEqual(args[args.index('--next-token') + 1], 'second')
                return {'serviceDeployments': [{'serviceDeploymentArn': 'later', 'status': later_status}]}
            if operation == 'describe-rules':
                return rules
            return schedule
        result = operations.runtime_snapshot(aws, outputs)
        self.assertEqual(len(result['nativeHistory']['api']), 2)
        later_status = 'IN_PROGRESS'
        with self.assertRaisesRegex(ValueError, 'Active deployment'):
            operations.runtime_snapshot(aws, outputs)

    def test_post_execution_aba_is_rejected_even_when_runtime_parameters_match(self):
        before = {'parameters': {'ApiDesiredCount': '2'}, 'nativeHistory': {'api': []},
                  'deployments': {'api': ['old']}, 'schedulerModified': 'before'}
        after = copy.deepcopy(before)
        after['nativeHistory']['api'].append({'serviceDeploymentArn': 'new', 'status': 'ROLLBACK_SUCCESSFUL'})
        with self.assertRaisesRegex(ValueError, 'Runtime changed'):
            operations.verify_runtime_transition(before, after, runtime_updated=False)
        with self.assertRaisesRegex(ValueError, 'Unexpected native deployment'):
            operations.verify_runtime_transition(before, after, runtime_updated=True)
        after['nativeHistory']['api'][0]['status'] = 'SUCCESSFUL'
        operations.verify_runtime_transition(before, after, runtime_updated=True)


if __name__ == '__main__':
    unittest.main()
