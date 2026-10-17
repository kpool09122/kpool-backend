"""Alarm routing, metric dimensions and optional notification-only Slack access."""
import unittest

from cfnlint.decode import decode
from test_contracts import ROOT, rules_accept, statements
from test_runtime import resolve_conditions


class MonitoringTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.template, errors = decode(str(ROOT / 'runtime.yaml'))
        if errors:
            raise AssertionError(errors)
        cls.resources = cls.template['Resources']

    def test_slack_is_optional_and_both_ids_are_required(self):
        for workspace, channel, accepted in (('', '', True), ('TTEST123', 'CTEST456', True),
                                              ('TTEST123', '', False), ('', 'CTEST456', False)):
            with self.subTest(workspace=workspace, channel=channel):
                overrides = dict(SlackWorkspaceId=workspace, SlackChannelId=channel, CertificateArn='issued-certificate')
                self.assertEqual(rules_accept(self.template, overrides), accepted)
                properties = resolve_conditions({'Fn::If': ['HasSlack', True, False]}, self.template, overrides)
                self.assertEqual(properties, bool(workspace and channel))
        for name in ('SlackNotifications', 'SlackNotificationRole'):
            self.assertEqual(self.resources[name]['Condition'], 'HasSlack')

    def test_alarm_and_recovery_routes_share_the_same_topic(self):
        alarms = {name: resource['Properties'] for name, resource in self.resources.items()
                  if resource['Type'] == 'AWS::CloudWatch::Alarm'}
        self.assertIn('WorkQueueDlqAlarm', alarms)
        for name, alarm in alarms.items():
            with self.subTest(name=name):
                self.assertEqual(alarm['AlarmActions'], [{'Ref': 'AlarmTopic'}])
                self.assertEqual(alarm['OKActions'], [{'Ref': 'AlarmTopic'}])
        slack = self.resources['SlackNotifications']['Properties']
        self.assertEqual(slack['SnsTopicArns'], [{'Ref': 'AlarmTopic'}])
        self.assertEqual(slack['LoggingLevel'], 'NONE')

    def test_load_alarms_use_standard_service_metrics_and_owned_dimensions(self):
        for name, metric, statistic, comparison in (
            ('DatabaseCpuAlarm', 'CPUUtilization', 'Average', 'GreaterThanOrEqualToThreshold'),
            ('DatabaseFreeStorageAlarm', 'FreeStorageSpace', 'Minimum', 'LessThanThreshold'),
        ):
            alarm = self.resources[name]['Properties']
            self.assertEqual(alarm['Namespace'], 'AWS/RDS')
            self.assertEqual(alarm['Dimensions'], [{'Name': 'DBInstanceIdentifier', 'Value': {'Ref': 'DatabaseIdentifier'}}])
            self.assertEqual(alarm['MetricName'], metric)
            self.assertEqual(alarm['Statistic'], statistic)
            self.assertEqual(alarm['ComparisonOperator'], comparison)
        for service in ('Api', 'Worker'):
            for kind, metric in (('Cpu', 'CPUUtilization'), ('Memory', 'MemoryUtilization')):
                alarm = self.resources[service + kind + 'Alarm']['Properties']
                self.assertEqual(alarm['Namespace'], 'AWS/ECS')
                self.assertEqual(alarm['MetricName'], metric)
                self.assertEqual(alarm['Dimensions'], [
                    {'Name': 'ClusterName', 'Value': {'Ref': 'Cluster'}},
                    {'Name': 'ServiceName', 'Value': {'Fn::GetAtt': [service + 'Service', 'Name']}},
                ])
                self.assertEqual(alarm['Period'], 60)
                self.assertEqual(alarm['EvaluationPeriods'], {'Ref': 'LoadAlarmEvaluationPeriods'})
                self.assertEqual(alarm['TreatMissingData'], 'notBreaching')
        self.assertEqual(self.template['Parameters']['ContainerInsights']['Default'], 'disabled')

    def test_slack_permissions_cannot_modify_infrastructure_or_read_secrets(self):
        slack = self.resources['SlackNotifications']['Properties']
        self.assertTrue(slack['UserRoleRequired'])
        self.assertEqual(slack['IamRoleArn'], {'Fn::GetAtt': ['SlackNotificationRole', 'Arn']})
        self.assertEqual(slack['GuardrailPolicies'], [{'Fn::Sub': 'arn:${AWS::Partition}:iam::aws:policy/CloudWatchReadOnlyAccess'}])
        allowed = set()
        for statement in statements(self.resources['SlackNotificationRole']):
            self.assertEqual(statement['Effect'], 'Allow')
            action = statement['Action']
            allowed.update([action] if isinstance(action, str) else action)
        self.assertEqual(allowed, {'cloudwatch:DescribeAlarms', 'cloudwatch:GetMetricData',
                                   'cloudwatch:GetMetricStatistics', 'cloudwatch:GetMetricWidgetImage', 'cloudwatch:ListMetrics'})
        bootstrap, errors = decode(str(ROOT / 'bootstrap.yaml'))
        self.assertFalse(errors)
        policy = bootstrap['Resources']['FoundationGlobalPolicy']['Properties']['PolicyDocument']['Statement']
        grant = next(s for s in policy if s['Sid'] == 'PassSlackNotificationRole')
        self.assertEqual(grant['Resource'], {'Fn::Sub': 'arn:${AWS::Partition}:iam::${AWS::AccountId}:role/${ProjectName}-slack-notifications'})
        self.assertEqual(grant['Condition'], {'StringEquals': {'iam:PassedToService': 'chatbot.amazonaws.com'}})
        self.assertNotIn('slack-notifications', str(list(statements(bootstrap['Resources']['DeploymentRole']))))

