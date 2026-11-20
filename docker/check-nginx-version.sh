#!/bin/sh
set -eu

version="$(nginx -v 2>&1)"
version="${version#nginx version: nginx/}"
case "$version" in
    1.31.*) patch="${version#1.31.}" ;;
    *) echo "Nginx 1.31.9 or a newer 1.31 patch is required: $version" >&2; exit 1 ;;
esac
case "$patch" in
    ''|*[!0-9]*) echo "Invalid Nginx patch version: $version" >&2; exit 1 ;;
esac
if [ "$patch" -lt 9 ]; then
    echo "Nginx 1.31.9 or a newer 1.31 patch is required: $version" >&2
    exit 1
fi
printf 'Nginx version OK: %s\n' "$version"
