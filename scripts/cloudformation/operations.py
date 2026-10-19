"""Reviewed CloudFormation changes with live inputs and fail-closed state guards.

No AWS mutations happen on import. The CLI requires an explicit command and plan.
"""
import argparse
import copy
import json
import os
from pathlib import Path
import subprocess
import sys
import uuid
from urllib.parse import quote
from cfnlint.decode import decode

from environment import RUNTIME_STATE, load_contract, parameters, validate_config
from runtime_state import resolve

ACTIVE_DEPLOYMENTS = ('PENDING', 'IN_PROGRESS', 'STOP_REQUESTED', 'ROLLBACK_REQUESTED', 'ROLLBACK_IN_PROGRESS')
STABLE_STACKS = {'CREATE_COMPLETE', 'UPDATE_COMPLETE', 'UPDATE_ROLLBACK_COMPLETE', 'IMPORT_COMPLETE', 'IMPORT_ROLLBACK_COMPLETE'}


class AwsError(RuntimeError):
    def __init__(self, service, operation, detail):
        super().__init__(f'AWS {service} {operation} failed; inspect credentials, permissions and AWS events')
        self.detail = detail


class Aws:
    def __init__(self, region):
        self.region = region

    def __call__(self, service, operation, *args):
        command = ['aws', '--region', self.region, '--no-cli-pager', '--output', 'json', service, operation, *args]
        result = subprocess.run(command, capture_output=True, text=True, check=False)
        if result.returncode:
            # Do not echo SDK errors or requests which could contain sensitive input.
            raise AwsError(service, operation, result.stderr)
        if operation == 'wait':
            return {}
        return json.loads(result.stdout) if result.stdout.strip() else {}


def read_stack(aws, name):
    try:
        stack = aws('cloudformation', 'describe-stacks', '--stack-name', name)['Stacks'][0]
    except AwsError as error:
        if 'ValidationError' in error.detail and f'Stack with id {name} does not exist' in error.detail:
            return None
        raise
    if stack['StackStatus'] not in STABLE_STACKS | {'REVIEW_IN_PROGRESS'}:
        raise ValueError(f'Stack not ready: {name}')
    return {key: stack.get(key) for key in ('StackId', 'StackStatus', 'CreationTime', 'LastUpdatedTime',
                                          'Outputs', 'Parameters', 'RoleARN', 'EnableTerminationProtection')}


def output_values(stack):
    return {item['OutputKey']: item['OutputValue'] for item in (stack or {}).get('Outputs') or []}


def runtime_parameters_for_update(live, outputs):
    """Keep empty bootstrap inputs distinct from deployed release revisions."""
    values = dict(live)
    for kind, state, inactive in (('Api', 'ApiDesiredCount', '0'),
                                   ('Worker', 'WorkerDesiredCount', '0'),
                                   ('Scheduler', 'SchedulerState', 'DISABLED')):
        name = kind + 'TaskDefinitionArn'
        if (values.get(state) == inactive
                and values.get(name) == outputs.get(kind + 'BootstrapTaskDefinitionArn')):
            values[name] = ''
    return values


def service_deployment_history(aws, cluster, service):
    history, tokens = [], set()
    token = None
    while True:
        args = ['--cluster', cluster, '--service', service, '--max-results', '100', '--no-paginate']
        if token:
            args.extend(['--next-token', token])
        page = aws('ecs', 'list-service-deployments', *args)
        history.extend(page.get('serviceDeployments', []))
        token = page.get('nextToken')
        if not token:
            return history
        if token in tokens:
            raise ValueError('Repeated native deployment pagination token; refusing incomplete inventory')
        tokens.add(token)


