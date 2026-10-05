#!/bin/sh
set -eu
umask 027
mkdir -p /tmp/nginx storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
# Configuration contains Sentry callbacks: evaluate it at runtime rather than
# serializing closures or deployment secrets. Each task owns its cache volume.
rm -f bootstrap/cache/*.php
if [ "${1:-api}" = api ]; then
    exec /usr/local/bin/api.sh
fi
exec "$@"
