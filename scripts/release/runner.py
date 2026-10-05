"""Stage orchestration. Frontend retry never enters this AWS runner."""


def execute(backend, journal, mode):
    stages = {'deploy': ('preflight','register','migration','api','worker','smoke'),
              'redeploy': ('preflight','register','api','worker','smoke'),
              'resume-worker': ('preflight','worker','smoke'),
              'rollback-api': ('preflight','api','smoke'),
              'rollback-worker': ('preflight','worker','smoke')}
    if mode not in stages:
        raise ValueError('Unsupported backend execution mode')
    try:
        for stage in stages[mode]:
            journal.event(stage, 'started')
            try:
                getattr(backend, stage)()
            except BaseException as error:
                journal.event(stage, 'failed', error_type=type(error).__name__)
                raise
            journal.event(stage, 'success')
    finally:
        try:
            backend.observe()
        except BaseException as error:
            journal.event('live', 'unavailable', error_type=type(error).__name__)
