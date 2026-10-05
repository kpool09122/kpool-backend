"""Pinned source resolution and repository-owned artifact retrieval using read-only GH API."""
import io
import json
import os
import subprocess
import zipfile
from contract import BACKEND, FRONTEND, SHA, validate_source


def gh(path, binary=False):
    result=subprocess.run(['gh','api',path],capture_output=True,timeout=60)
    if result.returncode:
        raise RuntimeError('GitHub read failed')
    return result.stdout if binary else json.loads(result.stdout)


def resolve_source(repository, ref):
    if repository not in (BACKEND,FRONTEND) or not (ref=='main' or SHA.fullmatch(ref)):
        raise ValueError('Production source must be main or an approved full SHA')
    sha=gh(f'repos/{repository}/commits/{ref}')['sha']
    validate_source(repository,sha)
    comparison=gh(f'repos/{repository}/compare/{sha}...main')
    if comparison['status'] not in ('ahead','identical'):
        raise ValueError('Production source is not reachable from main')
    return sha


def recorded_artifacts(run_id, current=False, attempt=1):
    if not str(run_id).isdigit() or int(run_id)<=0:
        raise ValueError('Recorded run ID must be a positive integer')
    if not str(attempt).isdigit() or int(attempt)<=0: raise ValueError('Recorded attempt must be positive')
    run=gh(f'repos/{BACKEND}/actions/runs/{run_id}')
    workflow=gh(f'repos/{BACKEND}/actions/workflows/{run["workflow_id"]}')
    if (run['repository']['full_name']!=BACKEND or run['event']!='workflow_dispatch'
            or run['head_branch']!='main' or workflow['path']!='.github/workflows/release.yml'
            or (not current and run['status']!='completed')):
        raise ValueError('Recorded run ownership/event/environment is not trusted')
    # Pagination is explicit: old records are never replaced by a latest-source lookup.
    artifacts=[]
    for page in range(1,21):
        items=gh(f'repos/{BACKEND}/actions/runs/{run_id}/artifacts?per_page=100&page={page}')['artifacts']
        artifacts.extend(a for a in items if not a['expired'])
        if len(items)<100: break
    else: raise ValueError('Artifact listing exceeds bounded limit')
    def read(artifact):
        archive=gh(f'repos/{BACKEND}/actions/artifacts/{artifact["id"]}/zip',binary=True)
        if len(archive)>10*1024*1024: raise ValueError('Record archive is too large')
        values={}
        names=('plan.json','manifest.json','deployment-manifest.json','journal.json')
        accepted={name:name for name in names}
        accepted.update({'records/'+name:name for name in names})
        with zipfile.ZipFile(io.BytesIO(archive)) as zipped:
            for item in zipped.infolist():
                # Never extract arbitrary artifact paths or execute artifact scripts.
                name=accepted.get(item.filename)
                if name is not None:
                    if name in values: raise ValueError('Duplicate record file')
                    if item.file_size>1024*1024: raise ValueError('Record file is too large')
                    values[name]=json.loads(zipped.read(item))
        return values
    def select(prefix):
        matches=[a for a in artifacts if a['name']==prefix+'-'+str(attempt)]
        if not matches: return {}
        return read(max(matches,key=lambda a:a['id']))
    return select('release-plan'),select('backend-image'),select('backend-record')
