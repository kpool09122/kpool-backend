"""AWS CLI boundary and backend deployment; CloudFormation access is read-only."""
import json
import os
import subprocess
import urllib.request
from contract import task_definition, runtime_environment, SECRET_KEYS, SECRET_REF
from deployment import (poll, desired_count, migration_complete, api_complete,
                        worker_complete, validate_api_service)
from records import write_json


class Aws:
    def __call__(self, aws_service, operation, **arguments):
        command = ['aws', aws_service, operation, '--region', 'ap-northeast-1',
                   '--output', 'json', '--no-cli-pager', '--cli-connect-timeout', '10',
                   '--cli-read-timeout', '30', '--cli-input-json', json.dumps(arguments)]
        if aws_service == 'lambda' and operation == 'get-function-configuration':
            command += ['--query','{State:State,LastUpdateStatus:LastUpdateStatus,CodeSha256:CodeSha256}']
        result = subprocess.run(command, capture_output=True, text=True, timeout=60,
                                env=dict(os.environ, AWS_MAX_ATTEMPTS='3'))
        if result.returncode:
            raise RuntimeError('AWS operation failed: ' + aws_service + '/' + operation)
        response = json.loads(result.stdout)
        if response.get('failures'):
            raise RuntimeError('AWS returned lookup or launch failures: '+operation)
        return response


def stack_outputs(aws, name, account):
    response = aws('cloudformation','describe-stacks',StackName=name)['Stacks']
    if len(response) != 1:
        raise ValueError('Expected exactly one stack')
    stack = response[0]
    if (stack['StackStatus'] not in ('CREATE_COMPLETE','UPDATE_COMPLETE','IMPORT_COMPLETE')
            or f':ap-northeast-1:{account}:stack/' not in stack['StackId']):
        raise ValueError('Stack is unstable or outside the configured environment')
    return {item['OutputKey']:item['OutputValue'] for item in stack.get('Outputs', [])}


