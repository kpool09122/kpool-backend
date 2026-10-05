"""Only stable live deployment state can become infrastructure input."""
import copy
import unittest

import runtime_state


def service(name, target=None):
    value = {'serviceName': name, 'serviceArn': 'arn:' + name, 'status': 'ACTIVE',
             'taskDefinition': 'release:42', 'desiredCount': 2, 'runningCount': 2,
             'pendingCount': 0, 'deployments': [{'status': 'PRIMARY', 'rolloutState': 'COMPLETED'}],
             'loadBalancers': []}
    if target:
        value['loadBalancers'] = [{'targetGroupArn': target,
                                  'advancedConfiguration': {'alternateTargetGroupArn': 'blue',
                                                            'productionListenerRule': 'prod',
                                                            'testListenerRule': 'test'}}]
    return value


def fixture():
    outputs = {'ApiServiceName': 'api', 'WorkerServiceName': 'worker', 'BlueTargetGroupArn': 'blue',
               'GreenTargetGroupArn': 'green', 'ProductionRuleArn': 'prod', 'TestRuleArn': 'test'}
    rules = {'Rules': [{'RuleArn': name, 'Actions': [{'Type': 'forward', 'ForwardConfig': {
        'TargetGroups': [{'TargetGroupArn': 'blue', 'Weight': 0},
                         {'TargetGroupArn': 'green', 'Weight': 1}]}}]} for name in ('prod', 'test')]}
    return outputs, {'failures': [], 'services': [service('api', 'green'), service('worker')]}, rules, {
        'State': 'ENABLED', 'Target': {'EcsParameters': {'TaskDefinitionArn': 'scheduler-release:9'}}}


class RuntimeStateTests(unittest.TestCase):
    def test_released_revision_capacity_and_independent_test_target_survive_update(self):
        outputs, services, rules, schedule = fixture()
        result = runtime_state.resolve(outputs, services, rules, schedule)
        self.assertEqual(result['ApiTaskDefinitionArn'], 'release:42')
        self.assertEqual(result['ApiDesiredCount'], '2')
        self.assertEqual(result['PrimaryTargetGroup'], 'Green')
        self.assertEqual(result['TestTargetGroup'], 'Green')
        self.assertEqual(result['SchedulerTaskDefinitionArn'], 'scheduler-release:9')

    def test_unstable_or_unrepresentable_state_is_rejected(self):
        mutations = [
            lambda o, s, r, q: s.update(failures=[{'reason': 'MISSING'}]),
            lambda o, s, r, q: s['services'][0].update(pendingCount=1),
            lambda o, s, r, q: s['services'][0].update(runningCount=1),
            lambda o, s, r, q: s['services'][0]['deployments'][0].update(rolloutState='IN_PROGRESS'),
            lambda o, s, r, q: s['services'][0]['deployments'].append({'status': 'ACTIVE'}),
            lambda o, s, r, q: s['services'][0]['loadBalancers'][0].update(targetGroupArn='unknown'),
            lambda o, s, r, q: r['Rules'][0]['Actions'][0]['ForwardConfig']['TargetGroups'][0].update(Weight=1),
            lambda o, s, r, q: r['Rules'][0]['Actions'][0]['ForwardConfig']['TargetGroups'][1].update(Weight=100),
            lambda o, s, r, q: r['Rules'][0]['Actions'][0]['ForwardConfig'].update(TargetGroupStickinessConfig={'Enabled': True}),
            lambda o, s, r, q: r['Rules'][0]['Actions'].append({'Type': 'authenticate-oidc'}),
        ]
        for mutate in mutations:
            with self.subTest(mutate=mutate):
                values = fixture()
                mutate(*values)
                with self.assertRaises(ValueError):
                    runtime_state.resolve(*values)

    def test_zero_task_bootstrap_is_preserved_without_activating_scheduler(self):
        outputs, services, rules, schedule = fixture()
        for value in services['services']:
            value.update(desiredCount=0, runningCount=0, taskDefinition='bootstrap:1')
        schedule['State'] = 'DISABLED'
        result = runtime_state.resolve(outputs, services, rules, schedule)
        self.assertEqual(result['ApiDesiredCount'], '0')
        self.assertEqual(result['ApiTaskDefinitionArn'], 'bootstrap:1')
        self.assertEqual(result['SchedulerState'], 'DISABLED')


if __name__ == '__main__':
    unittest.main()
