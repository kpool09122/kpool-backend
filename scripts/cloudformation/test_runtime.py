"""Runtime lifecycle contracts handed to #157 (AWS) and #156 (releases)."""
from itertools import product
import re
import unittest

from cfnlint.decode import decode
from test_contracts import ROOT, evaluate_rule, rules_accept, statements


def resolve_conditions(node, template, parameters):
    """Resolve Ref/Fn::If branches; keep resource references as logical IDs."""
    if isinstance(node, list):
        return [resolve_conditions(x, template, parameters) for x in node]
    if not isinstance(node, dict):
        return node
    if 'Ref' in node:
        return parameters.get(node['Ref'], node['Ref'])
    if 'Fn::If' in node:
        condition, yes, no = node['Fn::If']
        branch = yes if evaluate_rule(template['Conditions'][condition], parameters) else no
        return resolve_conditions(branch, template, parameters)
    return {key: resolve_conditions(value, template, parameters) for key, value in node.items()}


class RuntimeContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.template, errors = decode(str(ROOT / 'runtime.yaml'))
        if errors:
            raise AssertionError(errors)
        cls.runtime = cls.template['Resources']

    def test_target_roles_and_rule_weights_follow_parameters(self):
        # Reconciliation reflects each live value independently, including a test
        # rule that still points to the current production target after a release.
        for primary, production, test in product(('Blue', 'Green'), repeat=3):
            with self.subTest(primary=primary, production=production, test=test):
                parameters = {name: item.get('Default', '')
                              for name, item in self.template['Parameters'].items()}
                parameters.update(PrimaryTargetGroup=primary, ProductionTargetGroup=production,
                                  TestTargetGroup=test, CertificateArn='certificate')
                load_balancer = resolve_conditions(
                    self.runtime['ApiService']['Properties']['LoadBalancers'][0],
                    self.template, parameters)
                self.assertEqual(load_balancer['TargetGroupArn'], primary + 'TargetGroup')
                advanced = load_balancer['AdvancedConfiguration']
                alternate = 'Green' if primary == 'Blue' else 'Blue'
                self.assertEqual(advanced['AlternateTargetGroupArn'], alternate + 'TargetGroup')
                self.assertEqual(advanced['ProductionListenerRule'], 'ProductionRule')
                self.assertEqual(advanced['TestListenerRule'], 'TestRule')
                for rule, target in (('ProductionRule', production), ('TestRule', test)):
                    action = resolve_conditions(self.runtime[rule]['Properties']['Actions'][0],
                                                self.template, parameters)
                    self.assertEqual({x['TargetGroupArn']: x['Weight']
                                      for x in action['ForwardConfig']['TargetGroups']},
                                     {color + 'TargetGroup': int(color == target)
                                      for color in ('Blue', 'Green')})

    def test_hook_artifact_is_all_or_nothing_even_at_zero_tasks(self):
        for bucket, key, version in product(('', 'supplied'), repeat=3):
            with self.subTest(bucket=bucket, key=key, version=version):
                self.assertEqual(rules_accept(self.template, {
                    'CertificateArn': 'certificate', 'HookArtifactBucket': bucket,
                    'HookArtifactKey': key, 'HookArtifactVersion': version,
                }), bool(bucket) == bool(key) == bool(version))

    def test_api_hook_and_alarm_rollback_contract(self):
        api = self.runtime['ApiService']['Properties']
        configuration = api['DeploymentConfiguration']
        self.assertEqual(api['DeploymentController'], {'Type': 'ECS'})
        self.assertEqual(configuration['Strategy'], 'BLUE_GREEN')
        self.assertNotIn('DeploymentCircuitBreaker', configuration)
        self.assertNotIn('LinearConfiguration', configuration)
        self.assertNotIn('CanaryConfiguration', configuration)
        self.assertEqual(configuration['BakeTimeInMinutes'], 5)
        self.assertTrue(configuration['Alarms']['Enable'])
        self.assertTrue(configuration['Alarms']['Rollback'])
        self.assertEqual(configuration['Alarms']['AlarmNames'], [
            {'Ref': 'ServerErrorAlarm'}, {'Ref': 'LatencyAlarm'},
            {'Fn::If': ['HasCanary', {'Ref': 'ExternalCanaryAlarmName'}, {'Ref': 'AWS::NoValue'}]},
        ])
        hooks = configuration['LifecycleHooks']['Fn::If']
        self.assertEqual(hooks[0], 'HasHook')
        self.assertEqual(hooks[2], {'Ref': 'AWS::NoValue'})
        self.assertEqual(hooks[1], [{
            'HookTargetArn': {'Fn::GetAtt': ['LifecycleHook', 'Arn']},
            'RoleArn': {'Fn::GetAtt': ['HookInvocationRole', 'Arn']},
            'LifecycleStages': ['POST_TEST_TRAFFIC_SHIFT'],
            'HookDetails': {'contractVersion': '1', 'expectedHost': {'Ref': 'ApiDomainName'},
                            'testPort': 8443, 'healthPath': {'Ref': 'HealthCheckPath'}},
        }])
        worker = self.runtime['WorkerService']['Properties']['DeploymentConfiguration']
        self.assertEqual(worker['Strategy'], 'ROLLING')
        self.assertEqual(worker['DeploymentCircuitBreaker'], {'Enable': True, 'Rollback': True})
        self.assertNotIn('LifecycleHooks', worker)

    def test_private_hook_and_public_task_network_boundaries(self):
        for name, port in (('ProductionListener', 443), ('TestListener', 8443)):
            listener = self.runtime[name]['Properties']
            self.assertEqual(listener['Protocol'], 'HTTPS')
            self.assertEqual(listener['Port'], port)
            self.assertEqual(listener['DefaultActions'][0]['FixedResponseConfig']['StatusCode'], '403')
        self.assertEqual(self.runtime['LoadBalancer']['Properties']['Scheme'], 'internet-facing')
        self.assertEqual(self.runtime['LoadBalancer']['Properties']['Subnets'], {'Ref': 'PublicSubnetIds'})
        task_networks = [self.runtime[name]['Properties']['NetworkConfiguration']['AwsvpcConfiguration']
                         for name in ('ApiService', 'WorkerService')]
        task_networks.append(self.runtime['Schedule']['Properties']['Target']['EcsParameters']
                             ['NetworkConfiguration']['AwsvpcConfiguration'])
        for configuration in task_networks:
            self.assertEqual(configuration['Subnets'], {'Ref': 'PublicSubnetIds'})
            self.assertEqual(configuration['AssignPublicIp'], 'ENABLED')
        self.assertEqual(self.runtime['ApiSecurityGroup']['Properties']['SecurityGroupIngress'], [{
            'IpProtocol': 'tcp', 'FromPort': {'Ref': 'ApiPort'}, 'ToPort': {'Ref': 'ApiPort'},
            'SourceSecurityGroupId': {'Ref': 'AlbSecurityGroup'},
        }])
        self.assertEqual(self.runtime['AlbSecurityGroup']['Properties']['SecurityGroupIngress'], [{
            'IpProtocol': 'tcp', 'FromPort': 443, 'ToPort': 443, 'CidrIp': '0.0.0.0/0',
        }])
        self.assertEqual(self.runtime['TestListenerIngress']['Properties'], {
            'GroupId': {'Ref': 'AlbSecurityGroup'}, 'IpProtocol': 'tcp',
            'FromPort': 8443, 'ToPort': 8443, 'SourceSecurityGroupId': {'Ref': 'HookSecurityGroup'},
        })
        worker = self.runtime['WorkerService']['Properties']['NetworkConfiguration']['AwsvpcConfiguration']
        self.assertEqual(worker['SecurityGroups'], [{'Ref': 'ApplicationSecurityGroupId'}])
        network, errors = decode(str(ROOT / 'network.yaml'))
        self.assertFalse(errors)
        self.assertFalse(network['Resources']['ApplicationSecurityGroup']['Properties'].get('SecurityGroupIngress'))

    def test_hook_artifact_timeout_and_separated_roles(self):
        hook = self.runtime['LifecycleHook']
        self.assertEqual(hook['Condition'], 'HasHook')
        properties = hook['Properties']
        self.assertEqual(properties['Timeout'], 120)
        self.assertEqual(properties['Handler'], 'handler.handler')
        self.assertEqual(properties['Architectures'], ['arm64'])
        self.assertEqual(properties['Code'], {
            'S3Bucket': {'Ref': 'HookArtifactBucket'}, 'S3Key': {'Ref': 'HookArtifactKey'},
            'S3ObjectVersion': {'Ref': 'HookArtifactVersion'},
        })
        self.assertEqual(properties['VpcConfig'], {
            'SubnetIds': {'Ref': 'PrivateSubnetIds'}, 'SecurityGroupIds': [{'Ref': 'HookSecurityGroup'}],
        })
        self.assertEqual(list(statements(self.runtime['HookInvocationRole'])), [{
            'Effect': 'Allow', 'Action': ['lambda:InvokeFunction'],
            'Resource': {'Fn::GetAtt': ['LifecycleHook', 'Arn']},
        }])
        for role, service in (('AlbInfrastructureRole', 'ecs.amazonaws.com'),
                              ('HookInvocationRole', 'ecs.amazonaws.com'),
                              ('HookExecutionRole', 'lambda.amazonaws.com')):
            trust = self.runtime[role]['Properties']['AssumeRolePolicyDocument']['Statement']
            self.assertEqual(trust, [{'Effect': 'Allow', 'Principal': {'Service': service},
                                     'Action': 'sts:AssumeRole'}])
        grants = list(statements(self.runtime['AlbInfrastructureRole']))
        self.assertEqual(grants[1], {'Effect': 'Allow', 'Action': ['elasticloadbalancing:ModifyRule'],
                                    'Resource': [{'Ref': 'ProductionRule'}, {'Ref': 'TestRule'}]})
        self.assertEqual(grants[2], {'Effect': 'Allow',
                                    'Action': ['elasticloadbalancing:RegisterTargets',
                                               'elasticloadbalancing:DeregisterTargets'],
                                    'Resource': [{'Ref': 'BlueTargetGroup'}, {'Ref': 'GreenTargetGroup'}]})

    def test_alarms_follow_operator_inputs(self):
        for name, metric, statistic, threshold in (
            ('ServerErrorAlarm', 'HTTPCode_Target_5XX_Count', 'Sum', 'ErrorCountThreshold'),
            ('LatencyAlarm', 'TargetResponseTime', 'Average', 'LatencyThresholdSeconds'),
        ):
            alarm = self.runtime[name]['Properties']
            self.assertEqual(alarm['MetricName'], metric)
            self.assertEqual(alarm['Statistic'], statistic)
            for key, parameter in (('Period', 'AlarmPeriod'), ('EvaluationPeriods', 'AlarmEvaluationPeriods'),
                                   ('Threshold', threshold), ('TreatMissingData', 'AlarmMissingData')):
                self.assertEqual(alarm[key], {'Ref': parameter})
            self.assertEqual(alarm['ComparisonOperator'], 'GreaterThanOrEqualToThreshold')

    def test_worker_timeout_stop_and_visibility_contract(self):
        worker = self.runtime['WorkerBootstrapTaskDefinition']['Properties']['ContainerDefinitions'][0]
        self.assertEqual(worker['StopTimeout'], 120)
        self.assertIn('--timeout=90', worker['Command'])
        foundation, errors = decode(str(ROOT / 'storage.yaml'))
        self.assertFalse(errors)
        queues = [x['Properties'] for x in foundation['Resources'].values()
                  if x['Type'] == 'AWS::SQS::Queue' and 'RedrivePolicy' in x['Properties']]
        self.assertEqual(len(queues), 1)
        self.assertEqual(queues[0]['VisibilityTimeout'], 300)
        self.assertLess(90, worker['StopTimeout'])
        self.assertLess(worker['StopTimeout'], queues[0]['VisibilityTimeout'])

    def test_bootstrap_and_release_selection(self):
        defaults = {name: item.get('Default', '')
                    for name, item in self.template['Parameters'].items()}
        for service, prefix, container in (
            ('ApiService', 'Api', 'api'),
            ('WorkerService', 'Worker', 'worker'),
        ):
            with self.subTest(service=service):
                props = self.runtime[service]['Properties']
                bootstrap_name = prefix + 'BootstrapTaskDefinition'
                task = self.runtime[bootstrap_name]['Properties']
                self.assertEqual(task['RuntimePlatform'], {'CpuArchitecture': 'ARM64', 'OperatingSystemFamily': 'LINUX'})
                self.assertEqual(task['NetworkMode'], 'awsvpc')
                self.assertEqual(task['RequiresCompatibilities'], ['FARGATE'])
                self.assertEqual(task['Cpu'], {'Ref': prefix + 'Cpu'})
                self.assertEqual(task['Memory'], {'Ref': prefix + 'Memory'})
                self.assertEqual(defaults[prefix + 'DesiredCount'], 0)
                self.assertEqual(task['ContainerDefinitions'][0]['Name'], container)
                self.assertTrue(task['ContainerDefinitions'][0]['ReadonlyRootFilesystem'])
                self.assertEqual(resolve_conditions(props['TaskDefinition'], self.template, defaults), bootstrap_name)
                active = {**defaults, prefix + 'TaskDefinitionArn': 'approved-release:42'}
                self.assertEqual(resolve_conditions(props['TaskDefinition'], self.template, active), 'approved-release:42')

    def test_release_outputs_reference_the_owned_resources(self):
        for output, resource in (
            ('ApiServiceArn', 'ApiService'), ('WorkerServiceArn', 'WorkerService'),
            ('ProductionRuleArn', 'ProductionRule'), ('TestRuleArn', 'TestRule'),
            ('BlueTargetGroupArn', 'BlueTargetGroup'), ('GreenTargetGroupArn', 'GreenTargetGroup'),
            ('ApiSecurityGroupId', 'ApiSecurityGroup'), ('AlbSecurityGroupId', 'AlbSecurityGroup'),
            ('HookSecurityGroupId', 'HookSecurityGroup'), ('ServerErrorAlarmName', 'ServerErrorAlarm'),
            ('LatencyAlarmName', 'LatencyAlarm'),
        ):
            self.assertEqual(self.template['Outputs'][output]['Value'], {'Ref': resource})
        for output in ('LifecycleHookArn', 'HookInvocationRoleArn', 'HookExecutionRoleArn'):
            self.assertEqual(self.template['Outputs'][output]['Condition'], 'HasHook')
        self.assertEqual(self.template['Outputs']['ExternalCanaryAlarmName']['Value'],
                         {'Ref': 'ExternalCanaryAlarmName'})

    def test_http_draining_exceeds_processing_limits(self):
        repository = ROOT.parents[1]
        nginx = (repository / 'docker/production/nginx.conf').read_text()
        fpm = (repository / 'docker/production/php-fpm.conf').read_text()
        request_limit = int(re.search(r'request_terminate_timeout\s*=\s*(\d+)s', fpm)[1])
        upstream_wait = int(re.search(r'fastcgi_read_timeout\s+(\d+)s', nginx)[1])
        shutdown = int(re.search(r'worker_shutdown_timeout\s+(\d+)s', nginx)[1])
        stop = self.runtime['ApiBootstrapTaskDefinition']['Properties']['ContainerDefinitions'][0]['StopTimeout']
        self.assertLessEqual(request_limit, upstream_wait)
        self.assertLess(upstream_wait, shutdown)
        self.assertLess(shutdown, stop)
        attributes = {x['Key']: x['Value'] for x in
                      self.runtime['LoadBalancer']['Properties']['LoadBalancerAttributes']}
        # ALB default idle timeout (60s) also cuts off a silent 95s request.
        self.assertGreater(int(attributes.get('idle_timeout.timeout_seconds', '60')), shutdown)
        for group in ('BlueTargetGroup', 'GreenTargetGroup'):
            with self.subTest(group=group):
                attributes = {x['Key']: x['Value'] for x in
                              self.runtime[group]['Properties']['TargetGroupAttributes']}
                self.assertEqual(int(attributes['deregistration_delay.timeout_seconds']), stop)


if __name__ == '__main__':
    unittest.main()
