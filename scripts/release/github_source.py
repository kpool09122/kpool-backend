"""Pinned source resolution and repository-owned artifact retrieval using read-only GH API."""
import io
import json
import subprocess
import zipfile
from contract import BACKEND, FRONTEND, RELEASE_BRANCH, RELEASE_TAG, SHA, validate_source


def gh(path, binary=False):
    result=subprocess.run(['gh','api',path],capture_output=True,timeout=60)
    if result.returncode:
        raise RuntimeError('GitHub read failed')
    return result.stdout if binary else json.loads(result.stdout)


def resolve_source(repository, ref):
    if repository not in (BACKEND, FRONTEND):
        raise ValueError('Unknown source repository')
    if RELEASE_TAG.fullmatch(ref):
        # Fully qualified tag lookup distinguishes tags from same-named branches.
        target = gh(f'repos/{repository}/git/ref/tags/{ref}')['object']
        for _ in range(8):
            validate_source(repository, target['sha'])
            if target['type'] == 'commit':
                sha = target['sha']
                break
            if target['type'] != 'tag':
                raise ValueError('Release tag must point to a commit')
            target = gh(f'repos/{repository}/git/tags/{target["sha"]}')['object']
        else:
            raise ValueError('Release tag nesting exceeds limit')
    elif SHA.fullmatch(ref):
        sha = gh(f'repos/{repository}/commits/{ref}')['sha']
        if sha != ref:
            raise ValueError('Pinned source SHA changed')
    else:
        raise ValueError('Production source must be a v-prefixed release tag')
    return validate_source(repository, sha)


def verify_control(ref, sha):
    if not ref.startswith('refs/tags/') or not RELEASE_TAG.fullmatch(ref.removeprefix('refs/tags/')):
        raise ValueError('Production workflow must run from a v-prefixed release tag')
    # GitHub supplies workflow_sha for this invocation; do not reread a tag that may have moved.
    validate_source(BACKEND, sha)


def recorded_artifacts(run_id, current=False, attempt=1):
    if not str(run_id).isdigit() or int(run_id)<=0:
        raise ValueError('Recorded run ID must be a positive integer')
    if not str(attempt).isdigit() or int(attempt)<=0: raise ValueError('Recorded attempt must be positive')
    run=gh(f'repos/{BACKEND}/actions/runs/{run_id}')
    workflow=gh(f'repos/{BACKEND}/actions/workflows/{run["workflow_id"]}')
    if (run['repository']['full_name']!=BACKEND or run['event']!='workflow_dispatch'
            or not (run['head_branch']=='main' or RELEASE_BRANCH.fullmatch(run['head_branch'] or '') or RELEASE_TAG.fullmatch(run['head_branch'] or ''))
            or workflow['path']!='.github/workflows/release.yml'
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
    plan, image, record = select('release-plan'), select('backend-image'), select('backend-record')
    if run['head_branch'] != 'main':
        source = plan.get('plan.json', {})
        prefix = 'refs/heads/' if RELEASE_BRANCH.fullmatch(run['head_branch']) else 'refs/tags/'
        if (source.get('workflow_ref') != prefix + run['head_branch']
                or source.get('workflow_sha') != run.get('head_sha')):
            raise ValueError('Recorded release ref/SHA does not match the workflow run')
        validate_source(BACKEND, source['workflow_sha'])
    return plan, image, record
