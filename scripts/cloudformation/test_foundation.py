"""Offline security/topology contracts beyond CloudFormation's resource schema."""
import ipaddress
import json
from pathlib import Path
import unittest

from cfnlint.decode import decode

BASE = Path(__file__).resolve().parents[2] / 'infra' / 'cloudformation'


class FoundationTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.templates = {}
        for path in BASE.glob('*.yaml'):
            template, errors = decode(str(path))
            if errors:
                raise ValueError(errors)
            cls.templates[path.stem] = template

    def resources(self, stack, kind):
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
            self.assertEqual(resource['DeletionPolicy'], 'Retain')
            self.assertEqual(resource['UpdateReplacePolicy'], 'Retain')
        for output in root['Outputs'].values():
            stack, attribute = output['Value']['Fn::GetAtt']
            name = attribute.removeprefix('Outputs.')
            child = self.templates[Path(root['Resources'][stack]['Properties']['TemplateURL']).stem]
            self.assertIn(name, child['Outputs'])
        config = json.loads((BASE / 'parameters.production.json').read_text())
        self.assertEqual({p['ParameterKey'] for p in config}, set(root['Parameters']))
        for param in config:
            spec = root['Parameters'][param['ParameterKey']]
            if 'AllowedValues' in spec:
                self.assertIn(param['ParameterValue'], spec['AllowedValues'])

    def test_two_az_nonoverlapping_subnets(self):
        subnets = self.resources('network', 'EC2::Subnet')
        self.assertEqual(len(subnets), 4)
        cidrs = [ipaddress.ip_network(s['Properties']['CidrBlock']) for s in subnets.values()]
        vpc = ipaddress.ip_network(next(iter(self.resources('network', 'EC2::VPC').values()))['Properties']['CidrBlock'])
        for i, cidr in enumerate(cidrs):
            self.assertTrue(cidr.subnet_of(vpc))
            self.assertFalse(any(cidr.overlaps(other) for other in cidrs[i + 1:]))
        for prefix in ['Public', 'Data']:
            azs = [s['Properties']['AvailabilityZone'] for key, s in subnets.items() if key.startswith(prefix)]
            self.assertEqual(len(azs), 2)
            self.assertNotEqual(azs[0], azs[1])
        self.assertTrue(all(not s['Properties']['MapPublicIpOnLaunch'] for s in subnets.values()))

    def test_private_routes_and_no_nat(self):
        self.assertFalse(self.resources('network', 'EC2::NatGateway'))
        routes = self.resources('network', 'EC2::Route')
        self.assertEqual(len(routes), 1)
        self.assertEqual(next(iter(routes.values()))['Properties']['RouteTableId'], {'Ref': 'PublicRouteTable'})
        associations = self.resources('network', 'EC2::SubnetRouteTableAssociation')
        self.assertEqual(len(associations), 4)
        for resource in associations.values():
            p = resource['Properties']
            if p['SubnetId']['Ref'].startswith('Data'):
                self.assertTrue(p['RouteTableId']['Ref'].startswith('Data'))

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

    def test_database_protection_and_defaults(self):
        db = self.resources('data', 'RDS::DBInstance')['Database']
        p = db['Properties']
        self.assertEqual(db['DeletionPolicy'], 'Snapshot')
        self.assertEqual(db['UpdateReplacePolicy'], 'Snapshot')
        for key in ['StorageEncrypted', 'ManageMasterUserPassword', 'CopyTagsToSnapshot']:
            self.assertIs(p[key], True)
        self.assertIs(p['PubliclyAccessible'], False)
        self.assertNotIn('MasterUserPassword', p)
        self.assertEqual((p['StorageType'], p['AllocatedStorage']), ('gp3', '20'))
        self.assertGreaterEqual(p['BackupRetentionPeriod'], 7)
        params = self.templates['root']['Parameters']
        self.assertEqual(params['DatabaseInstanceClass']['Default'], 'db.t4g.micro')
        self.assertEqual(params['DatabaseMultiAZ']['Default'], 'false')
        self.assertEqual(params['DatabaseDeletionProtection']['Default'], 'true')

    def test_valkey_authentication_and_subnets(self):
        p = self.resources('data', 'ElastiCache::ServerlessCache')['Cache']['Properties']
        self.assertEqual(p['Engine'], 'valkey')
        self.assertEqual(p['SubnetIds'], {'Ref': 'DataSubnetIds'})
        self.assertEqual(p['SecurityGroupIds'], [{'Ref': 'CacheSecurityGroupId'}])
        self.assertEqual(p['UserGroupId'], {'Ref': 'CacheUserGroup'})
        user = self.resources('data', 'ElastiCache::User')['CacheUser']['Properties']
        self.assertEqual(user['AuthenticationMode']['Type'], 'password')
        self.assertIn('resolve:secretsmanager:', str(user['AuthenticationMode']['Passwords']))
        self.assertIn('GenerateSecretString', self.resources('data', 'SecretsManager::Secret')['CacheSecret']['Properties'])

    def test_buckets_private_encrypted_versioned_and_retained(self):
        buckets = self.resources('storage', 'S3::Bucket')
        self.assertEqual(len(buckets), 2)
        for bucket in buckets.values():
            self.assertEqual(bucket['DeletionPolicy'], 'Retain')
            self.assertEqual(bucket['UpdateReplacePolicy'], 'Retain')
            p = bucket['Properties']
            self.assertTrue(all(p['PublicAccessBlockConfiguration'].values()))
            self.assertEqual(p['VersioningConfiguration']['Status'], 'Enabled')
            self.assertIn('BucketEncryption', p)
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
        self.assertIn('${ImageDistribution}', allows[0]['Condition']['StringEquals']['AWS:SourceArn']['Fn::Sub'])
        self.assertFalse(any(s['Effect'] == 'Allow' for s in policies['PrivateFilesBucketPolicy']['Properties']['PolicyDocument']['Statement']))

    def test_queue_redrive_encryption_and_retention(self):
        queues = self.resources('storage', 'SQS::Queue')
        queue = queues['Queue']['Properties']
        dlq = queues['DeadLetterQueue']['Properties']
        for resource in queues.values():
            self.assertIs(resource['Properties']['SqsManagedSseEnabled'], True)
            self.assertEqual(resource['DeletionPolicy'], 'Retain')
        self.assertEqual(queue['RedrivePolicy']['deadLetterTargetArn'], {'Fn::GetAtt': ['DeadLetterQueue', 'Arn']})
        self.assertGreater(dlq['MessageRetentionPeriod'], queue['MessageRetentionPeriod'])
        self.assertEqual(dlq['RedriveAllowPolicy']['redrivePermission'], 'byQueue')
        self.assertIn(queue['QueueName']['Fn::Sub'], dlq['RedriveAllowPolicy']['sourceQueueArns'][0]['Fn::Sub'])


if __name__ == '__main__':
    unittest.main()
