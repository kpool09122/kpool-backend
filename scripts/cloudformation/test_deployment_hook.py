"""Execute the bundled Lambda with fake EC2/HTTPS boundaries, never AWS."""
import importlib.util
import os
import ssl
import unittest
from unittest.mock import MagicMock, patch

from cfnlint.decode import decode
from test_contracts import ROOT


class HookTests(unittest.TestCase):
    def setUp(self):
        template, errors = decode(str(ROOT / 'runtime.yaml'))
        self.assertFalse(errors)
        source = template['Resources']['LifecycleHook']['Properties']['Code']['Fn::If'][2]['ZipFile']
        self.module = importlib.util.module_from_spec(importlib.util.spec_from_loader('bundled_hook', loader=None))
        exec(compile(source, 'bundled_hook', 'exec'), self.module.__dict__)
        self.environment = dict(API_HOST='api.example.invalid', HEALTH_PATH='/health', TEST_PORT='8443',
                                VPC_ID='vpc-test', ALB_ARN='arn:aws:elasticloadbalancing:ap-northeast-1:123:loadbalancer/app/test/123')
        self.env = patch.dict(os.environ, self.environment)
        self.env.start()
        self.addCleanup(self.env.stop)
        self.client = MagicMock()
        self.client.get_paginator.return_value.paginate.return_value = [
            {'NetworkInterfaces': [{'PrivateIpAddress': '10.20.0.5'}]},
            {'NetworkInterfaces': [{'PrivateIpAddress': '10.20.1.5'}]},
        ]
        self.connection = MagicMock()
        self.connection.getresponse.return_value.status = 200

    def invoke(self, event=None):
        with patch.object(self.module.boto3, 'client', return_value=self.client), \
                patch.object(self.module, 'PrivateHttpsConnection', return_value=self.connection) as connect:
            result = self.module.handler(event or {'lifecycleStage': 'POST_TEST_TRAFFIC_SHIFT'}, None)
        return result, connect

    def test_all_private_alb_addresses_are_checked_with_deployment_host(self):
        result, connect = self.invoke({'lifecycleStage': 'POST_TEST_TRAFFIC_SHIFT',
                                       'hookDetails': {'expectedHost': 'attacker.invalid', 'healthPath': '/other'}})
        self.assertEqual(result, {'hookStatus': 'SUCCEEDED'})
        self.assertEqual([c.args for c in connect.call_args_list], [
            ('api.example.invalid', '10.20.0.5', 8443), ('api.example.invalid', '10.20.1.5', 8443)])
        self.connection.request.assert_called_with('GET', '/health', headers={'Host': 'api.example.invalid'})
        filters = self.client.get_paginator.return_value.paginate.call_args.kwargs['Filters']
        self.assertIn({'Name': 'vpc-id', 'Values': ['vpc-test']}, filters)
        self.assertIn({'Name': 'description', 'Values': ['ELB app/test/123']}, filters)

    def test_http_error_redirect_discovery_error_or_missing_alb_fails(self):
        for status in (301, 403, 500):
            self.connection.getresponse.return_value.status = status
            self.assertEqual(self.invoke()[0], {'hookStatus': 'FAILED'})
        self.connection.getresponse.return_value.status = 200
        self.client.get_paginator.return_value.paginate.return_value = []
        self.assertEqual(self.invoke()[0], {'hookStatus': 'FAILED'})
        self.client.get_paginator.side_effect = RuntimeError('secret-response-never-log')
        with patch('builtins.print') as log:
            self.assertEqual(self.invoke()[0], {'hookStatus': 'FAILED'})
        self.assertNotIn('secret-response-never-log', repr(log.call_args_list))
        self.assertEqual(self.invoke({'lifecycleStage': 'PRE_SCALE_UP'})[0], {'hookStatus': 'FAILED'})

    def test_tls_uses_api_hostname_verification_and_closes_failed_socket(self):
        connection = self.module.PrivateHttpsConnection('api.example.invalid', '10.20.0.5', 8443)
        self.assertTrue(connection._context.check_hostname)
        self.assertEqual(connection._context.verify_mode, ssl.CERT_REQUIRED)
        transport = MagicMock()
        tls = MagicMock()
        connection._context = tls
        with patch.object(self.module.socket, 'create_connection', return_value=transport):
            connection.connect()
        tls.wrap_socket.assert_called_once_with(transport, server_hostname='api.example.invalid')
        tls.wrap_socket.side_effect = ssl.SSLCertVerificationError('invalid certificate')
        with patch.object(self.module.socket, 'create_connection', return_value=transport), self.assertRaises(ssl.SSLCertVerificationError):
            connection.connect()
        transport.close.assert_called_once()


if __name__ == '__main__':
    unittest.main()
