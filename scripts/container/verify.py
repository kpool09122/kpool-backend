#!/usr/bin/env python3
"""Isolated, disposable production-image acceptance tests (no source/vendor mount)."""
import json
import os
import subprocess
import tarfile
import time
import uuid

IMAGE = os.environ.get('PRODUCTION_IMAGE', 'kpool-backend:production')
PREFIX = 'kpool-container-' + uuid.uuid4().hex[:10]
NETWORK = PREFIX
CONTAINERS = []
ENV = {
    'APP_KEY': 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    'APP_URL': 'http://localhost:8080', 'FRONTEND_URL': 'http://localhost:3000',
    'DB_CONNECTION': 'pgsql', 'DB_HOST': PREFIX + '-db', 'DB_PORT': '5432',
    'DB_DATABASE': 'kpool', 'DB_USERNAME': 'kpool', 'DB_PASSWORD': 'smoke-only',
    'REDIS_HOST': PREFIX + '-redis', 'QUEUE_CONNECTION': 'redis',
}


def docker(*args, check=True):
    result = subprocess.run(['docker', *args], text=True, capture_output=True)
    if check and result.returncode:
        raise RuntimeError(f'docker {args}: {result.stdout}\n{result.stderr}')
    return result


def wait_for(probe, description, timeout=60):
    deadline = time.monotonic() + timeout
    while time.monotonic() < deadline:
        if probe():
            return
        time.sleep(0.25)
    raise AssertionError('timeout: ' + description)


def run(name, *command, detached=False):
    name = PREFIX + '-' + name
    CONTAINERS.append(name)
    args = ['run', '--name', name, '--network', NETWORK, '--read-only',
            '--cap-drop=ALL', '--security-opt=no-new-privileges',
            '--tmpfs', '/tmp:uid=1000,gid=1000,mode=1770',
            '--tmpfs', '/var/www/html/storage:uid=1000,gid=1000,mode=770',
            '--tmpfs', '/var/www/html/bootstrap/cache:uid=1000,gid=1000,mode=770']
    for key, value in ENV.items():
        args += ['-e', key + '=' + value]
    if detached:
        args += ['-d']
    result = docker(*args, IMAGE, *command, check=False)
    if detached and result.returncode:
        raise RuntimeError(result.stderr)
    return name, result


def stop(name):
    start = time.monotonic()
    docker('stop', '--time', '120', name)
    duration = time.monotonic() - start
    state = json.loads(docker('inspect', name).stdout)[0]['State']
    assert state['ExitCode'] == 0 and not state['OOMKilled'], state
    assert duration < 120, duration
    return duration


