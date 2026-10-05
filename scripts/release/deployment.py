"""Finite deployment state machine, independent of AWS transport and workflow YAML."""
import time


def migration_complete(task):
    if task['lastStatus'] != 'STOPPED':
        return False
    containers = task.get('containers', [])
    if len(containers) != 1 or containers[0].get('name') != 'migration' or containers[0].get('exitCode') != 0:
        raise ValueError('Migration failed or exit code is absent')
    return True


def api_complete(deployment, revision, deployment_arn, task_arn, desired):
    if (deployment['serviceDeploymentArn'] != deployment_arn
            or revision['serviceRevisionArn'] != deployment['targetServiceRevision']['arn']
            or revision['taskDefinition'] != task_arn):
        raise ValueError('Deployment/revision ownership mismatch')
    status = deployment['status']
    if status not in ('PENDING', 'IN_PROGRESS', 'SUCCESSFUL') or deployment.get('rollback'):
        raise ValueError('API deployment failed, stopped or rolled back')
    configuration = deployment['deploymentConfiguration']
    if configuration.get('strategy') != 'BLUE_GREEN' or configuration.get('bakeTimeInMinutes', 0) < 5:
        raise ValueError('API deployment bake contract mismatch')
    target = deployment['targetServiceRevision']
    return (status == 'SUCCESSFUL' and target.get('runningTaskCount') == desired
            and target.get('pendingTaskCount') == 0
            and target.get('requestedProductionTrafficWeight') == 100
            and all(s.get('runningTaskCount') == 0 and s.get('pendingTaskCount') == 0
                    for s in deployment.get('sourceServiceRevisions', [])))


def desired_count(current, initial):
    if initial:
        if current != 0:
            raise ValueError('Initial activation requires desired count zero')
        return 1
    if current <= 0:
        raise ValueError('Zero desired count requires explicit initial activation')
    return current


def worker_complete(service, task_arn, desired):
    deployments = service['deployments']
    for deployment in deployments:
        if deployment.get('rolloutState') == 'FAILED':
            raise ValueError('Worker deployment failed')
    return (service['taskDefinition'] == task_arn
            and service['desiredCount'] == desired and service['runningCount'] == desired
            and service['pendingCount'] == 0 and len(deployments) == 1
            and deployments[0]['taskDefinition'] == task_arn
            and deployments[0].get('rolloutState') == 'COMPLETED')


def poll(check, timeout=1800, interval=15, clock=time.monotonic, sleep=time.sleep):
    deadline = clock() + timeout
    while clock() < deadline:
        value = check()
        if value:
            return value
        sleep(interval)
    raise TimeoutError('Deployment polling timed out; inspect recorded live state before retry')


def validate_api_service(service, outputs):
    configuration = service.get('deploymentConfiguration', {})
    alarms = configuration.get('alarms', {})
    names = [outputs[k] for k in ('ServerErrorAlarmName','LatencyAlarmName','ExternalCanaryAlarmName')]
    if (service.get('deploymentController', {}).get('type') != 'ECS'
            or configuration.get('strategy') != 'BLUE_GREEN'
            or configuration.get('bakeTimeInMinutes', 0) < 5
            or not alarms.get('enable') or not alarms.get('rollback')
            or not all(names) or set(alarms.get('alarmNames', [])) != set(names)
            or configuration.get('earlySuccessCriteria', {}).get('enable')):
        raise ValueError('API controller/alarm/bake contract is incomplete')
    hooks = configuration.get('lifecycleHooks', [])
    if not any(h.get('hookTargetArn') == outputs['LifecycleHookArn']
               and h.get('roleArn') == outputs['HookInvocationRoleArn']
               and 'POST_TEST_TRAFFIC_SHIFT' in h.get('lifecycleStages', []) for h in hooks):
        raise ValueError('Pretraffic lifecycle hook is missing')
    if configuration.get('deploymentLifecycleConfig'):
        raise ValueError('Configured pause/extra lifecycle controls require explicit integration')
    balances = service.get('loadBalancers', [])
    if len(balances) != 1:
        raise ValueError('Exactly one API load balancer is required')
    balance = balances[0]
    advanced = balance.get('advancedConfiguration', {})
    if ({balance.get('targetGroupArn'), advanced.get('alternateTargetGroupArn')}
            != {outputs['BlueTargetGroupArn'], outputs['GreenTargetGroupArn']}
            or advanced.get('productionListenerRule') != outputs['ProductionRuleArn']
            or advanced.get('testListenerRule') != outputs['TestRuleArn']
            or advanced.get('roleArn') != outputs['AlbInfrastructureRoleArn']):
        raise ValueError('API target groups/rules/ALB role contract mismatch')
