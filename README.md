# KPool Backend

PHPでの開発環境をDockerで構築したプロジェクトです。cs-fixer、phpstan、PHPUnitを使用してコード品質を保ちます。

## 必要な環境

- Docker
- Docker Compose
- Make (オプション)

## セットアップ

1. プロジェクトをクローンまたはダウンロード
2. Dockerコンテナをビルドして起動

```bash
# Makeを使用する場合
make build
make up
make install

# 直接docker-composeを使用する場合
docker-compose build
docker-compose up -d
docker-compose exec php composer install
```

## 使用方法

### 開発用コマンド

```bash
# コンテナのシェルにアクセス
make shell

# コードスタイルの修正
make cs-fix

# コードスタイルのチェック（修正なし）
make cs-check

# 静的解析の実行
make phpstan

# テストの実行
make test

# カバレッジ付きテストの実行
make test-coverage

# 全チェックの一括実行（cs-fix + phpstan + test）
make check

# ヘルプの表示
make help
```

### 直接composer scriptsを使用

```bash
docker-compose exec php composer cs-fix
docker-compose exec php composer cs-check
docker-compose exec php composer phpstan
docker-compose exec php composer test
docker-compose exec php composer test-coverage
```

## プロジェクト構造

```
kpool-backend/
├── src/                    # アプリケーションコード
├── tests/                  # テストコード
│   ├── Unit/              # ユニットテスト
│   └── Feature/           # 機能テスト
├── vendor/                # Composer依存関係
├── coverage/              # テストカバレッジレポート
├── docker-compose.yml     # Docker Compose設定
├── Dockerfile             # Docker設定
├── composer.json          # PHP依存関係
├── phpunit.xml           # PHPUnit設定
├── phpstan.neon          # PHPStan設定
├── .php-cs-fixer.php     # PHP CS Fixer設定
└── Makefile              # 開発用コマンド
```

## 開発ツール

- **PHP CS Fixer**: コードスタイルの自動修正
- **PHPStan**: 静的解析によるバグ検出
- **PHPUnit**: ユニットテストとカバレッジ測定

## クリーンアップ

```bash
# コンテナとボリュームの削除
make clean
```