try:
    assert json.loads(docker('image', 'inspect', IMAGE).stdout)[0]['Architecture'] == 'arm64'
    docker('network', 'create', NETWORK)
    for suffix, image, options in [
        ('db', 'postgres:18-alpine', ['-e', 'POSTGRES_DB=kpool', '-e', 'POSTGRES_USER=kpool', '-e', 'POSTGRES_PASSWORD=smoke-only']),
        ('redis', 'redis:8-alpine', []),
    ]:
        name = PREFIX + '-' + suffix
        CONTAINERS.append(name)
        docker('run', '-d', '--name', name, '--network', NETWORK, *options, image)
    wait_for(lambda: docker('exec', PREFIX + '-db', 'pg_isready', '-h', ENV['DB_HOST'], '-U', 'kpool', check=False).returncode == 0, 'PostgreSQL TCP')
    wait_for(lambda: docker('exec', PREFIX + '-redis', 'redis-cli', 'ping', check=False).returncode == 0, 'Redis')

    name, result = run('migration', 'php', 'artisan', 'migrate', '--force')
    assert result.returncode == 0, result.stdout + result.stderr
    print('PASS: migration / read-only root / exit=0')
    _, result = run('success', 'php', 'artisan', 'schedule:run')
    assert result.returncode == 0, result.stdout + result.stderr
    _, result = run('failure', 'php', 'artisan', 'command-that-does-not-exist')
    assert result.returncode != 0, result
    _, result = run('arguments', 'php', '-r', 'echo json_encode(array_slice($argv, 1));', 'two words', '--literal')
    assert result.stdout == '["two words","--literal"]', result.stdout
    print('PASS: one-shot success/failure / argument preservation')

    api, _ = run('api', detached=True)
    def request(path):
        return docker('exec', api, 'curl', '-sS', '-H', 'Accept: application/json', '-w', '\n%{http_code}', 'http://127.0.0.1:8080' + path, check=False)
    wait_for(lambda: request('/health').stdout.endswith('\n200'), 'Laravel health')
    csrf = request('/api/identity/auth/csrf-token')
    assert csrf.stdout == '\n204', csrf.stdout
    denied = request('/api/identity/auth/me')
    assert denied.stdout.endswith('\n401'), denied.stdout
    assert request('/not-a-script.php').stdout.endswith('\n404')
    assert request('/.env').stdout.endswith('\n403')
    logs = docker('logs', api).stdout + docker('logs', api).stderr
    assert '/health' in logs, logs
    logged = docker('exec', api, 'php', '-r', 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); $app->make("log")->info("container-smoke-log");')
    assert 'container-smoke-log' in logged.stderr
    print('PASS: health=200 / API=204 / auth=401 / PHP and dotfile restrictions / stdout+stderr logs')

    # Inspect the actual packaged filesystem, not the host vendor tree.
    export = subprocess.Popen(['docker', 'export', api], stdout=subprocess.PIPE)
    assert export.stdout is not None
    with tarfile.open(fileobj=export.stdout, mode='r|*') as archive:
        names = {member.name for member in archive}
    assert export.wait() == 0
    assert 'var/www/html/vendor/autoload.php' in names
    assert 'var/www/html/src' in names
    for forbidden in ['.env', '.env.prod', 'auth.json', 'tests', '.git', 'vendor/bin/phpunit', 'vendor/bin/phpstan', 'vendor/bin/php-cs-fixer']:
        assert 'var/www/html/' + forbidden not in names, forbidden
    assert docker('exec', api, 'php', '-r', 'exit(extension_loaded("pcov") ? 1 : 0);').returncode == 0
    assert docker('exec', api, 'mecab', '--version').returncode == 0
    print('PASS: packaged source / no host secrets or development tools / MeCab')
    docker('exec', PREFIX + '-redis', 'redis-cli', 'CLIENT', 'PAUSE', '5000', 'ALL')
    pending = subprocess.Popen(['docker', 'exec', api, 'curl', '-sS', '-H',
                                'Accept: application/json', '-w', '\n%{http_code}',
                                'http://127.0.0.1:8080/api/identity/auth/csrf-token'],
                               stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
    time.sleep(0.5)
    assert pending.poll() is None, 'request must still be in flight'
    duration = stop(api)
    response, error = pending.communicate(timeout=10)
    assert pending.returncode == 0 and response.endswith('\n204'), response + error
    print(f'PASS: API in-flight SIGTERM graceful shutdown ({duration:.2f}s)')

    # Queue a real Redis job and stop the worker while handle() is in progress.
    fixture = '''<?php
class ContainerSmokeJob implements \\Illuminate\\Contracts\\Queue\\ShouldQueue {
    public function handle(): void {
        fwrite(STDERR, "SMOKE_JOB_STARTED\\n");
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) { usleep(100000); }
        fwrite(STDERR, "SMOKE_JOB_FINISHED\\n");
    }
}
'''
    # Load the fixture from a writable temporary area, never overwrite packaged source.
    worker, _ = run('worker', 'sh', '-c', 'while [ ! -f /tmp/smoke-job.php ]; do sleep 0.1; done; exec php -d auto_prepend_file=/tmp/smoke-job.php artisan queue:work redis --queue=container-smoke --sleep=1 --timeout=90 --tries=1', detached=True)
    encoded = ('<?php require "/var/www/html/vendor/autoload.php"; ?>' + fixture).encode()
    subprocess.run(['docker', 'exec', '-i', worker, 'php', '-r',
                    'file_put_contents("/tmp/smoke-job.php", stream_get_contents(STDIN));'],
                   input=encoded, check=True)
    docker('exec', worker, 'php', '-r', 'require "/tmp/smoke-job.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); $app->make("queue")->connection("redis")->push(new ContainerSmokeJob, "", "container-smoke");')
    wait_for(lambda: 'SMOKE_JOB_STARTED' in docker('logs', worker).stderr, 'worker job start')
    duration = stop(worker)
    assert 'SMOKE_JOB_FINISHED' in docker('logs', worker).stderr
    assert duration >= 3, 'must wait for in-flight processing, not just terminate'
    print(f'PASS: Redis real job / in-flight graceful shutdown ({duration:.2f}s)')

    broken, _ = run('broken-api', detached=True)
    wait_for(lambda: docker('exec', broken, 'test', '-f', '/tmp/nginx.pid', check=False).returncode == 0, 'nginx PID')
    docker('exec', broken, 'sh', '-c', 'kill -TERM "$(cat /tmp/nginx.pid)"')
    wait_for(lambda: not json.loads(docker('inspect', broken).stdout)[0]['State']['Running'], 'peer shutdown')
    assert json.loads(docker('inspect', broken).stdout)[0]['State']['ExitCode'] != 0
    print('PASS: unexpected API daemon exit fails task and stops peer')
    print('ALL PRODUCTION CONTAINER TESTS PASSED (SQS live AWS acceptance belongs to deployment)')
finally:
    for name in reversed(CONTAINERS):
        result = docker('logs', name, check=False)
        if name.endswith('-api') or name.endswith('-worker'):
            print(result.stdout[-4000:] + result.stderr[-4000:])
        docker('rm', '-f', '-v', name, check=False)
    docker('network', 'rm', NETWORK, check=False)
