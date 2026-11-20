#!/bin/sh
set -eu

build_dir="$(mktemp -d)"
export GNUPGHOME="$build_dir/gnupg"
mkdir -m 700 "$GNUPGHOME"
trap 'rm -rf "$build_dir"' EXIT

curl -fsSL --retry 3 --connect-timeout 10 --max-time 120 \
    https://nginx.org/keys/nginx_signing.key -o "$build_dir/nginx_signing.key"
gpg --batch --import "$build_dir/nginx_signing.key"
# Export only the official signing keys used by nginx/docker-nginx.
gpg --batch --export \
    573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62 \
    8540A6F18833A80E9C1653A42FD21310B49F6B46 \
    9E9BE90EACBCDE69FE9B204CBCDCD8A38D88A2B3 \
    > /usr/share/keyrings/nginx-archive-keyring.gpg
test -s /usr/share/keyrings/nginx-archive-keyring.gpg
chmod 644 /usr/share/keyrings/nginx-archive-keyring.gpg

printf '%s\n' \
    'deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] https://nginx.org/packages/mainline/debian/ trixie nginx' \
    > /etc/apt/sources.list.d/nginx.list
printf '%s\n' 'Package: nginx' 'Pin: origin nginx.org' 'Pin-Priority: 900' \
    > /etc/apt/preferences.d/nginx
apt-get update
# A version glob selects the newest patch without moving to another minor series.
apt-get install -y --no-install-recommends 'nginx=1.31.*'
# The official package opens this log before reading the non-root runtime config.
ln -sf /dev/stdout /var/log/nginx/access.log
ln -sf /dev/stderr /var/log/nginx/error.log
check-nginx-version.sh
