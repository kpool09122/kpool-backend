"""Offline architectural invariants; cfn-lint separately validates AWS schemas.

Checks intentionally inspect policy/resource behavior, not complete golden files.
Mutation tests prove critical guards reject insecure but schema-valid changes.
"""
import copy
import json
from pathlib import Path
import re
import unittest

from cfnlint.decode import decode

ROOT = Path(__file__).resolve().parents[2] / 'infra/cloudformation'


def resources(templates, kind):
    for stack, template in templates.items():
        for name, resource in template.get('Resources', {}).items():
            if resource['Type'] == kind:
                yield stack, name, resource


def serialized(value):
    return json.dumps(value, sort_keys=True)


def statements(role):
    for policy in role.get('Properties', {}).get('Policies', []):
        yield from policy['PolicyDocument']['Statement']


def security_errors(templates):
    errors = []
    for stack, template in templates.items():
        for name, resource in template.get('Resources', {}).items():
            kind, props = resource['Type'], resource.get('Properties', {})
            label = f'{stack}.{name}'
            if kind == 'AWS::EC2::NatGateway':
                errors.append(f'{label}: NAT is forbidden')
            if kind == 'AWS::RDS::DBInstance':
                if props.get('PubliclyAccessible') is not False:
                    errors.append(f'{label}: RDS must be private')
                if props.get('StorageEncrypted') is not True:
                    errors.append(f'{label}: RDS encryption required')
                if props.get('ManageMasterUserPassword') is not True:
                    errors.append(f'{label}: use managed RDS credentials')
            if kind == 'AWS::S3::Bucket':
                block = props.get('PublicAccessBlockConfiguration', {})
                if any(block.get(key) is not True for key in (
                    'BlockPublicAcls', 'BlockPublicPolicy', 'IgnorePublicAcls', 'RestrictPublicBuckets'
                )):
                    errors.append(f'{label}: all S3 public access blocks required')
                if not props.get('BucketEncryption'):
                    errors.append(f'{label}: S3 encryption required')
            if kind in {'AWS::RDS::DBInstance', 'AWS::S3::Bucket', 'AWS::SQS::Queue',
                        'AWS::SecretsManager::Secret', 'AWS::ElastiCache::ServerlessCache',
                        'AWS::Logs::LogGroup', 'AWS::ECR::Repository', 'AWS::SSM::Parameter',
                        'AWS::ElastiCache::User', 'AWS::ElastiCache::UserGroup'}:
                if resource.get('DeletionPolicy') not in {'Retain', 'Snapshot'}:
                    errors.append(f'{label}: persistent data deletion policy required')
                if resource.get('UpdateReplacePolicy') not in {'Retain', 'Snapshot'}:
                    errors.append(f'{label}: persistent data replacement policy required')
            if kind == 'AWS::EC2::SecurityGroupIngress':
                if props.get('FromPort') in (5432, 6379, 6380) and (
                    not props.get('SourceSecurityGroupId') or props.get('CidrIp') or props.get('CidrIpv6')
                ):
                    errors.append(f'{label}: private data ingress must reference a source SG')
            if kind == 'AWS::EC2::SecurityGroup':
                for ingress in props.get('SecurityGroupIngress', []):
                    if ingress.get('FromPort') in (5432, 6379, 6380) and (
                        not ingress.get('SourceSecurityGroupId') or ingress.get('CidrIp') or ingress.get('CidrIpv6')
                    ):
                        errors.append(f'{label}: private data ingress must reference a source SG')
            if kind == 'AWS::SecretsManager::Secret' and 'SecretString' in props:
                errors.append(f'{label}: secret values must not be embedded')
            if kind == 'AWS::ECS::TaskDefinition':
                for container in props.get('ContainerDefinitions', []):
                    for item in container.get('Environment', []):
                        if re.search(r'PASSWORD|SECRET|TOKEN|APP_KEY', item['Name']):
                            errors.append(f'{label}: sensitive environment must use Secrets references')
        for name, output in template.get('Outputs', {}).items():
            if re.search(r'\{\{resolve:(secretsmanager|ssm-secure):', serialized(output)):
                errors.append(f'{stack}.{name}: resolved secret output forbidden')
        for name, parameter in template.get('Parameters', {}).items():
            if parameter.get('NoEcho') and parameter.get('Default') not in (None, ''):
                errors.append(f'{stack}.{name}: secret default forbidden')
    return errors


