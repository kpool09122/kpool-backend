"""Translate stable ECS/ALB/Scheduler state into CloudFormation parameters."""


def resolve(outputs, services, rules, schedule):
    if services.get('failures'):
        raise ValueError('ECS reported service lookup failures')
    by_name = {service['serviceName']: service for service in services['services']}
    expected = {outputs['ApiServiceName'], outputs['WorkerServiceName']}
    if set(by_name) != expected or len(services['services']) != len(expected):
        raise ValueError('Exactly the configured API and worker services must be returned')
    values = {}
    for prefix in ('Api', 'Worker'):
        service = by_name[outputs[prefix + 'ServiceName']]
        if (service['status'] != 'ACTIVE' or service['pendingCount'] != 0
                or service['desiredCount'] != service['runningCount']):
            raise ValueError('Service is not stable')
        deployments = service['deployments']
        if (len(deployments) != 1 or deployments[0]['status'] != 'PRIMARY'
                or deployments[0].get('rolloutState', 'COMPLETED') != 'COMPLETED'):
            raise ValueError('Service deployment is not complete')
        values[prefix + 'TaskDefinitionArn'] = service['taskDefinition']
        values[prefix + 'DesiredCount'] = str(service['desiredCount'])
    colors = {outputs['BlueTargetGroupArn']: 'Blue', outputs['GreenTargetGroupArn']: 'Green'}
    api = by_name[outputs['ApiServiceName']]
    balancers = api['loadBalancers']
    if len(balancers) != 1 or balancers[0]['targetGroupArn'] not in colors:
        raise ValueError('Unknown service target')
    primary = balancers[0]['targetGroupArn']
    advanced = balancers[0].get('advancedConfiguration', {})
    if (advanced.get('alternateTargetGroupArn') not in set(colors) - {primary}
            or advanced.get('productionListenerRule') != outputs['ProductionRuleArn']
            or advanced.get('testListenerRule') != outputs['TestRuleArn']):
        raise ValueError('Service load balancer contract changed')
    values['PrimaryTargetGroup'] = colors[primary]
    rule_by_arn = {rule['RuleArn']: rule for rule in rules['Rules']}
    if set(rule_by_arn) != {outputs['ProductionRuleArn'], outputs['TestRuleArn']}:
        raise ValueError('Unexpected listener rules')
    for prefix in ('Production', 'Test'):
        actions = rule_by_arn[outputs[prefix + 'RuleArn']]['Actions']
        if len(actions) != 1 or actions[0]['Type'] != 'forward':
            raise ValueError('Unexpected listener actions')
        config = actions[0]['ForwardConfig']
        if config.get('TargetGroupStickinessConfig', {}).get('Enabled', False):
            raise ValueError('Target group stickiness cannot be represented')
        targets = config['TargetGroups']
        weights = {target['TargetGroupArn']: target.get('Weight', 1) for target in targets}
        if (len(targets) != 2 or set(weights) != set(colors)
                or sorted(weights.values()) != [0, 1]):
            raise ValueError('Intermediate or unknown forwarding weights')
        values[prefix + 'TargetGroup'] = colors[next(arn for arn, weight in weights.items() if weight == 1)]
    if schedule['State'] not in {'ENABLED', 'DISABLED'}:
        raise ValueError('Unknown scheduler state')
    values['SchedulerState'] = schedule['State']
    values['SchedulerTaskDefinitionArn'] = schedule['Target']['EcsParameters']['TaskDefinitionArn']
    return values