def runtime_snapshot(aws, outputs):
    if not outputs:
        return None
    services = aws('ecs', 'describe-services', '--cluster', outputs['ClusterArn'], '--services',
                   outputs['ApiServiceName'], outputs['WorkerServiceName'])
    native_history = {}
    for service in services['services']:
        history = service_deployment_history(aws, outputs['ClusterArn'], service['serviceArn'])
        if any(item['status'] in ACTIVE_DEPLOYMENTS for item in history):
            raise ValueError('Active deployment: keep release paused and retry after completion')
        native_history[service['serviceName']] = sorted(
            [{key: item.get(key) for key in ('serviceDeploymentArn', 'status', 'createdAt', 'updatedAt')}
             for item in history], key=lambda item: item['serviceDeploymentArn'])
    rules = aws('elbv2', 'describe-rules', '--rule-arns', outputs['ProductionRuleArn'], outputs['TestRuleArn'])
    arn_parts = outputs['SchedulerArn'].split(':schedule/', 1)[1].split('/')
    if len(arn_parts) != 2:
        raise ValueError('Unexpected Scheduler ARN')
    schedule = aws('scheduler', 'get-schedule', '--group-name', arn_parts[0], '--name', arn_parts[1])
    return {'parameters': resolve(outputs, services, rules, schedule),
            'nativeHistory': native_history,
            # Track deployment identity too, so an ABA revision change is not silently accepted.
            'deployments': {service['serviceName']: [item.get('id') for item in service['deployments']]
                            for service in services['services']},
            'schedulerModified': schedule.get('LastModificationDate')}


def capture(aws, config, paused, allow_created_runtime=False):
    if aws('sts', 'get-caller-identity')['Account'] != config['accountId']:
        raise ValueError('AWS account mismatch; no change is permitted')
    stacks = {key: read_stack(aws, name) for key, name in config['stacks'].items()}
    if stacks['runtime'] and not paused and not allow_created_runtime:
        raise ValueError('Pause releases first and explicitly acknowledge --release-paused')
    runtime = runtime_snapshot(aws, output_values(stacks['runtime']))
    return {'stacks': stacks, 'runtime': runtime}


def change_set(aws, arn, root_arn=None, parent_arn=None):
    change = aws('cloudformation', 'describe-change-set', '--change-set-name', arn)
    execution_status = 'UNAVAILABLE' if parent_arn else 'AVAILABLE'
    if parent_arn and (change.get('RootChangeSetId') != root_arn or change.get('ParentChangeSetId') != parent_arn):
        raise ValueError('Nested change set hierarchy does not match the reviewed root')
    if change['Status'] != 'CREATE_COMPLETE' or change['ExecutionStatus'] != execution_status:
        raise ValueError('Change set is not executable (including no-change and already-executed plans)')
    for item in change.get('Changes', []):
        resource = item['ResourceChange']
        if resource['Action'] == 'Remove' or resource.get('Replacement') in {'True', 'Conditional'}:
            raise ValueError('Deletion/replacement requires a separate migration plan; automatic execution refused')
        if resource.get('ChangeSetId'):
            change_set(aws, resource['ChangeSetId'], root_arn or arn, arn)
    return change


def validate_bootstrap_permissions(config, stack, templates, contract, previous_stacks):
    """Reject dependent plans whose scoped permissions need a bootstrap update."""
    if stack not in ('root', 'runtime'):
        return
    live = previous_stacks['bootstrap']
    wanted = parameters(config, 'bootstrap', templates, contract, {}, live, previous_stacks)
    if stack == 'root':
        queue = wanted['FoundationWorkQueueName']
        covered = any(live.get(prefix) and queue.startswith(live[prefix] + '-')
                      for prefix in ('ProjectName', 'FoundationStackName'))
        names = () if covered else ('FoundationWorkQueueName',)
    else:
        names = ('CertificateHostedZoneId', 'HookArtifactObjectArn', 'MailIdentityDomain')
    for name in names:
        if live.get(name, '') != wanted[name]:
            raise ValueError(f'Bootstrap is stale ({name}); plan and apply bootstrap first')


def upload_template(aws, config, path, key):
    uploaded = aws('s3api', 'put-object', '--bucket', config['packageBucket'], '--key', key,
                   '--body', str(path), '--content-type', 'application/json',
                   '--expected-bucket-owner', config['accountId'])
    version = uploaded.get('VersionId')
    if not version or version == 'null':
        raise ValueError('Package bucket versioning must be enabled')
    return (f"https://{config['packageBucket']}.s3.{config['region']}.amazonaws.com/{key}"
            + '?versionId=' + quote(version, safe=''))


