"""Pinned Workers build handoff and deployment records, controlled by backend release tags."""
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tarfile
import urllib.request
from urllib.parse import urlsplit

from contract import FRONTEND
from records import write_json

API_KEYS = ('KPOOL_WIKI_PRIVATE_API_BASE_URL', 'KPOOL_IDENTITY_API_BASE_URL',
            'KPOOL_ACCOUNT_API_BASE_URL', 'KPOOL_SITE_MANAGEMENT_API_BASE_URL')


def configuration_digest(config):
    return hashlib.sha256(json.dumps(config, sort_keys=True, separators=(',', ':')).encode()).hexdigest()


def validate_configuration(config, plan):
    if not isinstance(config, dict) or set(config) != {'paused', 'smoke_url', 'wrangler'}:
        raise ValueError('Unexpected frontend release configuration keys')
    if config.get('paused') is not False:
        raise ValueError('Frontend release configuration is missing or paused')
    wrangler = config.get('wrangler', {})
    allowed = {'$schema', 'name', 'account_id', 'main', 'compatibility_date', 'compatibility_flags',
               'workers_dev', 'preview_urls', 'assets', 'services', 'r2_buckets',
               'durable_objects', 'migrations', 'images', 'vars', 'routes'}
    if (not isinstance(wrangler, dict) or not set(wrangler) <= allowed
            or not re.fullmatch('[a-f0-9]{32}', wrangler.get('account_id', ''))
            or not re.fullmatch('[a-z0-9][a-z0-9-]{0,62}', wrangler.get('name', ''))
            or 'local' in wrangler['name']
            or wrangler.get('main') != '.open-next/worker.js'
            or wrangler.get('assets') != {'directory': '.open-next/assets', 'binding': 'ASSETS'}
            or wrangler.get('workers_dev') is not False or wrangler.get('preview_urls') is not False
            or set(wrangler.get('vars', {})) != set(API_KEYS)):
        raise ValueError('Workers configuration must specify approved resources and nonsecret API URLs')
    url = urlsplit(config.get('smoke_url', ''))
    if (url.scheme != 'https' or not url.hostname or url.username or url.password
            or url.port not in (None, 443) or url.query or url.fragment
            or wrangler.get('routes') != [{'pattern': url.hostname, 'custom_domain': True}]):
        raise ValueError('Smoke URL must belong to the configured Workers custom domain')
    if (plan.get('frontend_configuration_sha256')
            and plan['frontend_configuration_sha256'] != configuration_digest(config)):
        raise ValueError('Frontend configuration changed after the immutable plan')
    return wrangler


def prepare(source, config, plan):
    wrangler = validate_configuration(config, plan)
    actual = subprocess.check_output(['git', '-C', str(source), 'rev-parse', 'HEAD'], text=True).strip()
    if actual != plan['frontend_sha']:
        raise ValueError('Frontend checkout does not match the release plan')
    path = source / 'wrangler.deploy.json'
    write_json(path, wrangler)
    # Reuse the frontend's binding/API contract before build or Cloudflare writes.
    subprocess.run(['node', '--input-type=module', '-e',
                    "import {readFileSync} from 'node:fs'; "
                    "import {validateDeploymentConfig} from './scripts/workers-deployment-config.mjs'; "
                    "if(validateDeploymentConfig(JSON.parse(readFileSync('wrangler.deploy.json','utf8'))).length) "
                    "throw new Error('Invalid production Workers bindings/API URLs');"], cwd=source, check=True)


def identity(plan, config):
    return dict(schema=1, repository=FRONTEND, environment='production',
                release_id=plan['release_id'], frontend_sha=plan['frontend_sha'],
                backend_sha=plan['backend_sha'], workflow_sha=plan['workflow_sha'],
                configuration_sha256=configuration_digest(config))


def build_record(source, directory, config, plan):
    validate_configuration(config, plan)
    if not (source / '.open-next/worker.js').is_file() or not (source / '.open-next/assets').is_dir():
        raise ValueError('OpenNext production build is missing')
    directory.mkdir(parents=True, exist_ok=True)
    archive = directory / 'frontend-build.tar.gz'
    # One archive retains OpenNext hidden files and modes across artifact upload.
    with tarfile.open(archive, 'w:gz', dereference=True) as target:
        target.add(source / '.open-next', arcname='.open-next')
    record = dict(identity(plan, config), archive_sha256=hashlib.file_digest(archive.open('rb'), 'sha256').hexdigest(),
                  lockfile_sha256=hashlib.sha256((source / 'pnpm-lock.yaml').read_bytes()).hexdigest())
    write_json(directory / 'frontend-build.json', record, exclusive=True)


