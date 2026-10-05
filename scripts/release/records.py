"""Append-only release events plus atomic convenience snapshot. Never store raw errors."""
import datetime
import json
from pathlib import Path


def write_json(path, value, exclusive=False):
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    if exclusive:
        with path.open('x') as stream:
            json.dump(value, stream, sort_keys=True, indent=2)
    else:
        temporary = path.with_suffix('.tmp')
        temporary.write_text(json.dumps(value, sort_keys=True, indent=2))
        temporary.replace(path)


class Journal:
    def __init__(self, directory, identity):
        self.directory = Path(directory)
        self.value = dict(identity, events=[])
        write_json(self.directory/'journal.json', self.value)

    def event(self, stage, status, **fields):
        allowed = {'task_definition', 'deployment_arn', 'revision_arn', 'task_arn',
                   'api', 'worker', 'error_type', 'desired', 'health', 'queue', 'queue_processing',
                   'image_digest', 'source_run', 'api_desired', 'worker_desired'}
        if not set(fields) <= allowed:
            raise ValueError('Unapproved journal field')
        event = dict(stage=stage, status=status, timestamp=datetime.datetime.now(datetime.timezone.utc).isoformat(), **fields)
        index = len(self.value['events'])
        write_json(self.directory/f'event-{index:04d}.json', event, exclusive=True)
        self.value['events'].append(event)
        write_json(self.directory/'journal.json', self.value)