def package_nested_templates(aws, config, root, directory, prefix, template):
    """Upload JSON children so YAML 1.2 -> 1.1 conversion cannot change scalars."""
    packaged = copy.deepcopy(template)
    for resource in packaged.get('Resources', {}).values():
        if resource['Type'] != 'AWS::CloudFormation::Stack':
            continue
        reference = resource.get('Properties', {}).get('TemplateURL')
        if not isinstance(reference, str) or reference.startswith('https://'):
            continue
        path = (root / reference).resolve()
        # These foundation children are local files in this template directory.
        if path.parent != root.resolve() or path.suffix != '.yaml' or not path.is_file():
            raise ValueError('Nested foundation templates must be local YAML files in the template directory')
        child, errors = decode(str(path))
        if errors:
            raise ValueError('Invalid nested template: ' + path.name)
        if any(item['Type'] == 'AWS::CloudFormation::Stack' for item in child.get('Resources', {}).values()):
            raise ValueError('Foundation children must not contain further nested stacks')
        rendered = directory / (path.stem + '.rendered.json')
        rendered.write_text(json.dumps(child, separators=(',', ':')))
        resource['Properties']['TemplateURL'] = upload_template(
            aws, config, rendered, prefix + '/' + path.stem + '.json')
    return packaged


def prepare(aws, config, stack, root, directory, paused):
    templates, contract = load_contract(root)
    validate_config(config, templates, contract)
    before = capture(aws, config, paused)
    existing = before['stacks'][stack]
    if existing and existing['StackStatus'] == 'REVIEW_IN_PROGRESS':
        raise ValueError('Unexecuted CREATE change set exists; inspect/delete it before replanning')
    for predecessor in contract['deploymentOrder'][:contract['deploymentOrder'].index(stack)]:
        if not before['stacks'][predecessor] or before['stacks'][predecessor]['StackStatus'] not in STABLE_STACKS:
            raise ValueError(f'Apply predecessor first: {predecessor}')
    outputs = {key: output_values(value) for key, value in before['stacks'].items()}
    previous = {item['ParameterKey']: item['ParameterValue'] for item in (existing or {}).get('Parameters') or []}
    previous_stacks = {key: {item['ParameterKey']: item['ParameterValue']
                             for item in (value or {}).get('Parameters') or []}
                       for key, value in before['stacks'].items()}
    validate_bootstrap_permissions(config, stack, templates, contract, previous_stacks)
    values = parameters(config, stack, templates, contract, outputs, previous, previous_stacks)
    for name in ('ProjectName', 'ResourcePrefix'):
        if name in previous and values.get(name) != previous[name]:
            raise ValueError('Immutable naming input changed; use an explicit migration instead')
    if stack == 'runtime' and before['runtime']:
        values.update(runtime_parameters_for_update(before['runtime']['parameters'], outputs['runtime']))
    name = 'infrastructure-' + uuid.uuid4().hex
    rendered_template = templates[stack]
    if stack == 'root':
        aws('s3api', 'head-bucket', '--bucket', config['packageBucket'], '--expected-bucket-owner', config['accountId'])
        location = aws('s3api', 'get-bucket-location', '--bucket', config['packageBucket'],
                       '--expected-bucket-owner', config['accountId'])
        if location.get('LocationConstraint') != config['region']:
            raise ValueError('Package bucket must be in the deployment region')
        rendered_template = package_nested_templates(aws, config, root, directory, name, rendered_template)
    # JSON preserves times, boolean-looking strings and inline source exactly.
    template = directory / f'{stack}.rendered.json'
    template.write_text(json.dumps(rendered_template, separators=(',', ':')))
    encoded = [{'ParameterKey': key, 'ParameterValue': value} for key, value in sorted(values.items())]
    source = ['--template-body', 'file://' + str(template)]
    if template.stat().st_size > 51200:
        aws('s3api', 'head-bucket', '--bucket', config['packageBucket'], '--expected-bucket-owner', config['accountId'])
        location = aws('s3api', 'get-bucket-location', '--bucket', config['packageBucket'],
                       '--expected-bucket-owner', config['accountId'])
        if location.get('LocationConstraint') != config['region']:
            raise ValueError('Package bucket must be in the deployment region')
        key = name + '/' + stack + '.json'
        url = upload_template(aws, config, template, key)
        source = ['--template-url', url]
    args = ['--stack-name', config['stacks'][stack], '--change-set-name', name,
            '--change-set-type', 'UPDATE' if existing else 'CREATE', *source,
            '--parameters', json.dumps(encoded), '--capabilities', 'CAPABILITY_NAMED_IAM']
    if stack != 'bootstrap':
        args.extend(['--role-arn', outputs['bootstrap']['CloudFormationExecutionRoleArn']])
    if stack == 'root':
        args.append('--include-nested-stacks')
    # Long package operations must not hide a release starting during preparation.
    if capture(aws, config, paused) != before:
        raise ValueError('Environment changed during preparation; replan')
    arn = aws('cloudformation', 'create-change-set', *args)['Id']
    aws('cloudformation', 'wait', 'change-set-create-complete', '--change-set-name', arn)
    change = change_set(aws, arn)
    guard = capture(aws, config, paused, allow_created_runtime=stack == 'runtime' and not existing)
    if existing and guard['stacks'][stack] != before['stacks'][stack]:
        raise ValueError('Target stack changed during planning; do not execute this change set')
    # CREATE introduces REVIEW_IN_PROGRESS; other stacks and live deployment must stay unchanged.
    for key in config['stacks']:
        if key != stack and guard['stacks'][key] != before['stacks'][key]:
            raise ValueError('Predecessor changed; do not execute the new change set')
    if guard['runtime'] != before['runtime']:
        raise ValueError('Release changed; do not execute the new change set')
    return {'version': 1, 'environment': config, 'stack': stack, 'changeSetArn': arn,
            'type': 'UPDATE' if existing else 'CREATE', 'parameters': encoded, 'guard': guard,
            'changes': change.get('Changes', [])}