def restore(source, directory, config, plan):
    validate_configuration(config, plan)
    record = json.loads((directory / 'frontend-build.json').read_text())
    archive = directory / 'frontend-build.tar.gz'
    if (any(record.get(k) != v for k, v in identity(plan, config).items())
            or record.get('archive_sha256') != hashlib.file_digest(archive.open('rb'), 'sha256').hexdigest()
            or record.get('lockfile_sha256') != hashlib.sha256((source / 'pnpm-lock.yaml').read_bytes()).hexdigest()):
        raise ValueError('Frontend build provenance/checksum mismatch')
    if (source / '.open-next').exists():
        raise ValueError('Restore requires a clean frontend checkout')
    with tarfile.open(archive, 'r:gz') as target:
        members = target.getmembers()
        for member in members:
            path = Path(member.name)
            if (not path.parts or path.parts[0] != '.open-next' or '..' in path.parts
                    or not (member.isfile() or member.isdir())):
                raise ValueError('Unsafe frontend archive entry')
        target.extractall(source, members=members, filter='data')


def cloudflare(config, suffix):
    wrangler = config['wrangler']
    request = urllib.request.Request(
        'https://api.cloudflare.com/client/v4/accounts/' + wrangler['account_id'] + suffix,
        headers={'Authorization': 'Bearer ' + os.environ['CLOUDFLARE_API_TOKEN']})
    with urllib.request.urlopen(request, timeout=30) as response:
        value = json.load(response)
    if value.get('success') is not True:
        raise ValueError('Cloudflare metadata lookup failed')
    return value['result']


def deployed_version(config):
    result = cloudflare(config, '/workers/scripts/' + config['wrangler']['name'] + '/deployments')
    deployments = result['deployments']
    if not deployments:
        return None
    current = max(deployments, key=lambda item: item['created_on'])
    versions = current['versions']
    if len(versions) != 1 or versions[0]['percentage'] != 100:
        raise ValueError('Workers deployment is not a single stable version')
    return {'deployment_id': current['id'], 'version_id': versions[0]['version_id']}


def preflight(directory, config, plan):
    validate_configuration(config, plan)
    # Check the pre-created R2 bucket and runtime Secret by metadata only.
    for bucket in config['wrangler']['r2_buckets']:
        if cloudflare(config, '/r2/buckets/' + bucket['bucket_name'])['name'] != bucket['bucket_name']:
            raise ValueError('R2 bucket ownership/name mismatch')
    keys = cloudflare(config, '/workers/scripts/' + config['wrangler']['name'] + '/secrets')
    if not any(key['name'] == 'WIKI_VISITOR_LOCATION_SECRET' for key in keys):
        raise ValueError('Provision the Wiki signing Secret on the target Worker first')
    write_json(directory / 'frontend-before.json', dict(identity(plan, config), previous=deployed_version(config)), exclusive=True)


def observe(directory, config, plan):
    # Run even after deploy fails: a Cloudflare write may have succeeded already.
    value = dict(identity(plan, config), status='unverified')
    try:
        value['current'] = deployed_version(config)
        before = json.loads((directory / 'frontend-before.json').read_text())
        value['previous'] = before['previous']
        if os.environ.get('FRONTEND_DEPLOY_OUTCOME') != 'success':
            raise ValueError('Deployment command failed or was interrupted')
        if not value['current'] or value['current'] == value['previous']:
            raise ValueError('No new Workers deployment observed')
        entries = [json.loads(line) for line in (directory / 'wrangler-output.jsonl').read_text().splitlines() if line.strip()]
        deployed = [entry for entry in entries if entry.get('type') == 'deploy']
        if (len(deployed) != 1 or deployed[0].get('version') != 1
                or deployed[0].get('worker_name') != config['wrangler']['name']
                or deployed[0].get('version_id') != value['current']['version_id']):
            raise ValueError('Live Workers version differs from this deployment command')
        url = config['smoke_url']
        with urllib.request.urlopen(url, timeout=30) as response:
            if response.status != 200 or response.geturl() != url:
                raise ValueError('Frontend HTTPS smoke failed or redirected')
            response.read(4096)
        value['status'] = 'success'
    finally:
        write_json(directory / 'frontend-deployment.json', value)


def main():
    from cli import validate_plan
    command = sys.argv[1]
    plan = validate_plan(json.loads(Path('release/plan.json').read_text()))
    if plan['target'] not in ('frontend', 'all'):
        raise ValueError('Not a frontend release')
    config = json.loads(os.environ['FRONTEND_RELEASE_CONFIGURATION'])
    validate_configuration(config, plan)
    source, directory = Path('source'), Path('release')
    commands = {'prepare': prepare, 'build-record': build_record, 'restore': restore}
    if command in commands:
        if command == 'prepare':
            prepare(source, config, plan)
        else:
            commands[command](source, directory, config, plan)
    elif command == 'preflight':
        preflight(directory, config, plan)
    elif command == 'observe':
        observe(directory, config, plan)
    else:
        raise ValueError('Unknown frontend release command')


if __name__ == '__main__':
    try:
        main()
    except Exception as error:
        # API errors and external tool output may include sensitive data.
        print('Frontend release stopped: ' + type(error).__name__, file=sys.stderr)
        sys.exit(1)
