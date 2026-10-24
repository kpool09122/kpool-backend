"""Update existing scheduler targets while retaining the live operator configuration."""
import copy
import re


def read_schedule(aws, arn, account):
    match = re.fullmatch(r'arn:aws:scheduler:ap-northeast-1:' + re.escape(account)
                         + r':schedule/([^/]+)/([^/]+)', arn)
    if not match:
        raise ValueError('Scheduler ARN is outside the release account/region')
    group, name = match.groups()
    schedule = aws('scheduler', 'get-schedule', GroupName=group, Name=name)
    if (schedule.get('Arn') != arn or schedule.get('GroupName') != group
            or schedule.get('Name') != name or schedule.get('State') not in ('ENABLED', 'DISABLED')
            or not schedule.get('Target', {}).get('EcsParameters', {}).get('TaskDefinitionArn')):
        raise ValueError('Scheduler identity/state/target is incomplete')
    return schedule


def update_schedule(aws, arn, account, task_arn, journal):
    current = read_schedule(aws, arn, account)
    journal.event('schedulers', 'requesting', schedule_arn=arn,
                  schedule_state=current['State'], task_definition=task_arn)
    if current['Target']['EcsParameters']['TaskDefinitionArn'] != task_arn:
        # UpdateSchedule replaces the complete configuration; forward every supported setting.
        selected = ('Name GroupName Description StartDate EndDate ScheduleExpression '
                    'ScheduleExpressionTimezone State FlexibleTimeWindow Target KmsKeyArn ActionAfterCompletion').split()
        request = {key: copy.deepcopy(current[key]) for key in selected if key in current}
        request['Target']['EcsParameters']['TaskDefinitionArn'] = task_arn
        aws('scheduler', 'update-schedule', **request)
        verified = read_schedule(aws, arn, account)
        if (verified['State'] != current['State']
                or verified['Target']['EcsParameters']['TaskDefinitionArn'] != task_arn):
            raise ValueError('Scheduler target/state changed during release; inspect before retry')
    journal.event('schedulers', 'observed', schedule_arn=arn,
                  schedule_state=current['State'], task_definition=task_arn)
