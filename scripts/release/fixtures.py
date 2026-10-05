"""TEST ONLY: deterministic fake AWS responses. Never imported by runtime modules."""
import copy
import botocore.session
from botocore.validate import validate_parameters
from contract import BACKEND

ACCOUNT='123456789012'
SHA='a'*40
DIGEST='sha256:'+'b'*64

def fixture():
    o={k:'fixture-'+k for k in ('ClusterArn ApiServiceName WorkerServiceName LifecycleHookArn HookInvocationRoleArn ServerErrorAlarmName LatencyAlarmName ExternalCanaryAlarmName BlueTargetGroupArn GreenTargetGroupArn ProductionRuleArn TestRuleArn AlbInfrastructureRoleArn PublicSubnetIds MigrationSecurityGroupId RuntimeConfigParameterName QueueUrl QueueArn').split()}
    o.update(RepositoryUri=ACCOUNT+'.dkr.ecr.ap-northeast-1.amazonaws.com/fixture',DeploymentRoleArn='fixture-deployment-role',ApiUrl='https://api.example.invalid')
    for key in ('AppSecretArn','MigrationSecretArn','CacheSecretArn'):
        o[key]=f'arn:aws:secretsmanager:ap-northeast-1:{ACCOUNT}:secret:fixture-{key}'
    for kind in ('Api','Worker','Migration'):
        o.update({kind+'TaskRoleArn':'fixture-'+kind+'-task-role',kind+'ReleaseFamily':'fixture-'+kind+'-release',kind+'BootstrapTaskDefinitionArn':'fixture-'+kind+'-bootstrap'})
    o.update(AppExecutionRoleArn='fixture-app-execution',MigrationExecutionRoleArn='fixture-migration-execution')
    environment={'APP_ENV':'production','APP_DEBUG':'false','DB_CONNECTION':'pgsql','DB_HOST':'fixture-db','QUEUE_CONNECTION':'sqs','SESSION_DRIVER':'redis','CACHE_STORE':'redis','REDIS_DB':'0','REDIS_CACHE_DB':'0','SQS_QUEUE_URL':o['QueueUrl'],'APP_URL':o['ApiUrl'],'FRONTEND_URL':'https://front.example.invalid'}
    config={'paused':False,'region':'ap-northeast-1','account_id':ACCOUNT,'deployment_role_arn':o['DeploymentRoleArn'],
            'stacks':{k:'fixture-'+k for k in ('bootstrap','root','integration','runtime')},'hook_code_sha256':'fixture-approved-code',
            'runtime_environment':{},'secret_contract':{'DATABASE_URL':'verify-full:/etc/ssl/certs/kpool-rds-ca.pem','REDIS_URL':'tls:db0'},
            'compatibility':{'approved':True,'backend_sha':SHA,'live_frontend_sha':'c'*40,'migration_policy':'expand-contract','evidence':'fixture-reviewed-compatibility'}}
    plan={'schema':1,'repository':BACKEND,'environment':'production','target':'backend','mode':'deploy','initial':False,'backend_sha':SHA,'live_frontend_sha':'c'*40,'workflow_sha':'d'*40,'release_id':'42-1'}
    manifest=dict(plan,repository_uri=o['RepositoryUri'],image_digest=DIGEST)
    return o,environment,config,plan,manifest

