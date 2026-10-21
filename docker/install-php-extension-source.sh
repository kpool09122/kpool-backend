#!/bin/sh
set -eu

extension="$1"
version="$2"
repository="$3"
commit="$4"
build_dir="$(mktemp -d)"
trap 'rm -rf "$build_dir"' EXIT

curl -fsSL --retry 3 --connect-timeout 10 --max-time 120 \
    "https://codeload.github.com/$repository/tar.gz/$commit" \
    -o "$build_dir/source.tar.gz"
mkdir "$build_dir/source"
tar -xzf "$build_dir/source.tar.gz" -C "$build_dir/source" --strip-components=1
(
    cd "$build_dir/source"
    phpize
    ./configure "--enable-$extension"
    make -j"$(nproc)"
    make install
)
docker-php-ext-enable "$extension"
php -r 'if (phpversion($argv[1]) !== $argv[2]) {
    fwrite(STDERR, "PHP extension version mismatch: " . $argv[1] . PHP_EOL);
    exit(1);
}' "$extension" "$version"
