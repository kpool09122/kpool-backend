#!/bin/sh

set -eu

# vendor 未生成時だけ依存を入れて、CLI運用はそのまま維持する
if [ -f "composer.json" ] && [ ! -f "vendor/autoload.php" ] && [ "${1:-}" != "composer" ]; then
    composer install --no-interaction --prefer-dist
fi

# コマンドが指定されていない場合はphp-fpmを実行
if [ $# -eq 0 ]; then
    exec php-fpm -F
else
    exec "$@"
fi