class Backend:
    def __init__(self, aws, plan, manifest, journal, configuration, previous=None):
        self.aws, self.plan, self.manifest = aws, plan, manifest
        self.journal, self.configuration, self.previous = journal, configuration, previous
        self.outputs = {}
        self.tasks = dict(manifest.get('task_definitions', {}))
        self.counts = {}

    def services(self):
        response = self.aws('ecs','describe-services',cluster=self.outputs['ClusterArn'],
                            services=[self.outputs['ApiServiceName'],self.outputs['WorkerServiceName']])
        services = {s['serviceName']: s for s in response['services']}
        if len(services) != 2 or set(services) != {self.outputs['ApiServiceName'],self.outputs['WorkerServiceName']}:
            raise ValueError('API/worker service lookup is incomplete')
        return {kind: services[self.outputs[kind+'ServiceName']] for kind in ('Api','Worker')}

    def preflight(self, check_image=True):
        c = self.configuration
        if c.get('paused') is not False or c.get('region') != 'ap-northeast-1':
            raise ValueError('Release is paused or region is invalid')
        account = c['account_id']
        if self.aws('sts','get-caller-identity')['Account'] != account:
            raise ValueError('AWS account mismatch')
        for key in ('bootstrap','root','integration','runtime'):
            self.outputs.update(stack_outputs(self.aws,c['stacks'][key],account))
        o = self.outputs
        if o['DeploymentRoleArn'] != c['deployment_role_arn']:
            raise ValueError('Deployment role does not match bootstrap output')
        required = ('RepositoryUri ClusterArn ApiServiceName WorkerServiceName ApiReleaseFamily WorkerReleaseFamily MigrationReleaseFamily ApiBootstrapTaskDefinitionArn WorkerBootstrapTaskDefinitionArn MigrationBootstrapTaskDefinitionArn ApiTaskRoleArn WorkerTaskRoleArn MigrationTaskRoleArn AppExecutionRoleArn MigrationExecutionRoleArn LifecycleHookArn HookInvocationRoleArn ServerErrorAlarmName LatencyAlarmName ExternalCanaryAlarmName BlueTargetGroupArn GreenTargetGroupArn ProductionRuleArn TestRuleArn AlbInfrastructureRoleArn PublicSubnetIds MigrationSecurityGroupId RuntimeConfigParameterName AppSecretArn MigrationSecretArn CacheSecretArn ApiUrl QueueUrl').split()
        if any(not o.get(key) for key in required):
            raise ValueError('Required infrastructure output is missing')
        registry = f'{account}.dkr.ecr.ap-northeast-1.amazonaws.com/'
        if not o['RepositoryUri'].startswith(registry):
            raise ValueError('ECR repository account mismatch')
        if self.manifest['repository_uri'] != o['RepositoryUri']:
            raise ValueError('Recorded ECR repository does not match live output')
        if check_image:
            images = self.aws('ecr','describe-images',repositoryName=o['RepositoryUri'].split('/',1)[1],
                              imageIds=[{'imageDigest':self.manifest['image_digest']}])['imageDetails']
            if len(images) != 1 or images[0]['imageDigest'] != self.manifest['image_digest']:
                raise ValueError('Recorded digest is unavailable')
        services = self.services()
        validate_api_service(services['Api'],o)
        for kind, service in services.items():
            if (service['status'] != 'ACTIVE' or service['pendingCount'] != 0
                    or service['runningCount'] != service['desiredCount']
                    or len(service['deployments']) != 1
                    or service['deployments'][0].get('rolloutState') == 'FAILED'):
                raise ValueError('Service is unstable; reconcile interrupted deployment first')
            self.counts[kind] = desired_count(service['desiredCount'], self.plan['initial'])
        history=self.aws('ecs','list-service-deployments',cluster=o['ClusterArn'],service=o['ApiServiceName'])['serviceDeployments']
        if any(d.get('status') in ('PENDING','IN_PROGRESS','STOP_REQUESTED','ROLLBACK_REQUESTED','ROLLBACK_IN_PROGRESS') for d in history):
            raise ValueError('A native API deployment is still active; reconcile first')
        alarm_names = [o[k] for k in ('ServerErrorAlarmName','LatencyAlarmName','ExternalCanaryAlarmName')]
        alarms = self.aws('cloudwatch','describe-alarms',AlarmNames=alarm_names)['MetricAlarms']
        if set(a['AlarmName'] for a in alarms) != set(alarm_names) or any(a['StateValue'] != 'OK' or not a['ActionsEnabled'] for a in alarms):
            raise ValueError('Deployment alarm is missing, disabled or unhealthy')
        hook = self.aws('lambda','get-function-configuration',FunctionName=o['LifecycleHookArn'])
        if (hook.get('State') != 'Active' or hook.get('LastUpdateStatus') != 'Successful'
                or not c.get('hook_code_sha256') or hook['CodeSha256'] != c['hook_code_sha256']):
            raise ValueError('Hook artifact is missing or differs from approved artifact')
        parameter = self.aws('ssm','get-parameter',Name=o['RuntimeConfigParameterName'],WithDecryption=False)['Parameter']
        if parameter['Type'] != 'String':
            raise ValueError('Runtime configuration must be nonsecret SSM String')
        self.env = json.loads(parameter['Value'])
        self.env.update(c['runtime_environment'])
        runtime_environment(self.env)
        required_env = {'APP_ENV':'production','APP_DEBUG':'false','DB_CONNECTION':'pgsql',
                        'QUEUE_CONNECTION':'sqs','SESSION_DRIVER':'redis','CACHE_STORE':'redis',
                        'REDIS_DB':'0','REDIS_CACHE_DB':'0'}
        if any(self.env.get(k) != v for k,v in required_env.items()):
            raise ValueError('Production DB/cache/TLS configuration is incomplete')
        if self.env.get('SQS_QUEUE_URL') != o['QueueUrl']:
            raise ValueError('Queue configuration differs from foundation output')
        if self.env.get('APP_URL') != o['ApiUrl'] or not self.env.get('FRONTEND_URL','').startswith('https://'):
            raise ValueError('Production origins are incomplete')
        if c.get('secret_contract') != {'DATABASE_URL':'verify-full:/etc/ssl/certs/kpool-rds-ca.pem','REDIS_URL':'tls:db0'}:
            raise ValueError('Operator TLS URL secret contract must be explicitly attested')
        compatibility = c['compatibility']
        if (compatibility.get('approved') is not True or compatibility.get('backend_sha') != self.plan['backend_sha']
                or compatibility.get('live_frontend_sha') != self.plan['live_frontend_sha']
                or compatibility.get('migration_policy') != 'expand-contract'
                or not compatibility.get('evidence')):
            raise ValueError('DB/queue/live frontend compatibility evidence is missing')
        # Only ARN references are read. Secret values remain in ECS/Secrets Manager.
        self.secrets = {}
        for kind in ('Api','Worker','Migration'):
            base = o['MigrationSecretArn'] if kind == 'Migration' else o['AppSecretArn']
            keys = ['APP_KEY','DATABASE_URL','DB_USERNAME','DB_PASSWORD']
            refs = [{'name':k,'valueFrom':base+':'+k+'::'} for k in keys]
            if kind != 'Migration':
                refs += [{'name':'REDIS_URL','valueFrom':base+':REDIS_URL::'}, {'name':'REDIS_PASSWORD','valueFrom':o['CacheSecretArn']+':password::'}]
                for key in c.get('app_secret_keys', []):
                    if key not in SECRET_KEYS or key in keys or key in ('REDIS_PASSWORD','REDIS_URL'):
                        raise ValueError('Invalid additional application secret key')
                    refs.append({'name':key,'valueFrom':base+':'+key+'::'})
            if any(not SECRET_REF.fullmatch(ref['valueFrom']) for ref in refs):
                raise ValueError('Invalid ECS secret JSON-key ARN reference')
            self.secrets[kind]=refs
        if self.plan['mode'] != 'deploy':
            self.validate_previous(services)
        self.journal.event('preflight','observed',api=services['Api']['taskDefinition'],
                           worker=services['Worker']['taskDefinition'],
                           api_desired=services['Api']['desiredCount'],worker_desired=services['Worker']['desiredCount'])
        # Prepare every definition before the first registration mutation.
        self.definitions = {}
        for kind in ('Api','Worker','Migration'):
            template = self.aws('ecs','describe-task-definition',taskDefinition=o[kind+'BootstrapTaskDefinitionArn'])['taskDefinition']
            if check_image:
                self.definitions[kind] = task_definition(kind,template,o,self.env,self.secrets[kind],self.manifest['image_digest'])
            elif (template['taskRoleArn'] != o[kind+'TaskRoleArn'] or template['executionRoleArn'] != o['MigrationExecutionRoleArn' if kind=='Migration' else 'AppExecutionRoleArn']
                  or template['runtimePlatform']['cpuArchitecture'] != 'ARM64'
                  or not template['containerDefinitions'][0].get('readonlyRootFilesystem')
                  or {m['containerPath'] for m in template['containerDefinitions'][0].get('mountPoints',[])} != {'/tmp','/var/www/html/storage','/var/www/html/bootstrap/cache'}):
                raise ValueError('Bootstrap role/platform/volume contract mismatch')
        self.queue_attributes()

    def validate_previous(self, services):
        required_tasks = {'rollback-api': {'Api'}, 'rollback-worker': {'Worker'},
                          'resume-worker': {'Api', 'Worker'}}.get(self.plan['mode'], set())
        if not required_tasks <= self.tasks.keys():
            raise ValueError('Recorded task revisions are required for rollback/resume')
        if not self.previous:
            raise ValueError('Recorded journal required for replay/rollback')
        events = self.previous['events']
        if self.plan['mode'] in ('redeploy','resume-worker') and not any(e['stage']=='migration' and e['status'] in ('success','inherited-success') for e in events):
            raise ValueError('Migration success is unrecorded; do not repeat automatically')
        if self.plan['mode'] in ('redeploy','resume-worker'):
            self.journal.event('migration','inherited-success',source_run=self.plan['source_run'])
        if self.plan['mode']=='resume-worker':
            if not any(e['stage']=='api' and e['status'] in ('success','inherited-success') for e in events) or services['Api']['taskDefinition'] != self.tasks['Api']:
                raise ValueError('API success/live revision mismatch')
        if self.plan['mode'].startswith('rollback') or self.plan['mode']=='resume-worker':
            for kind, arn in self.tasks.items():
                definition = self.aws('ecs','describe-task-definition',taskDefinition=arn)['taskDefinition']
                expected = self.outputs[kind+'ReleaseFamily']
                if (definition['family'] != expected or definition['taskRoleArn'] != self.outputs[kind+'TaskRoleArn']
                        or definition['executionRoleArn'] != self.outputs['MigrationExecutionRoleArn' if kind=='Migration' else 'AppExecutionRoleArn']
                        or definition['containerDefinitions'][0]['image'] != self.outputs['RepositoryUri']+'@'+self.manifest['image_digest']):
                    raise ValueError('Recorded task revision is not owned by this release/environment')

    def register(self):
        for kind, definition in self.definitions.items():
            result = self.aws('ecs','register-task-definition',**definition)
            self.tasks[kind]=result['taskDefinition']['taskDefinitionArn']
            self.journal.event('register','observed',task_definition=self.tasks[kind])
        # Separate immutable deployment manifest: publication manifest is never overwritten.
        write_json(self.journal.directory/'deployment-manifest.json',dict(self.manifest,task_definitions=self.tasks),exclusive=True)

    def migration(self):
        o=self.outputs
        response=self.aws('ecs','run-task',cluster=o['ClusterArn'],taskDefinition=self.tasks['Migration'],
                          launchType='FARGATE',platformVersion='1.4.0',count=1,
                          clientToken=self.plan['release_id']+'-migration',
                          networkConfiguration={'awsvpcConfiguration':{'subnets':o['PublicSubnetIds'].split(','),
                            'securityGroups':[o['MigrationSecurityGroupId']],'assignPublicIp':'ENABLED'}})
        if len(response.get('tasks',[])) != 1:
            raise ValueError('Exactly one migration task must be launched')
        arn=response['tasks'][0]['taskArn']
        self.journal.event('migration','launched',task_arn=arn)
        def check():
            tasks=self.aws('ecs','describe-tasks',cluster=o['ClusterArn'],tasks=[arn])['tasks']
            if len(tasks)!=1 or tasks[0]['taskArn']!=arn or tasks[0]['taskDefinitionArn']!=self.tasks['Migration']:
                raise ValueError('Migration task identity mismatch')
            return migration_complete(tasks[0])
        poll(check,timeout=600)

    def api(self):
        o=self.outputs
        before={d['serviceDeploymentArn'] for d in self.aws('ecs','list-service-deployments',cluster=o['ClusterArn'],service=o['ApiServiceName'])['serviceDeployments']}
        self.journal.event('api','requesting',task_definition=self.tasks['Api'],desired=self.counts['Api'])
        self.aws('ecs','update-service',cluster=o['ClusterArn'],service=o['ApiServiceName'],
                 taskDefinition=self.tasks['Api'],desiredCount=self.counts['Api'],forceNewDeployment=True)
        def discover():
            deployments=self.aws('ecs','list-service-deployments',cluster=o['ClusterArn'],service=o['ApiServiceName'])['serviceDeployments']
            candidates=[d['serviceDeploymentArn'] for d in deployments if d['serviceDeploymentArn'] not in before]
            if len(candidates)>1:
                raise ValueError('Concurrent API deployment detected')
            return candidates[0] if candidates else False
        arn=poll(discover,timeout=120)
        self.journal.event('api','launched',deployment_arn=arn,task_definition=self.tasks['Api'])
        def check():
            deployments=self.aws('ecs','describe-service-deployments',serviceDeploymentArns=[arn])['serviceDeployments']
            if len(deployments)!=1:
                raise ValueError('API deployment disappeared')
            deployment=deployments[0]
            live_contract=self.services()['Api']
            validate_api_service(dict(live_contract,deploymentConfiguration=deployment['deploymentConfiguration']),o)
            revision_arn=deployment['targetServiceRevision']['arn']
            revisions=self.aws('ecs','describe-service-revisions',serviceRevisionArns=[revision_arn])['serviceRevisions']
            if len(revisions)!=1:
                raise ValueError('API target revision disappeared')
            return api_complete(deployment,revisions[0],arn,self.tasks['Api'],self.counts['Api'])
        poll(check,timeout=1500)
        live=self.services()['Api']
        if live['taskDefinition']!=self.tasks['Api'] or live['runningCount']!=self.counts['Api'] or live['pendingCount']!=0:
            raise ValueError('API live state differs from successful deployment')

    def worker(self):
        self.journal.event('worker','requesting',task_definition=self.tasks['Worker'],desired=self.counts['Worker'])
        self.aws('ecs','update-service',cluster=self.outputs['ClusterArn'],service=self.outputs['WorkerServiceName'],
                 taskDefinition=self.tasks['Worker'],desiredCount=self.counts['Worker'],forceNewDeployment=True)
        poll(lambda:worker_complete(self.services()['Worker'],self.tasks['Worker'],self.counts['Worker']),timeout=600)

    def queue_attributes(self):
        attributes=self.aws('sqs','get-queue-attributes',QueueUrl=self.outputs['QueueUrl'],
                            AttributeNames=['QueueArn','ApproximateNumberOfMessages','ApproximateNumberOfMessagesNotVisible'])['Attributes']
        if attributes['QueueArn']!=self.outputs['QueueArn']:
            raise ValueError('Queue identity differs from foundation output')
        return attributes

    def smoke(self):
        url=self.outputs['ApiUrl']+'/up'
        with urllib.request.urlopen(url,timeout=20) as response:
            if response.status!=200 or response.geturl()!=url:
                raise ValueError('API health check failed or redirected')
            response.read(4096)
        self.queue_attributes()
        # This read-only probe is useful, but does NOT prove a job was processed.
        self.journal.event('smoke','observed',health='HTTP 200 /up',queue='identity/attributes verified',queue_processing='NOT_VERIFIED: approved canary job required')

    def observe(self):
        if not self.outputs.get('ClusterArn'):
            raise ValueError('Infrastructure identity unavailable')
        services=self.services()
        self.journal.event('live','observed',api=services['Api']['taskDefinition'],
                           worker=services['Worker']['taskDefinition'],api_desired=services['Api']['desiredCount'],
                           worker_desired=services['Worker']['desiredCount'])
        deployments=self.aws('ecs','list-service-deployments',cluster=self.outputs['ClusterArn'],service=self.outputs['ApiServiceName'])['serviceDeployments']
        # ARN-only history avoids hook details or arbitrary AWS error bodies in artifacts.
        for deployment in deployments:
            self.journal.event('live','deployment',deployment_arn=deployment['serviceDeploymentArn'])
