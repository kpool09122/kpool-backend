"""Offline security/topology contracts beyond CloudFormation's resource schema."""
import copy
import ipaddress
import json
import re
from pathlib import Path
import unittest

from cfnlint.decode import decode

BASE = Path(__file__).resolve().parents[2] / 'infra' / 'cloudformation'


class FoundationTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        """Decode local templates once and reject malformed CloudFormation YAML."""
        cls.templates = {}
        for path in BASE.glob('*.yaml'):
            template, errors = decode(str(path))
            if errors:
                raise ValueError(errors)
            cls.templates[path.stem] = template

    def resources(self, stack, kind):
        """Select resources by AWS type suffix while preserving logical IDs."""
        return {
            key: value for key, value in self.templates[stack]['Resources'].items()
            if value['Type'] == 'AWS::' + kind
        }

    def test_nested_interfaces_and_outputs(self):
        root = self.templates['root']
        for resource in root['Resources'].values():
            child = self.templates[Path(resource['Properties']['TemplateURL']).stem]
            parameters = resource['Properties'].get('Parameters', {})
            self.assertEqual(set(parameters), set(child.get('Parameters', {})))
            self.assertNotIn('DeletionPolicy', resource)
            self.assertNotIn('UpdateReplacePolicy', resource)
        for output in root['Outputs'].values():
            stack, attribute = output['Value']['Fn::GetAtt']
            name = attribute.removeprefix('Outputs.')
            child = self.templates[Path(root['Resources'][stack]['Properties']['TemplateURL']).stem]
            self.assertIn(name, child['Outputs'])


    def test_subnets_fit_the_vpc_without_overlapping(self):
        subnets = self.resources('network', 'EC2::Subnet')
        cidrs = [ipaddress.ip_network(s['Properties']['CidrBlock']) for s in subnets.values()]
        vpc = ipaddress.ip_network(next(iter(self.resources('network', 'EC2::VPC').values()))['Properties']['CidrBlock'])
        for i, cidr in enumerate(cidrs):
            self.assertTrue(cidr.subnet_of(vpc))
            self.assertFalse(any(cidr.overlaps(other) for other in cidrs[i + 1:]))

    def assert_data_network_is_private(self, network):
        resources = network['Resources']
        data = {item['Ref'] for item in network['Outputs']['DataSubnetIds']['Value']['Fn::Join'][1]}
        public = {item['Ref'] for item in network['Outputs']['PublicSubnetIds']['Value']['Fn::Join'][1]}
        self.assertTrue(data)
        self.assertFalse(data & public, 'Data subnets must not be handed to public tasks/ALB')
        # RDS subnet groups and the ALB need multiple AZs, not a fixed subnet count.
        for names in (data, public):
            zones = {json.dumps(resources[name]['Properties']['AvailabilityZone'], sort_keys=True) for name in names}
            self.assertGreaterEqual(len(zones), 2)
        associations = {r['Properties']['SubnetId']['Ref']: r['Properties']['RouteTableId']['Ref']
                        for r in resources.values() if r['Type'] == 'AWS::EC2::SubnetRouteTableAssociation'}
        internet_tables = {r['Properties']['RouteTableId']['Ref'] for r in resources.values()
                           if r['Type'] == 'AWS::EC2::Route'
                           and ('GatewayId' in r['Properties'] or 'EgressOnlyInternetGatewayId' in r['Properties'])}
        gateways = {name for name, r in resources.items() if r['Type'] == 'AWS::EC2::InternetGateway'}
        public_tables = {r['Properties']['RouteTableId']['Ref'] for r in resources.values()
                         if r['Type'] == 'AWS::EC2::Route'
                         and r['Properties'].get('DestinationCidrBlock') == '0.0.0.0/0'
                         and r['Properties'].get('GatewayId', {}).get('Ref') in gateways}
        for name in data:
            self.assertIs(resources[name]['Properties'].get('MapPublicIpOnLaunch', False), False)
            self.assertIn(name, associations, 'Do not assume the implicit main route table is private')
            self.assertNotIn(associations[name], internet_tables, 'Data route table must not reach an internet gateway')
        for name in public:
            self.assertIn(associations[name], public_tables, 'Public IPv4 tasks need a default route to an internet gateway')

    def test_data_subnets_have_no_direct_internet_access(self):
        self.assert_data_network_is_private(self.templates['network'])
        for kind in ('RDS::DBSubnetGroup', 'ElastiCache::ServerlessCache'):
            for resource in self.resources('data', kind).values():
                self.assertEqual(resource['Properties']['SubnetIds'], {'Ref': 'DataSubnetIds'})

    def test_network_guard_rejects_public_data_and_accepts_nat_or_extra_subnets(self):
        for label, mutate in (
            ('automatic public IP', lambda n: n['Resources']['DataSubnetA']['Properties'].update(MapPublicIpOnLaunch=True)),
            ('public table', lambda n: n['Resources']['DataAssociationA']['Properties'].update(RouteTableId={'Ref': 'PublicRouteTable'})),
            ('implicit table', lambda n: n['Resources'].pop('DataAssociationA')),
            ('public output', lambda n: n['Outputs']['DataSubnetIds']['Value']['Fn::Join'][1].append({'Ref': 'PublicSubnetA'})),
            ('missing public default route', lambda n: n['Resources']['PublicDefaultRoute']['Properties'].update(DestinationCidrBlock='10.99.0.0/16')),
            ('internet route', lambda n: n['Resources'].update(DataInternetRoute={'Type': 'AWS::EC2::Route', 'Properties': {
                'RouteTableId': {'Ref': 'DataRouteTableA'}, 'DestinationCidrBlock': '0.0.0.0/0', 'GatewayId': {'Ref': 'InternetGateway'}}})),
            ('IPv6 internet route', lambda n: n['Resources'].update(DataInternetRoute={'Type': 'AWS::EC2::Route', 'Properties': {
                'RouteTableId': {'Ref': 'DataRouteTableA'}, 'DestinationIpv6CidrBlock': '::/0',
                'EgressOnlyInternetGatewayId': {'Ref': 'Ipv6Gateway'}}})),
        ):
            network = copy.deepcopy(self.templates['network'])
            mutate(network)
            with self.subTest(risk=label), self.assertRaises(AssertionError):
                self.assert_data_network_is_private(network)
        network = copy.deepcopy(self.templates['network'])
        network['Resources']['Nat'] = {'Type': 'AWS::EC2::NatGateway', 'Properties': {
            'SubnetId': {'Ref': 'PublicSubnetA'}, 'AllocationId': 'eipalloc-example'}}
        network['Resources']['DataNatRoute'] = {'Type': 'AWS::EC2::Route', 'Properties': {
            'RouteTableId': {'Ref': 'DataRouteTableA'}, 'DestinationCidrBlock': '0.0.0.0/0', 'NatGatewayId': {'Ref': 'Nat'}}}
        network['Resources']['ExtraPublicSubnet'] = copy.deepcopy(network['Resources']['PublicSubnetA'])
        network['Resources']['ExtraPublicSubnet']['Properties']['CidrBlock'] = '10.20.2.0/24'
        network['Resources']['ExtraPublicAssociation'] = {'Type': 'AWS::EC2::SubnetRouteTableAssociation', 'Properties': {
            'SubnetId': {'Ref': 'ExtraPublicSubnet'}, 'RouteTableId': {'Ref': 'PublicRouteTable'}}}
        network['Outputs']['PublicSubnetIds']['Value']['Fn::Join'][1].append({'Ref': 'ExtraPublicSubnet'})
        self.assert_data_network_is_private(network)

    def test_data_access_only_from_application_security_group(self):
        groups = self.resources('network', 'EC2::SecurityGroup')
        for name, ports in [('DatabaseSecurityGroup', (5432, 5432)), ('CacheSecurityGroup', (6379, 6380))]:
            ingress = groups[name]['Properties']['SecurityGroupIngress']
            self.assertEqual(len(ingress), 1)
            self.assertEqual(ingress[0], {
                'IpProtocol': 'tcp', 'FromPort': ports[0], 'ToPort': ports[1],
                'SourceSecurityGroupId': {'Ref': 'ApplicationSecurityGroup'},
            })
        self.assertFalse(groups['ApplicationSecurityGroup']['Properties'].get('SecurityGroupIngress'))

    def test_database_backup_tls_and_deletion_protection(self):
        database = self.resources('data', 'RDS::DBInstance')['Database']
        # Generic persistent-resource guards also allow Retain; RDS needs a final snapshot.
        self.assertEqual(database['DeletionPolicy'], 'Snapshot')
        self.assertEqual(database['UpdateReplacePolicy'], 'Snapshot')
        p = database['Properties']
        self.assertTrue(p['CopyTagsToSnapshot'])
        self.assertGreaterEqual(p['BackupRetentionPeriod'], 7)
        self.assertIs(p['DeleteAutomatedBackups'], False)
        self.assertEqual(p['DeletionProtection'], {'Ref': 'DatabaseDeletionProtection'})
        self.assertEqual(self.templates['root']['Parameters']['DatabaseDeletionProtection']['Default'], 'true')
        group = self.resources('data', 'RDS::DBParameterGroup')['DatabaseParameterGroup']
        self.assertEqual(group['Properties']['Parameters']['rds.force_ssl'], '1')

    def test_valkey_authentication_and_subnets(self):
        p = self.resources('data', 'ElastiCache::ServerlessCache')['Cache']['Properties']
        self.assertEqual(p['Engine'], 'valkey')
        self.assertEqual(p['SubnetIds'], {'Ref': 'DataSubnetIds'})
        self.assertEqual(p['SecurityGroupIds'], [{'Ref': 'CacheSecurityGroupId'}])
        self.assertEqual(p['UserGroupId'], {'Ref': 'CacheUserGroup'})
        self.assertGreaterEqual(p['SnapshotRetentionLimit'], 7)
        user = self.resources('data', 'ElastiCache::User')['CacheUser']['Properties']
        self.assertEqual(user['AuthenticationMode']['Type'], 'password')
        self.assertEqual(user['AuthenticationMode']['Passwords'], [
            {'Fn::Sub': '{{resolve:secretsmanager:${CacheSecret}:SecretString}}'},
        ])
        self.assertIn('GenerateSecretString', self.resources('data', 'SecretsManager::Secret')['CacheSecret']['Properties'])

    def test_object_version_recovery_and_tls_only_access(self):
        for bucket in self.resources('storage', 'S3::Bucket').values():
            self.assertEqual(bucket['Properties']['VersioningConfiguration']['Status'], 'Enabled')
        for policy in self.resources('storage', 'S3::BucketPolicy').values():
            statements = policy['Properties']['PolicyDocument']['Statement']
            self.assertTrue(any(s['Effect'] == 'Deny' and s.get('Condition') == {'Bool': {'aws:SecureTransport': 'false'}} for s in statements))

    def test_cloudfront_only_public_images_with_scoped_oac(self):
        distribution = self.resources('storage', 'CloudFront::Distribution')['ImageDistribution']['Properties']['DistributionConfig']
        origins = distribution['Origins']
        self.assertEqual(len(origins), 1)
        self.assertEqual(origins[0]['DomainName'], {'Fn::GetAtt': ['PublicImagesBucket', 'RegionalDomainName']})
        self.assertIn('OriginAccessControlId', origins[0])
        self.assertEqual(origins[0]['S3OriginConfig']['OriginAccessIdentity'], '')
        oac = self.resources('storage', 'CloudFront::OriginAccessControl')['ImageOriginAccessControl']['Properties']['OriginAccessControlConfig']
        self.assertEqual((oac['SigningBehavior'], oac['SigningProtocol']), ('always', 'sigv4'))
        policies = self.resources('storage', 'S3::BucketPolicy')
        allows = [s for s in policies['PublicImagesBucketPolicy']['Properties']['PolicyDocument']['Statement'] if s['Effect'] == 'Allow']
        self.assertEqual(len(allows), 1)
        self.assertEqual(allows[0]['Principal'], {'Service': 'cloudfront.amazonaws.com'})
        self.assertEqual(allows[0]['Action'], 's3:GetObject')
        self.assertIn('${ImageDistribution}', allows[0]['Condition']['StringEquals']['AWS:SourceArn']['Fn::Sub'])
        self.assertFalse(any(s['Effect'] == 'Allow' for s in policies['PrivateFilesBucketPolicy']['Properties']['PolicyDocument']['Statement']))

    def test_image_delivery_requires_custom_domain_and_tls12(self):
        """Require TLS 1.2 with an explicit hostname and a us-east-1 certificate."""
        distribution = self.resources('storage', 'CloudFront::Distribution')['ImageDistribution']['Properties']['DistributionConfig']
        self.assertEqual(distribution['Aliases'], [{'Ref': 'ImageDomainName'}])
        self.assertEqual(distribution['ViewerCertificate'], {
            'AcmCertificateArn': {'Ref': 'ImageCertificateArn'},
            'MinimumProtocolVersion': 'TLSv1.2_2021',
            'SslSupportMethod': 'sni-only',
        })
        self.assertEqual(distribution['DefaultCacheBehavior']['ViewerProtocolPolicy'], 'redirect-to-https')
        self.assertEqual(self.templates['storage']['Outputs']['ImageBaseUrl']['Value'], {
            'Fn::Sub': 'https://${ImageDomainName}',
        })
        storage_parameters = self.templates['root']['Resources']['Storage']['Properties']['Parameters']
        valid = {
            'ImageDomainName': 'images.example.com',
            'ImageCertificateArn': 'arn:aws:acm:us-east-1:123456789012:certificate/12345678-1234-1234-1234-123456789012',
        }
        invalid = {
            'ImageDomainName': ['', 'https://images.example.com', '*.example.com', 'images', '-images.example.com'],
            'ImageCertificateArn': ['', valid['ImageCertificateArn'].replace('us-east-1', 'ap-northeast-1')],
        }
        for name, value in valid.items():
            self.assertEqual(storage_parameters[name], {'Ref': name})
            for stack in ['root', 'storage']:
                spec = self.templates[stack]['Parameters'][name]
                self.assertNotIn('Default', spec)
                self.assertIsNotNone(re.fullmatch(spec['AllowedPattern'], value))
                for rejected in invalid[name]:
                    self.assertIsNone(re.fullmatch(spec['AllowedPattern'], rejected))

    def test_queue_redrive_encryption_and_retention(self):
        queues = self.resources('storage', 'SQS::Queue')
        queue = queues['Queue']['Properties']
        dlq = queues['DeadLetterQueue']['Properties']
        for resource in queues.values():
            self.assertIs(resource['Properties']['SqsManagedSseEnabled'], True)
        self.assertEqual(queue['RedrivePolicy']['deadLetterTargetArn'], {'Fn::GetAtt': ['DeadLetterQueue', 'Arn']})
        self.assertGreater(dlq['MessageRetentionPeriod'], queue['MessageRetentionPeriod'])
        self.assertEqual(dlq['RedriveAllowPolicy']['redrivePermission'], 'byQueue')
        self.assertEqual(queue['QueueName'], {'Ref': 'WorkQueueName'})
        self.assertIn('${WorkQueueName}', dlq['RedriveAllowPolicy']['sourceQueueArns'][0]['Fn::Sub'])


if __name__ == '__main__':
    unittest.main()
