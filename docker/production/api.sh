#!/usr/bin/env bash
set -euo pipefail
php-fpm -F &
fpm=$!
nginx -g 'daemon off;' &
nginx_pid=$!
shutdown() {
    trap '' TERM INT
    kill -QUIT "$nginx_pid" "$fpm" 2>/dev/null || true
    wait "$nginx_pid" 2>/dev/null || true
    wait "$fpm" 2>/dev/null || true
}
trap 'shutdown; exit 0' TERM INT
set +e
wait -n "$fpm" "$nginx_pid"
status=$?
set -e
# A daemon exit (even zero) is unexpected; stop its peer and fail the task.
echo "API child exited (status=$status); stopping peer" >&2
shutdown
if [ "$status" -eq 0 ]; then status=1; fi
exit "$status"
