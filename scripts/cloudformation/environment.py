"""Resolve nonsecret operator inputs and live cross-stack dependencies."""
import json
from pathlib import Path
import re

from cfnlint.decode import decode

RUNTIME_STATE = frozenset({
    'ApiTaskDefinitionArn', 'WorkerTaskDefinitionArn', 'SchedulerTaskDefinitionArn',
    'ApiDesiredCount', 'WorkerDesiredCount', 'SchedulerState', 'PrimaryTargetGroup',
    'ProductionTargetGroup', 'TestTargetGroup',
})


def load_contract(root):
    contract = json.loads((root / 'contracts.json').read_text())
    templates = {}
    for stack in contract['deploymentOrder']:
        template, errors = decode(str(root / f'{stack}.yaml'))
        if errors:
            raise ValueError(f'Invalid template: {stack}')
        templates[stack] = template
    return templates, contract


def validate_config(config, templates, contract):
    required = {'accountId', 'region', 'projectName', 'resourcePrefix', 'packageBucket', 'stacks', 'inputs'}
    if not isinstance(config, dict) or set(config) != required:
        raise ValueError('Environment keys must match the example schema')
    if (not all(isinstance(config[key], str) for key in required - {'stacks', 'inputs'})
            or not isinstance(config['stacks'], dict) or not isinstance(config['inputs'], dict)
            or not all(isinstance(values, dict) for values in config['inputs'].values())
            or not all(isinstance(value, str) for value in config['stacks'].values())):
        raise ValueError('Environment values must follow the example types')
    validate_value('root', 'ResourcePrefix', templates['root']['Parameters']['ResourcePrefix'], config['resourcePrefix'])
    validate_value('bootstrap', 'ProjectName', templates['bootstrap']['Parameters']['ProjectName'], config['projectName'])
    if not re.fullmatch(r'[0-9]{12}', config['accountId']):
        raise ValueError('accountId must have 12 digits')
    if config['region'] != contract['region']:
        raise ValueError('Unsupported region')
    if set(config['stacks']) != set(contract['deploymentOrder']):
        raise ValueError('Configure exactly the parent stacks')
    if len(set(config['stacks'].values())) != len(config['stacks']):
        raise ValueError('Stack names must be distinct')
    if len(config['stacks']['root']) > 20:
        raise ValueError('Foundation stack name must fit generated resource names (20 characters)')
    for name in config['stacks'].values():
        if not re.fullmatch(r'[a-z][a-z0-9-]{0,127}', name):
            raise ValueError('Stack names must use lowercase letters, digits and hyphens')
    for stack in ('bootstrap', 'integration', 'runtime'):
        if config['stacks'][stack] != f"{config['projectName']}-{stack}":
            raise ValueError('Stack names must match the scoped bootstrap IAM policy')
    if not re.fullmatch(r'[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]', config['packageBucket']):
        raise ValueError('Invalid package bucket name')
    if set(config['inputs']) - set(templates):
        raise ValueError('Unknown input stack')
    derived = {(link['to'], link['parameter']) for link in contract['links']}
    derived.update((stack, 'ProjectName') for stack in templates)
    derived.update({('root', 'ResourcePrefix'), ('bootstrap', 'FoundationStackName'),
                    ('bootstrap', 'FoundationTemplateBucketName'), ('bootstrap', 'FoundationWorkQueueName'),
                    ('bootstrap', 'CertificateHostedZoneId'), ('bootstrap', 'HookArtifactObjectArn')})
    for stack, template in templates.items():
        for name, definition in template['Parameters'].items():
            if ('Default' not in definition and (stack, name) not in derived
                    and name not in config['inputs'].get(stack, {})):
                raise ValueError(f'Missing operator input: {stack}.{name}')
    for stack, values in config['inputs'].items():
        for name, value in values.items():
            definition = templates[stack]['Parameters'].get(name)
            if definition is None:
                raise ValueError(f'Unknown input: {stack}.{name}')
            if (stack, name) in derived or (stack == 'runtime' and name in RUNTIME_STATE):
                raise ValueError(f'Derived input cannot be overridden: {stack}.{name}')
            if definition.get('NoEcho') or re.search(r'PASSWORD|TOKEN|APP_KEY|SECRET_VALUE', name, re.I):
                raise ValueError('Only secret ARNs, never secret values, are accepted')
            if not isinstance(value, str):
                raise ValueError(f'Input must be a string: {stack}.{name}')
            validate_value(stack, name, definition, value)


def validate_value(stack, name, definition, value):
    if definition.get('NoEcho'):
        raise ValueError('NoEcho inputs are not supported by the nonsecret planner')
    if 'AllowedValues' in definition and value not in list(map(str, definition['AllowedValues'])):
        raise ValueError(f'Invalid allowed value: {stack}.{name}')
    if 'AllowedPattern' in definition and not re.fullmatch(definition['AllowedPattern'], value):
        raise ValueError(f'Invalid pattern: {stack}.{name}')
    if 'MinLength' in definition and len(value) < definition['MinLength']:
        raise ValueError(f'Input too short: {stack}.{name}')
    if 'MaxLength' in definition and len(value) > definition['MaxLength']:
        raise ValueError(f'Input too long: {stack}.{name}')
    if definition['Type'] == 'Number':
        number = float(value)
        if not (definition.get('MinValue', float('-inf')) <= number <= definition.get('MaxValue', float('inf'))):
            raise ValueError(f'Number out of range: {stack}.{name}')


def parameters(config, stack, templates, contract, outputs, previous=None, previous_stacks=None):
    validate_config(config, templates, contract)
    definitions = templates[stack].get('Parameters', {})
    values = {name: str(item['Default']) for name, item in definitions.items() if 'Default' in item}
    values.update({name: value for name, value in (previous or {}).items() if name in definitions})
    values.update(config['inputs'].get(stack, {}))
    if 'ProjectName' in definitions:
        values['ProjectName'] = config['projectName']
    if stack == 'root':
        values['ResourcePrefix'] = config['resourcePrefix']
    if stack == 'bootstrap':
        root_values = {name: str(item['Default']) for name, item in templates['root']['Parameters'].items()
                       if 'Default' in item}
        root_values.update((previous_stacks or {}).get('root', {}))
        root_values.update(config['inputs'].get('root', {}))
        runtime = {**(previous_stacks or {}).get('runtime', {}), **config['inputs'].get('runtime', {})}
        values.update(FoundationStackName=config['stacks']['root'],
                      FoundationTemplateBucketName=config['packageBucket'],
                      FoundationWorkQueueName=root_values['WorkQueueName'],
                      CertificateHostedZoneId=runtime.get('HostedZoneId', ''))
        bucket, key = runtime.get('HookArtifactBucket', ''), runtime.get('HookArtifactKey', '')
        values['HookArtifactObjectArn'] = f'arn:aws:s3:::{bucket}/{key}' if bucket and key else ''
    for link in contract['links']:
        if link['to'] != stack:
            continue
        value = outputs.get(link['from'], {}).get(link['output'])
        if not value:
            raise ValueError(f"Missing live Output: {link['from']}.{link['output']}")
        values[link['parameter']] = value
    for name, definition in definitions.items():
        if name not in values:
            raise ValueError(f'Missing input: {stack}.{name}')
        value = values[name]
        validate_value(stack, name, definition, value)
    return values
