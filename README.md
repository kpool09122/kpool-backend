# kpool-backend

PHP Project with PHPUnit

## S3画像保存・非公開書類

公開画像のS3保存とCloudFront配信、本人確認書類の別バケット保存は [S3保存・配信設定](docs/s3-storage.md) を参照してください。CloudFormation Outputsと環境変数、ローカル既定、キー接頭辞、#157向け実AWS確認・キャッシュ失効手順を記載しています。

## AWS 本番基盤

構成・管理責任・設定の正本は [本番インフラの入口](infra/cloudformation/README.md)。通常配備、基盤変更、Secret更新、容量変更、障害復旧はそこからたどれます。非秘密の環境入力を1つにまとめ、実Outputsと配備stateの取込みは `task infra:operate` が行います。静的検証は `task cfn:install` 後に `task infra:validate`（AWS認証不要）。AWS変更操作は管理者がplan/review/executeで明示実行します。

## 本番用 ARM64 コンテナ

`task container:build` でソース・本番vendor内包の Linux ARM64 イメージを生成します。
`task container:smoke` と `task container:verify` で拡張、API、worker実ジョブ、migration、単発終了コード、read-only root、処理中の停止を検証できます。
同じイメージのAPIは8080・`/health`、worker/migration/単発はコマンド指定で実行します。
必要な環境変数、UID 1000の書き込みvolume、停止120秒、ECS #156への引き渡しは
[本番コンテナ実行契約](doc/infrastructure/container-runtime.md) を参照してください。
既存のローカル開発はComposeのdevelopmentステージを引き続き利用します。

## PostgreSQL Database Setup

### Environment Variables

Create a `.env` file in the project root with the following configuration:

```bash
# Database Configuration (Testing)
DB_CONNECTION=pgsql
DB_HOST=testing_db
DB_PORT=5432
DB_DATABASE=kpool
DB_USERNAME=kpool
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Application Environment
APP_ENV=local
APP_DEBUG=true
APP_KEY=
APP_URL=http://127.0.0.1:8080
FRONTEND_URL=http://localhost:3000

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=debug
LOG_DAILY_DAYS=10
```

### Running with Docker

1. Start the Laravel API stack (`nginx + php-fpm + postgres + redis + mailpit`):
```bash
task up
```

2. Install PHP dependencies if `vendor/` is not present yet:
```bash
task install
```

3. Confirm the API server is reachable from the host:
```bash
curl -i http://localhost:8080/
```

`/` has no application route by default, so a `404 Not Found` response still confirms that `nginx -> php-fpm -> Laravel` is working.

4. Confirm an `/api/...` endpoint is reachable from the host:
```bash
curl -i -X POST http://localhost:8080/api/v1/identity/auth/send-auth-code \
  -H 'Content-Type: application/json' \
  --data '{"email":"demo@example.com"}'
```

5. Confirm another container on the same Docker network can reach the backend:
```bash
docker run --rm --network kpool-network curlimages/curl:8.13.0 \
  -i http://nginx/api/v1/identity/auth/send-auth-code \
  -H 'Content-Type: application/json' \
  --data '{"email":"demo@example.com"}'
```

6. Run tests:
```bash
# Run all tests (including database tests)
task test

# Run tests without database
task test-no-db

```

### API Operations

Use these tasks during development:

```bash
task up
task down
task restart
task logs
task shell
```

The backend is published on `http://localhost:8080`, and containers joined to the shared Docker network can reach it via `http://nginx`. The network name is fixed to `kpool-network` so a separate Next.js compose stack can join it as an external network.

If the frontend runs on another origin, set `FRONTEND_URL` in `.env` so CORS permits requests from that origin.

### Queue / EventBridge 運用（Issue #666）

`local` / `testing` は Redis、それ以外（production / staging / preview）は
Laravel 標準 SQS driver を既定とします。`QUEUE_CONNECTION` の明示指定は優先されます。
メール、Wiki、webhook、settlement の producer は本番では #673 の単一 work queue を共有します。
ローカルは `task queue-work` で `webhook,settlement,default` を消費します。

