#!/usr/bin/env python3
"""Inspect every final-image layer, including files hidden by later deletion."""
import json
import os
from pathlib import PurePosixPath
import subprocess
import tarfile
import tempfile

image = os.environ.get('PRODUCTION_IMAGE', 'kpool-backend:production')
with tempfile.TemporaryDirectory(prefix='kpool-image-audit-') as directory:
    path = directory + '/image.tar'
    subprocess.run(['docker', 'image', 'save', '-o', path, image], check=True)
    with tarfile.open(path) as archive:
        manifest_file = archive.extractfile('manifest.json')
        assert manifest_file is not None
        manifests = json.load(manifest_file)
        for manifest in manifests:
            config_file = archive.extractfile(manifest['Config'])
            assert config_file is not None
            config = json.load(config_file)
            for entry in config['config'].get('Env', []):
                assert entry.split('=', 1)[0] not in {
                    'APP_KEY', 'DB_PASSWORD', 'AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY',
                    'STRIPE_SECRET_KEY', 'GOOGLE_APPLICATION_CREDENTIALS',
                }, entry.split('=', 1)[0]
            for layer in manifest['Layers']:
                with tarfile.open(fileobj=archive.extractfile(layer), mode='r|*') as files:
                    for member in files:
                        name = member.name.removeprefix('./')
                        path = PurePosixPath(name)
                        if name.startswith('var/www/html/'):
                            local = name.removeprefix('var/www/html/')
                            assert not local.startswith(('.env', '.git/', 'tests/', 'coverage')), local
                            assert local not in {'auth.json', 'vendor/bin/phpunit', 'vendor/bin/phpstan', 'vendor/bin/php-cs-fixer'}, local
                            assert not (local.startswith('bootstrap/cache/') and local.endswith('.php')), local
                        assert path.name != 'auth.json', name
print('PASS: all final-image layers exclude application env/credentials/dev tools/generated secret caches')
