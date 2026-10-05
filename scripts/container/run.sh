#!/usr/bin/env bash
set -euo pipefail

mode="${1:-api}"
set -- "${PRODUCTION_IMAGE:-kpool-backend:production}" "$@"
if [[ "$mode" == api ]]; then
    set -- -p "127.0.0.1:${API_PORT:-18080}:8080" "$@"
fi

exec docker run --rm --read-only --cap-drop=ALL --security-opt=no-new-privileges \
    --stop-timeout=120 --env-file "${RUNTIME_ENV_FILE:?RUNTIME_ENV_FILE is required}" \
    --tmpfs /tmp:uid=1000,gid=1000,mode=1770 \
    --tmpfs /var/www/html/storage:uid=1000,gid=1000,mode=770 \
    --tmpfs /var/www/html/bootstrap/cache:uid=1000,gid=1000,mode=770 \
    "$@"