SQS QueueUrl、タスクロール権限、worker timeout / 停止契約、EventBridge CLI、
失敗ジョブと DLQ の区別、Cloud Tasks の drain / 切り替え / 復旧、#665 / #157 での
実環境検証は [SQS・EventBridge 運用手順](doc/infrastructure/queue-operations.md)を参照してください。
依存は `task install`、検証は `task check` を使用します。
Google Cloud Translation と Google OAuth の依存・資格情報は移行後も維持します。

### Test Organization

Tests are organized using PHPUnit groups:
- **`@group useDb`**: Tests that require database connection
- Tests without this annotation run without database

Example:
```php
/**
 * @group UseDb
 */
class DatabaseConnectionTest extends TestCase
{
    // Database tests here
}
```

### Database Connection

The project is configured to connect to PostgreSQL with the following features:
- PostgreSQL 18 Alpine image
- UUID and crypto extensions enabled
- Separate test database configuration
- Connection via PDO with PostgreSQL driver

### Manual Database Setup (if not using Docker)

If you prefer to set up PostgreSQL manually:

1. Install PostgreSQL 18
2. Create database: `CREATE DATABASE kpool;`
3. Create user: `CREATE USER kpool WITH PASSWORD 'secret';`
4. Grant privileges: `GRANT ALL PRIVILEGES ON DATABASE kpool TO kpool;`
5. Enable extensions: `CREATE EXTENSION IF NOT EXISTS "uuid-ossp";`

## AWS 基盤（Issue #668）

CloudFormation の構成・Parameters/Outputs・change set・保持方針は
[ネットワーク・データ基盤の運用手順](infra/cloudformation/README.md)を参照してください。
依存ツールの準備後、`task infra:validate` で AWS 認証情報なしに検証できます。

## Automated Dependency Updates

Dependencies are automatically reviewed by Renovate using the rules in `renovate.json`. It groups Composer updates, schedules them for early Tokyo mornings, and surfaces all pending changes on the Renovate dashboard so pull requests stay easy to review. Enable Renovate for this repository on GitHub (connecting it once is enough) to keep tooling current without manual version tracking.

## OpenAPI Generation

TypeSpec definitions live under `typespec/` and are split by Laravel route file so later endpoint work can proceed in parallel.

- `typespec/services/identity-api.tsp` -> `routes/v1/identity_api.php`
- `typespec/services/account-api.tsp` -> `routes/v1/account_api.php`
- `typespec/services/monetization-api.tsp` -> `routes/v1/monetization_api.php`
- `typespec/services/site-management-api.tsp` -> `routes/v1/site_management_api.php`
- `typespec/services/wiki-api.tsp` -> `routes/v1/wiki_api.php`
- `typespec/services/webhook.tsp` -> `routes/webhook.php`
- `typespec/common/` holds shared schema fragments such as Problem Details

Install TypeSpec dependencies and generate OpenAPI artifacts with:

```bash
task openapi
```

After the initial install, regenerate the specs with:

```bash
task openapi-generate
```

If you want to run the compiler directly, use `pnpm run typespec:compile`.

Generated OpenAPI files are written to `doc/openapi/` and should be updated together with TypeSpec changes.

## License
All rights reserved. Unauthorized forks, copying, distribution, modification, or commercial use of this project are strictly prohibited without explicit written permission from the project owner.

## ライセンス
全著作権所有。プロジェクトオーナーによる明示的な書面での許可がない限り、このプロジェクトのフォーク、複製、配布、改変、商業利用はいかなる場合も固く禁じられています。

## 라이선스
모든 권리 보유. 프로젝트 소유자의 명시적인 서면 허락 없이 본 프로젝트의 포크, 복제, 배포, 수정 또는 상업적 사용을 일절 금합니다.
