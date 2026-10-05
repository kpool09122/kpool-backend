#!/usr/bin/env bash
set -euo pipefail
image=${PRODUCTION_IMAGE:-kpool-backend:production}
docker image inspect "$image" >/dev/null
docker run --rm --read-only --tmpfs /tmp:uid=1000,gid=1000,mode=1770 --tmpfs /var/www/html/storage:uid=1000,gid=1000 --tmpfs /var/www/html/bootstrap/cache:uid=1000,gid=1000 "$image" php -r 'if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5 || !extension_loaded("pdo_pgsql") || !extension_loaded("redis") || !extension_loaded("pcntl") || !gd_info()["WebP Support"] || extension_loaded("pcov") || file_exists("vendor/bin/phpunit") || file_exists(".env")) { exit(1); } echo "production runtime OK\n";'
