"""Workflow entry points. Inputs are environment/data, never interpolated shell code."""
import hashlib
import json
import os
from pathlib import Path
import signal
import subprocess
import sys
from contract import BACKEND, FRONTEND, SHA, DIGEST, validate_source, validate_ca
from github_source import resolve_source, recorded_artifacts
from aws_backend import Aws, Backend, stack_outputs
from records import Journal, write_json
from runner import execute

ROOT=Path('release')


def load(path): return json.loads(Path(path).read_text())


def output(**values):
    with open(os.environ['GITHUB_OUTPUT'],'a') as stream:
        for key,value in values.items():
            if '\n' in str(value): raise ValueError('Output contains newline')
            stream.write(f'{key}={value}\n')


def validate_plan(plan):
    if (plan.get('schema')!=1 or plan.get('repository')!=BACKEND or plan.get('environment')!='production'
            or plan.get('target') not in ('backend','all','frontend')
            or plan.get('mode') not in ('deploy','redeploy','resume-worker','rollback-api','rollback-worker')
            or not SHA.fullmatch(plan.get('workflow_sha',''))
            or not SHA.fullmatch(plan.get('live_frontend_sha',''))
            or not isinstance(plan.get('initial'),bool)):
        raise ValueError('Release plan schema/environment is invalid')
    validate_source(BACKEND,plan['backend_sha'])
    return plan


def validate_manifest(manifest, plan):
    if (manifest.get('schema')!=1 or manifest.get('repository')!=BACKEND
            or manifest.get('environment')!='production' or manifest.get('backend_sha')!=plan['backend_sha']
            or manifest.get('release_id')!=plan.get('source_release_id',plan['release_id'])
            or not DIGEST.fullmatch(manifest.get('image_digest',''))
            or not SHA.fullmatch(manifest.get('workflow_sha',''))):
        raise ValueError('Image record ownership/schema/digest mismatch')
    return manifest


def plan_release():
    ROOT.mkdir(exist_ok=True)
    if os.environ['GITHUB_REPOSITORY']!=BACKEND or os.environ['GITHUB_REF']!='refs/heads/main':
        raise ValueError('Production control workflow must run from backend main')
    target=os.environ['RELEASE_TARGET']; mode=os.environ['RELEASE_MODE']
    control=os.environ['CONTROL_SHA']
    if not SHA.fullmatch(control): raise ValueError('Control workflow SHA must be complete')
    live_frontend_sha=os.environ.get('LIVE_FRONTEND_SHA','')
    if not SHA.fullmatch(live_frontend_sha): raise ValueError('Current live frontend SHA must be complete')
    recorded=os.environ.get('SOURCE_RUN','')
    attempt=int(os.environ['GITHUB_RUN_ATTEMPT'])
    if attempt>1 and not recorded: recorded=os.environ['GITHUB_RUN_ID']
    plan={'schema':1,'repository':BACKEND,'environment':'production','target':target,'mode':mode,
          'workflow_sha':control,'live_frontend_sha':live_frontend_sha,'initial':os.environ['INITIAL_ACTIVATION']=='true',
          'release_id':os.environ['GITHUB_RUN_ID']+'-'+str(attempt),
          'run_id':os.environ['GITHUB_RUN_ID'],'run_attempt':attempt,
          'run_url':os.environ['GITHUB_SERVER_URL']+'/'+BACKEND+'/actions/runs/'+os.environ['GITHUB_RUN_ID']}
    if recorded:
        original,image,record=recorded_artifacts(recorded,current=recorded==os.environ['GITHUB_RUN_ID'],attempt=os.environ.get('SOURCE_ATTEMPT','1'))
        source=validate_plan(original['plan.json'])
        plan.update(backend_sha=source['backend_sha'],
                    source_run=str(recorded),source_release_id=source.get('source_release_id',source['release_id']),initial=False)
        if target!='backend': raise ValueError('Frontend replay awaits the pinned frontend workflow contract')
        manifest=record.get('deployment-manifest.json',record.get('manifest.json',image.get('manifest.json')))
        if not manifest: raise ValueError('Recorded image manifest is unavailable; do not rebuild latest source')
        if attempt>1 and mode=='deploy': plan['mode']='redeploy'
        if plan['mode']=='deploy': raise ValueError('Recorded digest requires an explicit replay or rollback mode')
        validate_manifest(manifest,plan)
        previous=record.get('journal.json')
        if not previous or previous.get('repository')!=BACKEND or previous.get('environment')!='production' or previous.get('release_id')!=source['release_id']:
            raise ValueError('Recorded deployment journal is missing or mismatched')
        write_json(ROOT/'manifest.json',manifest,exclusive=True)
        write_json(ROOT/'previous.json',previous,exclusive=True)
    else:
        if mode!='deploy': raise ValueError('Replay/rollback requires source_run')
        plan['backend_sha']=resolve_source(BACKEND,os.environ['BACKEND_REF'])
    validate_plan(plan)
    write_json(ROOT/'plan.json',plan,exclusive=True)
    # Fail before build/publication/AWS when frontend integration is not installed.
    if target!='backend':
        contract=json.loads(os.environ.get('FRONTEND_CONTRACT') or '{}')
        if (contract.get('repository')!=FRONTEND or not SHA.fullmatch(contract.get('workflow_sha',''))
                or not SHA.fullmatch(contract.get('source_sha',''))):
            raise ValueError('Frontend pinned reusable workflow/source contract is missing')
        raise ValueError('Frontend reusable workflow is not connected: #427 contract is required')
    output(backend_sha=plan['backend_sha'],mode=plan['mode'],release_id=plan['release_id'])