def verify_initial_runtime(values, outputs, runtime):
    expected = {name: values[name] for name in RUNTIME_STATE}
    for prefix in ('Api', 'Worker', 'Scheduler'):
        name = prefix + 'TaskDefinitionArn'
        if not expected[name]:
            expected[name] = outputs[prefix + 'BootstrapTaskDefinitionArn']
    # ECS can finish the empty blue/green deployment on either known target.
    # runtime_snapshot has already rejected split traffic and unknown groups.
    for name in ('PrimaryTargetGroup', 'ProductionTargetGroup', 'TestTargetGroup'):
        color = (runtime or {}).get('parameters', {}).get(name)
        if color not in ('Blue', 'Green'):
            raise ValueError('Initial runtime has unknown target groups')
        expected[name] = color
    if runtime is None or runtime['parameters'] != expected:
        raise ValueError('Initial runtime differs from the reviewed zero-task bootstrap state')


def verify_runtime_transition(before, after, runtime_updated):
    if before is None:
        if after is not None:
            raise ValueError('Runtime changed unexpectedly during non-runtime operation')
        return
    if not runtime_updated:
        if before != after:
            raise ValueError('Runtime changed during non-runtime operation; keep releases paused')
        return
    if after is None or before['parameters'] != after['parameters']:
        raise ValueError('Live runtime values changed after infrastructure update; keep releases paused')
    for service, history in after['nativeHistory'].items():
        prior = {item['serviceDeploymentArn']: item for item in before['nativeHistory'].get(service, [])}
        for item in history:
            if prior.get(item['serviceDeploymentArn']) != item and item['status'] != 'SUCCESSFUL':
                raise ValueError('Unexpected native deployment failure/rollback during infrastructure update')


def execute(aws, plan, root, paused):
    if plan['version'] != 1:
        raise ValueError('Unsupported plan version')
    config = plan['environment']
    templates, contract = load_contract(root)
    validate_config(config, templates, contract)
    initial_runtime = plan['stack'] == 'runtime' and plan['type'] == 'CREATE' and plan['guard']['runtime'] is None
    if capture(aws, config, paused, allow_created_runtime=initial_runtime) != plan['guard']:
        raise ValueError('Stale plan: stack or release changed; create and review a new plan')
    change = change_set(aws, plan['changeSetArn'])
    prefix = f"arn:aws:cloudformation:{config['region']}:{config['accountId']}:stack/{config['stacks'][plan['stack']]}/"
    if not change.get('StackId', '').startswith(prefix):
        raise ValueError('Change set stack identity differs from the reviewed environment')
    actual = {item['ParameterKey']: item['ParameterValue'] for item in change.get('Parameters', [])}
    expected = {item['ParameterKey']: item['ParameterValue'] for item in plan['parameters']}
    if actual != expected:
        raise ValueError('Change set parameters differ from the reviewed plan')
    # The ARN is immutable; AWS state is rechecked immediately before execution.
    aws('cloudformation', 'update-termination-protection', '--stack-name', config['stacks'][plan['stack']],
        '--enable-termination-protection')
    # Preserve successfully created data resources if an initial sibling fails.
    # A failed CREATE remains blocked for operator repair instead of tearing down
    # a still-creating protected database during automatic rollback.
    options = ['--disable-rollback'] if plan['type'] == 'CREATE' else []
    aws('cloudformation', 'execute-change-set', '--change-set-name', plan['changeSetArn'], *options)
    waiter = 'stack-update-complete' if plan['type'] == 'UPDATE' else 'stack-create-complete'
    aws('cloudformation', 'wait', waiter, '--stack-name', config['stacks'][plan['stack']])
    after = capture(aws, config, paused, allow_created_runtime=initial_runtime)
    for key in config['stacks']:
        if key != plan['stack'] and after['stacks'][key] != plan['guard']['stacks'][key]:
            raise ValueError('Untouched parent changed during execution; keep releases paused')
    target = after['stacks'][plan['stack']]
    if not target or target['StackStatus'] not in STABLE_STACKS:
        raise ValueError('Target stack disappeared or did not complete; keep releases paused')
    reviewed_target = plan['guard']['stacks'][plan['stack']]
    if not reviewed_target or target['StackId'] != reviewed_target['StackId']:
        raise ValueError('Target stack identity changed during execution')
    if initial_runtime:
        verify_initial_runtime(expected, output_values(target), after['runtime'])
    else:
        verify_runtime_transition(plan['guard']['runtime'], after['runtime'], runtime_updated=plan['stack'] == 'runtime')
    return {'stack': config['stacks'][plan['stack']], 'status': 'verified',
            'outputs': output_values(after['stacks'][plan['stack']])}