def evaluate_rule(node, parameters):
    """Evaluate the small CloudFormation Rules boolean vocabulary used here."""
    if not isinstance(node, dict):
        return node
    if 'Ref' in node:
        return parameters[node['Ref']]
    key, values = next(iter(node.items()))
    values = [evaluate_rule(value, parameters) for value in values]
    if key == 'Fn::Equals':
        return str(values[0]) == str(values[1])
    if key == 'Fn::Not':
        return not values[0]
    if key == 'Fn::And':
        return all(values)
    if key == 'Fn::Or':
        return any(values)
    raise AssertionError(f'Unsupported rule operator: {key}')


def rules_accept(template, overrides):
    values = {name: item.get('Default', '') for name, item in template.get('Parameters', {}).items()}
    values.update({'AWS::Region': 'ap-northeast-1'})
    values.update(overrides)
    for rule in template.get('Rules', {}).values():
        if 'RuleCondition' in rule and not evaluate_rule(rule['RuleCondition'], values):
            continue
        for assertion in rule['Assertions']:
            if not evaluate_rule(assertion['Assert'], values):
                return False
    return True


class CloudFormationContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.templates = {}
        for path in sorted(ROOT.glob('*.yaml')):
            template, errors = decode(str(path))
            if errors:
                raise AssertionError(errors)
            cls.templates[path.stem] = template
        cls.contract = json.loads((ROOT / 'contracts.json').read_text())

    def test_stack_inventory_and_linkage(self):
        self.assertEqual(set(self.templates), set(self.contract['stacks']))
        self.assertEqual(self.contract['region'], 'ap-northeast-1')
        seen = set()
        for link in self.contract['links']:
            with self.subTest(link=link):
                self.assertIn(link['output'], self.templates[link['from']]['Outputs'])
                self.assertIn(link['parameter'], self.templates[link['to']]['Parameters'])
                target = (link['to'], link['parameter'])
                self.assertNotIn(target, seen)
                seen.add(target)
        for item in self.contract.get('externalInputs', []):
            self.assertIn(item['parameter'], self.templates[item['stack']]['Parameters'])
            self.assertTrue(item['description'])

    def test_complete_contract_and_examples(self):
        order = self.contract['stacks']
        linked = {(x['to'], x['parameter']) for x in self.contract['links']}
        external = {(x['stack'], x['parameter']) for x in self.contract['externalInputs']}
        self.assertFalse(linked & external)
        for link in self.contract['links']:
            self.assertLess(order.index(link['from']), order.index(link['to']))
        shared = {}
        for stack, template in self.templates.items():
            values = json.loads((ROOT / 'parameters' / f'{stack}.json').read_text())
            example = {x['ParameterKey']: x['ParameterValue'] for x in values}
            self.assertEqual(set(example), set(template.get('Parameters', {})))
            self.assertEqual(set(self.contract['outputInventory'][stack]), set(template['Outputs']))
            self.assertTrue(rules_accept(template, example), stack)
            for name in template.get('Parameters', {}):
                self.assertIn((stack, name), linked | external)
                if name in self.contract['sharedParameters']:
                    self.assertEqual(shared.setdefault(name, example[name]), example[name])

    def test_activation_rules_fail_closed(self):
        template = self.templates['runtime']
        base = {'CertificateArn': 'arn:aws:acm:ap-northeast-1:123456789012:certificate/example'}
        self.assertTrue(rules_accept(template, base))
        for desired in ('ApiDesiredCount', 'WorkerDesiredCount'):
            self.assertFalse(rules_accept(template, {**base, desired: 1}))
        self.assertFalse(rules_accept(template, {**base, 'SchedulerState': 'ENABLED'}))
        ready = {**base, 'ApiDesiredCount': 1, 'ApiTaskDefinitionArn': 'release:1',
                 'HookArtifactBucket': 'artifact-bucket', 'HookArtifactKey': 'hook.zip',
                 'HookArtifactVersion': 'immutable-version', 'ExternalCanaryAlarmName': 'continuous-smoke'}
        self.assertTrue(rules_accept(template, ready))
        for required in ('ApiTaskDefinitionArn', 'HookArtifactBucket', 'HookArtifactKey',
                         'HookArtifactVersion', 'ExternalCanaryAlarmName'):
            with self.subTest(required=required):
                self.assertFalse(rules_accept(template, {**ready, required: ''}))
        self.assertTrue(rules_accept(template, {**base, 'WorkerDesiredCount': 1,
                                              'WorkerTaskDefinitionArn': 'worker-release:1'}))
        self.assertTrue(rules_accept(template, {**base, 'SchedulerState': 'ENABLED',
                                              'SchedulerTaskDefinitionArn': 'scheduler-release:1'}))
        self.assertFalse(rules_accept(template, {**base, 'ApiCpu': '1024', 'ApiMemory': '1024'}))
        self.assertFalse(rules_accept(template, {**base, 'WorkerCpu': '512', 'WorkerMemory': '512'}))
        for t in self.templates.values():
            if 'TokyoOnly' in t.get('Rules', {}):
                self.assertFalse(rules_accept(t, {**base, 'AWS::Region': 'us-east-1'}))

    def test_parameter_examples(self):
        for stack, template in self.templates.items():
            values = json.loads((ROOT / 'parameters' / f'{stack}.json').read_text())
            example = {item['ParameterKey']: item['ParameterValue'] for item in values}
            self.assertEqual(len(values), len(example), f'{stack}: duplicate parameter')
            parameters = template.get('Parameters', {})
            self.assertFalse(set(example) - set(parameters), f'{stack}: unknown parameters')
            for name, parameter in parameters.items():
                with self.subTest(stack=stack, parameter=name):
                    self.assertTrue(name in example or 'Default' in parameter, 'required input missing')
                    value = example.get(name, parameter.get('Default'))
                    if 'AllowedValues' in parameter:
                        self.assertIn(str(value), list(map(str, parameter['AllowedValues'])))
                    if 'AllowedPattern' in parameter:
                        self.assertIsNotNone(re.fullmatch(parameter['AllowedPattern'], str(value)))
                    if parameter['Type'] == 'Number':
                        if 'MinValue' in parameter:
                            self.assertGreaterEqual(float(value), parameter['MinValue'])
                        if 'MaxValue' in parameter:
                            self.assertLessEqual(float(value), parameter['MaxValue'])
                    if parameter.get('NoEcho'):
                        self.assertIn(value, ('', None), 'example must contain no secrets')

    def test_security_and_retention(self):
        self.assertEqual(security_errors(self.templates), [])

    def test_mutations_are_rejected(self):
        cases = [
            ('AWS::S3::Bucket', lambda r: r['Properties']['PublicAccessBlockConfiguration'].update(BlockPublicPolicy=False), 'public access'),
            ('AWS::RDS::DBInstance', lambda r: r['Properties'].update(PubliclyAccessible=True), 'private'),
            ('AWS::SQS::Queue', lambda r: r.update(UpdateReplacePolicy='Delete'), 'replacement'),
        ]
        for kind, mutate, message in cases:
            with self.subTest(kind=kind):
                templates = copy.deepcopy(self.templates)
                matches = list(resources(templates, kind))
                self.assertTrue(matches)
                mutate(matches[0][2])
                self.assertTrue(any(message in error for error in security_errors(templates)))

    def test_foundation_is_not_duplicated(self):
        expected = {'AWS::EC2::VPC': 1, 'AWS::RDS::DBInstance': 1,
                    'AWS::ElastiCache::ServerlessCache': 1, 'AWS::ElastiCache::User': 1,
                    'AWS::S3::Bucket': 2, 'AWS::CloudFront::Distribution': 1,
                    'AWS::CloudFront::OriginAccessControl': 1, 'AWS::SQS::Queue': 3}
        for kind, count in expected.items():
            with self.subTest(kind=kind):
                self.assertEqual(len(list(resources(self.templates, kind))), count)
        self.assertEqual(set(self.contract['deploymentOrder']), {'bootstrap', 'root', 'integration', 'runtime'})
        self.assertEqual(set(self.contract['nestedStacks']), {'network', 'data', 'storage'})
        self.assertEqual([n for _, n, _ in resources({'runtime': self.templates['runtime']}, 'AWS::SQS::Queue')],
                         ['SchedulerDlq'])
        self.assertEqual(self.templates['runtime']['Resources']['WorkerService']['Properties']
                         ['NetworkConfiguration']['AwsvpcConfiguration']['SecurityGroups'],
                         [{'Ref': 'ApplicationSecurityGroupId'}])
        for link in self.contract['links']:
            if link['to'] in {'integration', 'runtime'} and link['from'] != 'integration':
                self.assertEqual(link['from'], 'root')

    def test_s3_task_permissions_match_uploads_and_document_existence(self):
        runtime = self.templates['runtime']['Resources']
        for role, expected in (
            ('ApiTaskRole', [('ImagesBucketArn', '/images/*'),
                             ('FilesBucketArn', '/verification-documents/*')]),
            ('WorkerTaskRole', [('ImagesBucketArn', '/images/*')]),
        ):
            with self.subTest(role=role):
                grants = list(statements(runtime[role]))
                acl_grants = [s for s in grants if s['Action'] == 's3:PutObjectAcl']
                self.assertEqual(len(acl_grants), len(expected))
                self.assertEqual(
                    [s['Resource'] for s in acl_grants],
                    [{'Fn::Join': ['', [{'Ref': bucket}, prefix]]}
                     for bucket, prefix in expected],
                )
                for grant in acl_grants:
                    self.assertEqual(grant['Condition'], {
                        'StringEquals': {'s3:x-amz-acl': 'bucket-owner-full-control'},
                    })
                list_grants = [s for s in grants if s['Action'] == 's3:ListBucket']
                if role == 'ApiTaskRole':
                    self.assertEqual(len(list_grants), 1)
                    self.assertEqual(list_grants[0]['Resource'], {'Ref': 'FilesBucketArn'})
                    # HeadObject supplies no s3:prefix context; a condition breaks missing-key 404s.
                    self.assertNotIn('Condition', list_grants[0])
                else:
                    self.assertEqual(list_grants, [])
                    self.assertNotIn('FilesBucketArn', serialized(grants))

    def test_password_cache_and_role_separation(self):
        runtime = self.templates['runtime']['Resources']
        app = serialized(list(statements(runtime['AppExecutionRole'])))
        migration = serialized(list(statements(runtime['MigrationExecutionRole'])))
        self.assertIn('CacheSecretArn', app)
        self.assertNotIn('CacheSecretArn', migration)
        self.assertNotIn('elasticache:Connect', serialized(self.templates['runtime']))
        self.assertEqual(runtime['MigrationDatabaseIngress']['Properties']['GroupId'],
                         {'Ref': 'DatabaseSecurityGroupId'})
        self.assertEqual(runtime['ApiCacheIngress']['Properties']['GroupId'],
                         {'Ref': 'CacheSecurityGroupId'})
        for resource in self.templates['bootstrap']['Resources'].values():
            if resource['Type'] == 'AWS::IAM::ManagedPolicy':
                policy = json.dumps(resource['Properties']['PolicyDocument'], separators=(',', ':'))
                self.assertLessEqual(len(policy), 6144, 'IAM managed policy size limit')

    def test_foundation_execution_permissions(self):
        bootstrap = self.templates['bootstrap']['Resources']
        regional = bootstrap['FoundationRegionalPolicy']['Properties']['PolicyDocument']['Statement']
        named = bootstrap['FoundationNamedPolicy']['Properties']['PolicyDocument']['Statement']
        global_policy = bootstrap['FoundationGlobalPolicy']['Properties']['PolicyDocument']['Statement']
        by_sid = {s.get('Sid'): s for s in regional + named + global_policy if 'Sid' in s}
        nested = by_sid['ManageNestedFoundation']
        self.assertIn('cloudformation:CreateStack', nested['Action'])
        self.assertIn('${FoundationStackName}-*', serialized(nested['Resource']))
        self.assertEqual(by_sid['ReadPackagedFoundation']['Action'], ['s3:GetObject', 's3:GetObjectVersion'])
        self.assertIn('${FoundationTemplateBucketName}', serialized(by_sid['ReadPackagedFoundation']['Resource']))
        self.assertIn('${FoundationStackName}-*', serialized(by_sid['Buckets']['Resource']))
        self.assertIn('${FoundationWorkQueueName}', serialized(by_sid['NamedRegionalResources']['Resource']))
        self.assertEqual(by_sid['ResolveFoundationCachePassword']['Action'], ['secretsmanager:GetSecretValue'])
        self.assertNotEqual(by_sid['ResolveFoundationCachePassword']['Resource'], '*')
        trust = bootstrap['CloudFormationExecutionRole']['Properties']['AssumeRolePolicyDocument']
        self.assertEqual(trust['Statement'][0]['Principal'], {'Service': 'cloudformation.amazonaws.com'})
        for statement in statements(bootstrap['DeploymentRole']):
            self.assertNotIn('cloudformation:CreateStack', statement.get('Action', []))
            if statement.get('Sid') == 'PassApplicationRoles':
                self.assertNotIn('cloudformation-execution', serialized(statement))
                self.assertNotIn('alb-infrastructure', serialized(statement))
        for stack in self.contract['deploymentOrder']:
            self.assertLessEqual((ROOT / f'{stack}.yaml').stat().st_size, 51200,
                                 'Documented template-body path must fit CloudFormation limit')

    def test_private_dns_endpoint_execution_permission(self):
        """Private DNS endpoints need a global Route 53 association permission."""
        runtime = self.templates['runtime']['Resources']
        self.assertTrue(runtime['Ec2Endpoint']['Properties']['PrivateDnsEnabled'])
        bootstrap = self.templates['bootstrap']['Resources']
        role = bootstrap['CloudFormationExecutionRole']['Properties']
        grants = []
        for policy_ref in role['ManagedPolicyArns']:
            policy = bootstrap[policy_ref['Ref']]['Properties']['PolicyDocument']
            for statement in policy['Statement']:
                actions = statement.get('Action', [])
                if isinstance(actions, str):
                    actions = [actions]
                if statement.get('Effect') == 'Allow' and 'route53:AssociateVPCWithHostedZone' in actions:
                    grants.append(statement)
        self.assertTrue(grants, 'CloudFormation must be able to associate endpoint private DNS')
        for grant in grants:
            self.assertNotIn('aws:RequestedRegion', serialized(grant.get('Condition', {})))
            self.assertEqual(grant['Resource'],
                             {'Fn::Sub': 'arn:${AWS::Partition}:route53:::hostedzone/*'})

    def test_native_runtime_bootstrap(self):
        runtime = self.templates['runtime']
        for name in ['ApiDesiredCount', 'WorkerDesiredCount']:
            self.assertEqual(runtime['Parameters'][name]['Default'], 0)
        self.assertEqual(runtime['Parameters']['SchedulerState']['Default'], 'DISABLED')
        self.assertTrue(runtime.get('Rules'), 'activation prerequisites must be checked by CloudFormation')
        services = list(resources({'runtime': runtime}, 'AWS::ECS::Service'))
        self.assertEqual(len(services), 2)
        strategies = []
        for _, name, resource in services:
            props = resource['Properties']
            self.assertEqual(props['NetworkConfiguration']['AwsvpcConfiguration']['AssignPublicIp'], 'ENABLED')
            configuration = props['DeploymentConfiguration']
            strategies.append(configuration.get('Strategy', 'ROLLING'))
            if configuration.get('Strategy') == 'BLUE_GREEN':
                self.assertNotIn('DeploymentCircuitBreaker', configuration,
                                 'Circuit breaker is only supported for rolling updates')
                self.assertEqual(configuration['BakeTimeInMinutes'], 5)
                self.assertTrue(configuration['Alarms']['Rollback'])
                self.assertTrue(configuration['LifecycleHooks'])
            else:
                self.assertTrue(configuration['DeploymentCircuitBreaker']['Rollback'])
        self.assertCountEqual(strategies, ['BLUE_GREEN', 'ROLLING'])
        for _, _, resource in resources({'runtime': runtime}, 'AWS::ECS::TaskDefinition'):
            self.assertEqual(resource['Properties']['RuntimePlatform']['CpuArchitecture'], 'ARM64')
        groups = list(resources({'runtime': runtime}, 'AWS::ElasticLoadBalancingV2::TargetGroup'))
        self.assertEqual(len(groups), 2)
        self.assertTrue(all(r['Properties']['TargetType'] == 'ip' for _, _, r in groups))

    def test_oidc_and_deployment_iam(self):
        deployment_roles = []
        for _, name, role in resources(self.templates, 'AWS::IAM::Role'):
            trust = serialized(role['Properties']['AssumeRolePolicyDocument'])
            if 'sts:AssumeRoleWithWebIdentity' not in trust:
                continue
            deployment_roles.append(role)
            self.assertIn('token.actions.githubusercontent.com:aud', trust)
            self.assertIn('sts.amazonaws.com', trust)
            self.assertIn('token.actions.githubusercontent.com:sub', trust)
            self.assertIn('environment:', trust)
            self.assertIn('StringEquals', trust)
            for statement in statements(role):
                if statement.get('Effect') != 'Allow':
                    continue
                actions = statement['Action']
                if isinstance(actions, str):
                    actions = [actions]
                for action in actions:
                    service, operation = action.lower().split(':')
                    self.assertNotIn(service, ['*', 'rds', 'elasticache', 'ec2'])
                    self.assertNotEqual(operation, '*')
                    if service == 'cloudformation':
                        self.assertIn(operation, ['describestacks'])
                    if service == 'iam':
                        self.assertEqual(operation, 'passrole')
                        self.assertNotEqual(statement['Resource'], '*')
                        self.assertIn('iam:PassedToService', serialized(statement.get('Condition', {})))
                    if service == 's3':
                        self.assertNotIn(operation, ['putbucketpolicy', 'putbucketacl', '*'])
        self.assertEqual(len(deployment_roles), 1)

    def test_private_network_and_image_origin(self):
        network = self.templates['network']
        subnets = list(resources({'network': network}, 'AWS::EC2::Subnet'))
        self.assertEqual(len(subnets), 4)
        associations = {r['Properties']['SubnetId']['Ref']: r['Properties']['RouteTableId']['Ref']
                        for _, _, r in resources({'network': network}, 'AWS::EC2::SubnetRouteTableAssociation')}
        internet_tables = {r['Properties']['RouteTableId']['Ref']
                           for _, _, r in resources({'network': network}, 'AWS::EC2::Route')
                           if 'GatewayId' in r['Properties']}
        private = [name for _, name, r in subnets if associations.get(name) not in internet_tables]
        public = [name for _, name, r in subnets if associations.get(name) in internet_tables]
        self.assertEqual(len(private), 2)
        self.assertEqual(len(public), 2)
        for names in (private, public):
            zones = {serialized(network['Resources'][name]['Properties']['AvailabilityZone']) for name in names}
            self.assertEqual(len(zones), 2)
        private_output = serialized(network['Outputs']['DataSubnetIds'])
        public_output = serialized(network['Outputs']['PublicSubnetIds'])
        for name in private:
            self.assertIn(name, private_output)
            self.assertNotIn(name, public_output)
        for _, _, r in resources(self.templates, 'AWS::RDS::DBSubnetGroup'):
            self.assertEqual(r['Properties']['SubnetIds'], {'Ref': 'DataSubnetIds'})
        for _, _, r in resources(self.templates, 'AWS::ElastiCache::ServerlessCache'):
            self.assertEqual(r['Properties']['SubnetIds'], {'Ref': 'DataSubnetIds'})
        for _, _, route in resources({'network': network}, 'AWS::EC2::Route'):
            self.assertNotIn('NatGatewayId', route['Properties'])
        endpoints = list(resources({'runtime': self.templates['runtime']}, 'AWS::EC2::VPCEndpoint'))
        endpoint_services = {r['Properties']['ServiceName']['Fn::Sub'] for _, _, r in endpoints}
        self.assertEqual(endpoint_services, {'com.amazonaws.${AWS::Region}.ec2'})
        for _, _, endpoint in endpoints:
            self.assertEqual(endpoint['Properties']['SubnetIds'], {'Ref': 'PrivateSubnetIds'})
            policy = serialized(endpoint['Properties']['PolicyDocument'])
            self.assertIn('${ProjectName}-hook-execution', policy)
        hook_role = serialized(self.templates['runtime']['Resources']['HookExecutionRole'])
        self.assertIn('ec2:DescribeSubnets', hook_role)
        self.assertNotRegex((ROOT / 'runtime.yaml').read_text(), r'[&*]id[0-9]+')
        buckets = list(resources(self.templates, 'AWS::S3::Bucket'))
        self.assertGreaterEqual(len(buckets), 2)
        self.assertEqual(len(list(resources(self.templates, 'AWS::CloudFront::OriginAccessControl'))), 1)
        distributions = list(resources(self.templates, 'AWS::CloudFront::Distribution'))
        self.assertEqual(len(distributions), 1)
        origins = distributions[0][2]['Properties']['DistributionConfig']['Origins']
        self.assertEqual(len(origins), 1)
        self.assertIn('OriginAccessControlId', origins[0])
        origin_bucket = origins[0]['DomainName']['Fn::GetAtt'][0]
        allowed_buckets = []
        for _, _, policy in resources(self.templates, 'AWS::S3::BucketPolicy'):
            for statement in policy['Properties']['PolicyDocument']['Statement']:
                if statement.get('Effect') == 'Allow':
                    self.assertEqual(statement['Principal'], {'Service': 'cloudfront.amazonaws.com'})
                    self.assertEqual(statement['Action'], 's3:GetObject')
                    self.assertIn('AWS:SourceArn', serialized(statement['Condition']))
                    allowed_buckets.append(policy['Properties']['Bucket']['Ref'])
        self.assertEqual(allowed_buckets, [origin_bucket])


if __name__ == '__main__':
    unittest.main()