def prepare_ca():
    data=validate_ca(os.environ['RDS_CA_PEM'],os.environ['RDS_CA_SHA256'])
    ROOT.mkdir(exist_ok=True)
    (ROOT/'rds-ca.pem').write_bytes(data)
    subprocess.run(['openssl','crl2pkcs7','-nocrl','-certfile',str(ROOT/'rds-ca.pem'),'-out',str(ROOT/'ca-validation.p7b')],check=True,capture_output=True)
    (ROOT/'ca-validation.p7b').unlink()


def build_record():
    plan=validate_plan(load(ROOT/'plan.json'))
    image=ROOT/'backend-image.tar'
    installed=subprocess.check_output(['docker','run','--rm','--entrypoint','sha256sum','kpool-release:'+plan['backend_sha'],'/etc/ssl/certs/kpool-rds-ca.pem'],text=True).split()[0]
    if installed!=os.environ['RDS_CA_SHA256']: raise ValueError('Built image public CA checksum mismatch')
    digest=hashlib.file_digest(image.open('rb'),'sha256').hexdigest()
    write_json(ROOT/'build.json',dict(schema=1,backend_sha=plan['backend_sha'],platform='linux/arm64',target='production',archive_sha256=digest,rds_ca_sha256=os.environ['RDS_CA_SHA256']),exclusive=True)


def configuration():
    value=json.loads(os.environ['RELEASE_CONFIGURATION'])
    # No fallback account/ARN/stack/secret values.
    if not isinstance(value,dict) or value.get('region')!='ap-northeast-1': raise ValueError('Invalid release configuration')
    return value