def save(path, value):
    # Exclusive creation prevents accidentally overwriting an operator record or following a symlink.
    with path.open('x', encoding='utf-8') as stream:
        os.chmod(path, 0o600)
        json.dump(value, stream, ensure_ascii=False, indent=2)
        stream.write('\n')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest='command', required=True)
    check = commands.add_parser('config-check', help='Offline validation of nonsecret input schema')
    check.add_argument('--environment', type=Path, required=True)
    configure_parser = commands.add_parser('configure', help='Create/update app Secrets, DB roles and local release configuration')
    configure_parser.add_argument('--environment', type=Path, required=True)
    configure_parser.add_argument('--output', type=Path, required=True)
    configure_parser.add_argument('--release-paused', action='store_true')
    prepare_parser = commands.add_parser('plan', help='Create (not execute) a reviewed AWS change set')
    prepare_parser.add_argument('--environment', type=Path, required=True)
    prepare_parser.add_argument('--stack', choices=('bootstrap', 'root', 'integration', 'runtime'), required=True)
    prepare_parser.add_argument('--record', type=Path, required=True)
    prepare_parser.add_argument('--release-paused', action='store_true')
    execute_parser = commands.add_parser('execute', help='Execute the exact reviewed plan and verify live state')
    execute_parser.add_argument('--record', type=Path, required=True)
    execute_parser.add_argument('--release-paused', action='store_true')
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[2] / 'infra/cloudformation'
    try:
        if args.command == 'execute':
            plan = json.loads(args.record.read_text())
            result = execute(Aws(plan['environment']['region']), plan, root, args.release_paused)
            save(args.record.with_suffix('.result.json'), result)
            print('Stack update verified. Record: ' + str(args.record.with_suffix('.result.json')))
            return
        config = json.loads(args.environment.read_text())
        templates, contract = load_contract(root)
        validate_config(config, templates, contract)
        if args.command == 'configure':
            from configuration import configure
            capture(Aws(config['region']), config, args.release_paused)
            configure(Aws(config['region']), config, args.output)
            return
        if args.command == 'config-check':
            parameters(config, 'bootstrap', templates, contract, {})
            parameters(config, 'root', templates, contract, {})
            print('Environment input valid; live Outputs, permissions and activation are not verified.')
            return
        if not args.record.is_absolute() or args.record.exists():
            raise ValueError('Use a new absolute record path outside the repository')
        repository = root.parents[1]
        if args.record.is_relative_to(repository):
            raise ValueError('Operator records must be outside the repository')
        args.record.parent.mkdir(parents=True, exist_ok=True, mode=0o700)
        plan = prepare(Aws(config['region']), config, args.stack, root, args.record.parent, args.release_paused)
        save(args.record, plan)
        print('Change set ready for human review (not executed): ' + plan['changeSetArn'])
        print('Review parent and nested changes before execute. Record: ' + str(args.record))
    except (AwsError, ValueError, KeyError, OSError) as error:
        print(str(error) if not isinstance(error, KeyError) else 'Required AWS/config field missing; refusing to continue', file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