class FakeAws:
    def __init__(self, outputs, environment):
        self.outputs,self.environment=copy.deepcopy(outputs),copy.deepcopy(environment)
        self.calls=[]; self.failure=None; self.api_updated=False
        self.migration_status='STOPPED'; self.migration_exit=0; self.api_status='SUCCESSFUL'; self.wrong_revision=False
        self.worker_failed=False; self.account=ACCOUNT; self.stack_status='CREATE_COMPLETE'; self.alarm_state='OK'
        self.hook_state='Active'; self.ssm_type='String'; self.pending=0; self.template_overrides={}
        self.services={kind:{'serviceName':outputs[kind+'ServiceName'],'status':'ACTIVE','taskDefinition':'fixture-old-'+kind,
                             'desiredCount':2,'runningCount':2,'pendingCount':0,
                             'deployments':[{'status':'PRIMARY','taskDefinition':'fixture-old-'+kind,'rolloutState':'COMPLETED'}]} for kind in ('Api','Worker')}
        self.services['Api'].update(deploymentController={'type':'ECS'},deploymentConfiguration={
            'strategy':'BLUE_GREEN','bakeTimeInMinutes':5,'alarms':{'enable':True,'rollback':True,'alarmNames':[outputs[k] for k in ('ServerErrorAlarmName','LatencyAlarmName','ExternalCanaryAlarmName')]},
            'lifecycleHooks':[{'hookTargetArn':outputs['LifecycleHookArn'],'roleArn':outputs['HookInvocationRoleArn'],'lifecycleStages':['POST_TEST_TRAFFIC_SHIFT']}]},
            loadBalancers=[{'targetGroupArn':outputs['BlueTargetGroupArn'],'advancedConfiguration':{'alternateTargetGroupArn':outputs['GreenTargetGroupArn'],'productionListenerRule':outputs['ProductionRuleArn'],'testListenerRule':outputs['TestRuleArn'],'roleArn':outputs['AlbInfrastructureRoleArn']}}])
        self.session=botocore.session.get_session()
    def __call__(self, aws_service, operation, **arguments):
        model=self.session.get_service_model(aws_service).operation_model(''.join(part.capitalize() for part in operation.split('-')))
        validate_parameters(arguments,model.input_shape)
        self.calls.append((aws_service,operation,copy.deepcopy(arguments)))
        if operation==self.failure: raise RuntimeError('fixture transport failure with secret-do-not-log')
        o=self.outputs
        if operation=='get-caller-identity': return {'Account':self.account}
        if operation=='describe-stacks': return {'Stacks':[{'StackStatus':self.stack_status,'StackId':f'arn:aws:cloudformation:ap-northeast-1:{ACCOUNT}:stack/fixture/id','Outputs':[{'OutputKey':k,'OutputValue':v} for k,v in o.items()]}]}
        if operation=='describe-images': return {'imageDetails':[{'imageDigest':DIGEST}]}
        if operation=='describe-services': return {'services':copy.deepcopy(list(self.services.values()))}
        if operation=='describe-alarms': return {'MetricAlarms':[{'AlarmName':name,'StateValue':self.alarm_state,'ActionsEnabled':True} for name in arguments['AlarmNames']]}
        if operation=='get-function-configuration': return {'State':self.hook_state,'LastUpdateStatus':'Successful','CodeSha256':'fixture-approved-code','Environment':{'Variables':{'SECRET':'fixture-never-log'}}}
        if operation=='get-parameter':
            import json
            return {'Parameter':{'Type':self.ssm_type,'Value':json.dumps(self.environment)}}
        if operation=='describe-task-definition':
            kind=next(kind for kind in ('Api','Worker','Migration') if arguments['taskDefinition']==o[kind+'BootstrapTaskDefinitionArn'])
            template={'family':'fixture-'+kind+'-bootstrap','taskRoleArn':o[kind+'TaskRoleArn'],'executionRoleArn':o['MigrationExecutionRoleArn' if kind=='Migration' else 'AppExecutionRoleArn'],
                      'networkMode':'awsvpc','requiresCompatibilities':['FARGATE'],'cpu':'512','memory':'1024',
                      'runtimePlatform':{'cpuArchitecture':'ARM64','operatingSystemFamily':'LINUX'},
                      'containerDefinitions':[{'name':kind.lower(),'image':'fixture-unpublished','readonlyRootFilesystem':True,'essential':True,'stopTimeout':120}]}
            template.update(self.template_overrides)
            return {'taskDefinition':template}
        if operation=='get-queue-attributes': return {'Attributes':{'QueueArn':o['QueueArn'],'ApproximateNumberOfMessages':'0','ApproximateNumberOfMessagesNotVisible':'0'}}
        if operation=='register-task-definition': return {'taskDefinition':{'taskDefinitionArn':arguments['family']+':7'}}
        if operation=='run-task': return {'tasks':[{'taskArn':'fixture-migration-task'}]}
        if operation=='describe-tasks':
            container={'name':'migration'}
            if self.migration_exit is not None: container['exitCode']=self.migration_exit
            return {'tasks':[{'taskArn':'fixture-migration-task','taskDefinitionArn':'fixture-Migration-release:7','lastStatus':self.migration_status,'containers':[container]}]}
        if operation=='update-service':
            kind='Api' if arguments['service']==o['ApiServiceName'] else 'Worker'
            item=self.services[kind]; item['taskDefinition']=arguments['taskDefinition']; item['desiredCount']=arguments['desiredCount']; item['runningCount']=arguments['desiredCount']
            item['deployments']=[{'status':'PRIMARY','taskDefinition':arguments['taskDefinition'],'rolloutState':'FAILED' if kind=='Worker' and self.worker_failed else 'COMPLETED'}]
            if kind=='Api': self.api_updated=True
            return {'service':copy.deepcopy(item)}
        if operation=='list-service-deployments': return {'serviceDeployments':[{'serviceDeploymentArn':'fixture-old-deployment'}]+([{'serviceDeploymentArn':'fixture-new-deployment'}] if self.api_updated else [])}
        if operation=='describe-service-deployments': return {'serviceDeployments':[{
            'serviceDeploymentArn':'fixture-new-deployment','status':self.api_status,'deploymentConfiguration':self.services['Api']['deploymentConfiguration'],
            'targetServiceRevision':{'arn':'fixture-new-revision','runningTaskCount':self.services['Api']['desiredCount'],'pendingTaskCount':self.pending,'requestedProductionTrafficWeight':100},
            'sourceServiceRevisions':[{'runningTaskCount':0,'pendingTaskCount':0}]}]}
        if operation=='describe-service-revisions': return {'serviceRevisions':[{'serviceRevisionArn':'fixture-new-revision','taskDefinition':'fixture-wrong' if self.wrong_revision else self.services['Api']['taskDefinition']}]}
        raise AssertionError('Unhandled fake AWS call: '+operation)