def publish():
    plan=validate_plan(load(ROOT/'plan.json')); config=configuration(); aws=Aws()
    if config.get('paused') is not False: raise ValueError('Release is paused')
    account=config['account_id']
    if aws('sts','get-caller-identity')['Account']!=account: raise ValueError('AWS account mismatch')
    bootstrap=stack_outputs(aws,config['stacks']['bootstrap'],account)
    if bootstrap['DeploymentRoleArn']!=config['deployment_role_arn']: raise ValueError('Bootstrap role mismatch')
    outputs={}
    for key in ('root','integration','runtime'): outputs.update(stack_outputs(aws,config['stacks'][key],account))
    uri=outputs['RepositoryUri']
    if not uri.startswith(account+'.dkr.ecr.ap-northeast-1.amazonaws.com/'): raise ValueError('ECR ownership mismatch')
    record=load(ROOT/'build.json'); archive=ROOT/'backend-image.tar'
    if (record['schema']!=1 or record['backend_sha']!=plan['backend_sha'] or record['platform']!='linux/arm64'
            or record['target']!='production' or record['archive_sha256']!=hashlib.file_digest(archive.open('rb'),'sha256').hexdigest()):
        raise ValueError('Validated build artifact integrity mismatch')
    # Full backend preflight before ECR publication, using an existing bootstrap image
    # would be impossible on first release. Non-image preflight is run separately.
    preflight_manifest={'repository_uri':uri,'image_digest':None}
    journal=Journal(ROOT/'publication',plan)
    backend=Backend(aws,plan,preflight_manifest,journal,config)
    backend.preflight(check_image=False)
    tag='release-'+plan['release_id']
    subprocess.run(['docker','load','--input',str(archive)],check=True,capture_output=True)
    image='kpool-release:'+plan['backend_sha']
    inspection=json.loads(subprocess.check_output(['docker','image','inspect',image]))[0]
    if inspection['Architecture']!='arm64' or inspection['Os']!='linux': raise ValueError('Build image is not Linux ARM64')
    subprocess.run(['docker','tag',image,uri+':'+tag],check=True)
    password=subprocess.run(['aws','ecr','get-login-password','--region','ap-northeast-1'],capture_output=True,check=True).stdout
    subprocess.run(['docker','login','--username','AWS','--password-stdin',uri.split('/')[0]],input=password,capture_output=True,check=True)
    try:
        subprocess.run(['docker','push',uri+':'+tag],check=True,capture_output=True,timeout=900)
    finally:
        subprocess.run(['docker','logout',uri.split('/')[0]],capture_output=True)
    details=aws('ecr','describe-images',repositoryName=uri.split('/',1)[1],imageIds=[{'imageTag':tag}])['imageDetails']
    if len(details)!=1 or not DIGEST.fullmatch(details[0]['imageDigest']): raise ValueError('Published digest missing')
    manifest=dict(plan,repository_uri=uri,image_digest=details[0]['imageDigest'],build_archive_sha256=record['archive_sha256'],rds_ca_sha256=record['rds_ca_sha256'])
    write_json(ROOT/'manifest.json',manifest,exclusive=True)
    output(image_digest=manifest['image_digest'])


def deploy():
    plan=validate_plan(load(ROOT/'plan.json')); manifest=validate_manifest(load(ROOT/'manifest.json'),plan)
    previous=load(ROOT/'previous.json') if (ROOT/'previous.json').exists() else None
    journal=Journal(ROOT/'records',plan)
    # Copy provenance into records, retaining publication manifest unchanged.
    write_json(ROOT/'records'/'manifest.json',manifest,exclusive=True)
    backend=Backend(Aws(),plan,manifest,journal,configuration(),previous)
    execute(backend,journal,plan['mode'])


def summary():
    # Only approved identity and enum status fields reach the job summary.
    plan=load(ROOT/'plan.json') if (ROOT/'plan.json').exists() else {}
    with open(os.environ['GITHUB_STEP_SUMMARY'],'a') as stream:
        stream.write('## Backend release\n')
        for key in ('release_id','environment','backend_sha','live_frontend_sha','workflow_sha','run_url'):
            value=plan.get(key,'unavailable')
            stream.write(f'- {key}: `{value}`\n')
        if (ROOT/'records'/'journal.json').exists():
            for event in load(ROOT/'records'/'journal.json')['events']:
                stream.write(f"- {event['stage']}: {event['status']}\n")
        stream.write('- SQS job processing: NOT VERIFIED (read-only queue attributes only)\n')


def interrupted(signum,frame): raise InterruptedError('Workflow interrupted')


if __name__=='__main__':
    signal.signal(signal.SIGTERM,interrupted)
    commands={'plan':plan_release,'prepare-ca':prepare_ca,'build-record':build_record,'publish':publish,'deploy':deploy,'summary':summary}
    try: commands[sys.argv[1]]()
    except BaseException as error:
        # Never print raw subprocess output, config/secret values, or stack traces.
        ROOT.mkdir(exist_ok=True)
        write_json(ROOT/'failure.json',{'command':sys.argv[1],'error_type':type(error).__name__})
        print('Release stopped: '+type(error).__name__+' (consult stage records and operator gates)',file=sys.stderr)
        sys.exit(1)
