"""Validate real AWS request shapes without network/credentials; model from cfn-lint env."""
import ast
import unittest
from pathlib import Path
import botocore.session
from botocore.validate import validate_parameters

class AwsModelTests(unittest.TestCase):
    def test_every_runtime_aws_operation_uses_actual_model_member_casing(self):
        session=botocore.session.get_session()
        files=['aws_backend.py','cli.py']
        count=0
        for filename in files:
            tree=ast.parse((Path(__file__).parent/filename).read_text())
            for node in ast.walk(tree):
                if not isinstance(node,ast.Call) or len(node.args)<2: continue
                if not all(isinstance(arg,ast.Constant) and isinstance(arg.value,str) for arg in node.args[:2]): continue
                service,operation=[arg.value for arg in node.args[:2]]
                if service not in ('ecs','ecr','sqs','cloudwatch','lambda','ssm','sts','cloudformation'): continue
                model=session.get_service_model(service).operation_model(''.join(part.capitalize() for part in operation.split('-')))
                for keyword in node.keywords:
                    if keyword.arg: self.assertIn(keyword.arg,model.input_shape.members,f'{service}/{operation}: {keyword.arg}')
                count+=1
        self.assertGreaterEqual(count,20)
    def test_sqs_cli_json_uses_pascal_case_and_ecs_uses_lower_camel_case(self):
        session=botocore.session.get_session()
        validate_parameters({'QueueUrl':'https://sqs.ap-northeast-1.amazonaws.com/123456789012/fixture','AttributeNames':['QueueArn']},session.get_service_model('sqs').operation_model('GetQueueAttributes').input_shape)
        validate_parameters({'cluster':'fixture','service':'fixture','taskDefinition':'fixture','desiredCount':1,'forceNewDeployment':True},session.get_service_model('ecs').operation_model('UpdateService').input_shape)
