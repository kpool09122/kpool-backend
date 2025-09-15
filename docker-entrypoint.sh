#!/bin/bash

# Composerの依存関係をインストール
if [ -f "composer.json" ]; then
    composer install
fi

# コマンドが指定されていない場合はbashを実行
if [ $# -eq 0 ]; then
    exec bash
else
    exec "$@"
fi 