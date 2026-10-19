"""Clearly labeled fake AWS boundary fixtures. No production defaults."""
import unittest
from deployment import migration_complete

class MigrationTests(unittest.TestCase):
    def test_stopped_requires_exact_task_and_exit_zero(self):
        for task in ({'lastStatus':'STOPPED','containers':[{'name':'migration'}]},
                     {'lastStatus':'STOPPED','containers':[{'name':'migration','exitCode':1}]},
                     {'lastStatus':'STOPPED','containers':[]}):
            with self.subTest(task=task), self.assertRaises(ValueError):
                migration_complete(task)
        self.assertFalse(migration_complete({'lastStatus':'RUNNING'}))
        self.assertTrue(migration_complete({'lastStatus':'STOPPED','containers':[{'name':'migration','exitCode':0}]}))

class ApiTests(unittest.TestCase):
    def test_success_requires_exact_revision_zero_bake_and_old_tasks_removed(self):
        from deployment import api_complete
        deployment = {'serviceDeploymentArn':'fixture-deployment', 'status':'SUCCESSFUL',
                      'deploymentConfiguration':{'strategy':'BLUE_GREEN','bakeTimeInMinutes':0},
                      'targetServiceRevision':{'arn':'fixture-revision','runningTaskCount':2,'pendingTaskCount':0,'requestedTaskCount':2,'requestedProductionTrafficWeight':100},
                      'sourceServiceRevisions':[{'runningTaskCount':0,'pendingTaskCount':0}]}
        revision = {'serviceRevisionArn':'fixture-revision','taskDefinition':'fixture-task'}
        self.assertTrue(api_complete(deployment, revision, 'fixture-deployment', 'fixture-task', 2))
        for configuration in (
                {'strategy': 'BLUE_GREEN', 'bakeTimeInMinutes': 5},
                {'strategy': 'BLUE_GREEN', 'bakeTimeInMinutes': 0, 'alarms': {'enable': True}},
                {'strategy': 'BLUE_GREEN', 'bakeTimeInMinutes': 0, 'alarms': {'rollback': True}}):
            with self.subTest(configuration=configuration), self.assertRaises(ValueError):
                api_complete(dict(deployment, deploymentConfiguration=configuration), revision,
                             'fixture-deployment', 'fixture-task', 2)
        for key, value in [('status','ROLLBACK_SUCCESSFUL'),('serviceDeploymentArn','old-stable')]:
            broken = dict(deployment, **{key:value})
            with self.subTest(key=key), self.assertRaises(ValueError):
                api_complete(broken, revision, 'fixture-deployment','fixture-task',2)
        with self.assertRaises(ValueError):
            api_complete(deployment, dict(revision,taskDefinition='wrong'), 'fixture-deployment','fixture-task',2)
        deployment['sourceServiceRevisions'][0]['runningTaskCount'] = 1
        self.assertFalse(api_complete(deployment,revision,'fixture-deployment','fixture-task',2))

class PollTests(unittest.TestCase):
    def test_poll_is_finite_and_initial_activation_is_explicit(self):
        from deployment import poll, desired_count, worker_complete
        clock = iter([0,0,1,2,3])
        with self.assertRaises(TimeoutError):
            poll(lambda: False, timeout=2, interval=0, clock=lambda:next(clock), sleep=lambda _:None)
        self.assertEqual(desired_count(3,False),3)
        self.assertEqual(desired_count(0,True),1)
        for count, initial in [(0,False),(2,True)]:
            with self.assertRaises(ValueError): desired_count(count,initial)
        service = {'taskDefinition':'fixture-new','desiredCount':2,'runningCount':2,'pendingCount':0,
                   'deployments':[{'status':'PRIMARY','taskDefinition':'fixture-new','rolloutState':'COMPLETED'}]}
        self.assertTrue(worker_complete(service,'fixture-new',2))
        service['deployments'][0]['rolloutState']='FAILED'
        with self.assertRaises(ValueError): worker_complete(service,'fixture-new',2)

class ServiceContractTests(unittest.TestCase):
    def test_missing_hook_is_allowed_only_for_explicit_inactive_bootstrap(self):
        import copy
        from deployment import validate_api_service, initial_api_configuration
        from fixtures import fixture, FakeAws
        outputs, environment, _, _, _ = fixture()
        service = FakeAws(outputs, environment).services['Api']
        service.update(desiredCount=0, runningCount=0, pendingCount=0,
                       taskDefinition=outputs['ApiBootstrapTaskDefinitionArn'])
        service['deploymentConfiguration'].pop('lifecycleHooks')
        validate_api_service(service, outputs, allow_bootstrap=True)
        with self.assertRaises(ValueError): validate_api_service(service, outputs)
        for key, value in (('desiredCount', 1), ('runningCount', 1), ('pendingCount', 1),
                           ('taskDefinition', 'unapproved-release')):
            changed = copy.deepcopy(service); changed[key] = value
            with self.subTest(key=key), self.assertRaises(ValueError):
                validate_api_service(changed, outputs, allow_bootstrap=True)
        config = initial_api_configuration(service, outputs)
        self.assertNotIn('lifecycleHooks', service['deploymentConfiguration'])
        validate_api_service(dict(service, deploymentConfiguration=config), outputs)

    def test_hooks_and_target_group_role_swap_are_checked(self):
        from deployment import validate_api_service
        outputs = {k:'fixture-'+k for k in ('LifecycleHookArn','HookInvocationRoleArn','ServerErrorAlarmName','LatencyAlarmName','ExternalCanaryAlarmName','BlueTargetGroupArn','GreenTargetGroupArn','ProductionRuleArn','TestRuleArn','AlbInfrastructureRoleArn')}
        configuration = {'strategy':'BLUE_GREEN','bakeTimeInMinutes':0,
                         'alarms':{'enable':False,'rollback':False,'alarmNames':[]},
                         'lifecycleHooks':[{'hookTargetArn':outputs['LifecycleHookArn'],'roleArn':outputs['HookInvocationRoleArn'],'lifecycleStages':['POST_TEST_TRAFFIC_SHIFT']}]}
        service = {'deploymentController':{'type':'ECS'},'deploymentConfiguration':configuration,
                   'loadBalancers':[{'targetGroupArn':outputs['BlueTargetGroupArn'], 'advancedConfiguration':{'alternateTargetGroupArn':outputs['GreenTargetGroupArn'], 'productionListenerRule':outputs['ProductionRuleArn'], 'testListenerRule':outputs['TestRuleArn'],'roleArn':outputs['AlbInfrastructureRoleArn']}}]}
        validate_api_service(service, outputs)
        service['loadBalancers'][0]['targetGroupArn']=outputs['GreenTargetGroupArn']
        service['loadBalancers'][0]['advancedConfiguration']['alternateTargetGroupArn']=outputs['BlueTargetGroupArn']
        validate_api_service(service, outputs)
        for key,value in [('strategy','ROLLING'),('lifecycleHooks',[])]:
            original=configuration[key]; configuration[key]=value
            with self.subTest(key=key), self.assertRaises(ValueError): validate_api_service(service,outputs)
            configuration[key]=original
