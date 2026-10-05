"""Release read-only permissions and writable production paths."""
import unittest
from pathlib import Path
from cfnlint.decode import decode

BASE=Path(__file__).resolve().parents[2]

class ReleasePermissionsTest(unittest.TestCase):
    def test_release_observation_permissions_are_scoped_and_never_read_secret_values(self):
        template,errors=decode(str(BASE/'infra/cloudformation/bootstrap.yaml'))
        self.assertFalse(errors)
        statements=template['Resources']['DeploymentRole']['Properties']['Policies'][0]['PolicyDocument']['Statement']
        by_action={a:s for s in statements for a in (s['Action'] if isinstance(s['Action'],list) else [s['Action']])}
        for action in ('ssm:GetParameter','cloudwatch:DescribeAlarms','lambda:GetFunctionConfiguration','sqs:GetQueueAttributes'):
            self.assertIn(action,by_action)
            self.assertNotEqual(by_action[action]['Resource'],'*')
        self.assertNotIn('secretsmanager:GetSecretValue',by_action)
        self.assertNotIn('ssm:GetParametersByPath',by_action)

    def test_bootstrap_templates_provide_writable_paths_for_readonly_root(self):
        template,errors=decode(str(BASE/'infra/cloudformation/runtime.yaml'))
        self.assertFalse(errors)
        for kind in ('Api','Worker','Migration','Scheduler'):
            props=template['Resources'][kind+'BootstrapTaskDefinition']['Properties']
            container=props['ContainerDefinitions'][0]
            self.assertTrue(container['ReadonlyRootFilesystem'])
            self.assertEqual({m['ContainerPath'] for m in container['MountPoints']},
                             {'/tmp','/var/www/html/storage','/var/www/html/bootstrap/cache'})
            self.assertEqual({v['Name'] for v in props['Volumes']},{m['SourceVolume'] for m in container['MountPoints']})
        dockerfile=(BASE/'Dockerfile').read_text().split('FROM runtime AS production',1)[1].split('FROM runtime AS development',1)[0]
        self.assertIn('VOLUME ["/tmp", "/var/www/html/storage", "/var/www/html/bootstrap/cache"]',dockerfile)
