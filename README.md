# kpool-backend

PHP Project with PHPUnit

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
curl -i -X POST http://localhost:8080/api/identity/auth/send-auth-code \
  -H 'Content-Type: application/json' \
  --data '{"email":"demo@example.com"}'
```

5. Confirm another container on the same Docker network can reach the backend:
```bash
docker run --rm --network kpool-network curlimages/curl:8.13.0 \
  -i http://nginx/api/identity/auth/send-auth-code \
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

### Default Mail Queue

Passkey recovery and SSO linking emails use the default connection and queue.
`APP_ENV=local` and `APP_ENV=testing` default to Redis; all other environments
default to Google Cloud Tasks. `QUEUE_CONNECTION` can override the connection.

For local development, start the worker with:

```bash
task queue-work
```

Google Cloud infrastructure can be provisioned later. Before enabling queued
emails in a hosted environment, create a Cloud Tasks queue and configure:

```bash
APP_ENV=production
CLOUD_TASKS_PROJECT=your-project-id
CLOUD_TASKS_LOCATION=asia-northeast1
CLOUD_TASKS_QUEUE=default
CLOUD_TASKS_HANDLER=https://your-task-handler.example.com
CLOUD_TASKS_SERVICE_EMAIL=tasks@your-project-id.iam.gserviceaccount.com
CLOUD_TASKS_HANDLER_ENABLED=true
```

`CLOUD_TASKS_HANDLER` is the HTTPS base URL of the Laravel app that processes
mail. Tasks are delivered to `/internal/queue/default`. The app verifies
the Google ID token's signature, expiry, issuer, audience and service account
email before processing the job. The handler is disabled by default and remains
disabled in local and testing environments. Cloud Tasks uses HTTP delivery, so
this connection does not use `queue:work` in hosted environments.

Install dependencies with `composer install` on PHP 8.5 or later. Configure Google
Application Default Credentials on the app runtime, enable the Cloud Tasks API,
and grant the runtime service account permission to enqueue and inspect tasks
and to act as `CLOUD_TASKS_SERVICE_EMAIL`. If the HTTP service enforces platform
IAM authentication, configure its invoker permissions and accepted audience for
the task URL as well. Rebuild Laravel's config and route caches after setting
these values. The producer and handler must use compatible application code and
the same `APP_KEY`.

Until these resources and settings are ready, hosted environments cannot deliver
recovery mail. Queue submission errors are reported while preserving the API's
existing response behavior.

References: [Cloud Tasks HTTP targets](https://docs.cloud.google.com/tasks/docs/creating-http-target-tasks),
[Laravel Cloud Tasks driver](https://github.com/stackkit/laravel-google-cloud-tasks-queue).

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
- PostgreSQL 16 Alpine image
- UUID and crypto extensions enabled
- Separate test database configuration
- Connection via PDO with PostgreSQL driver

### Manual Database Setup (if not using Docker)

If you prefer to set up PostgreSQL manually:

1. Install PostgreSQL 16
2. Create database: `CREATE DATABASE kpool;`
3. Create user: `CREATE USER kpool WITH PASSWORD 'secret';`
4. Grant privileges: `GRANT ALL PRIVILEGES ON DATABASE kpool TO kpool;`
5. Enable extensions: `CREATE EXTENSION IF NOT EXISTS "uuid-ossp";`

## Automated Dependency Updates

Dependencies are automatically reviewed by Renovate using the rules in `renovate.json`. It groups Composer updates, schedules them for early Tokyo mornings, and surfaces all pending changes on the Renovate dashboard so pull requests stay easy to review. Enable Renovate for this repository on GitHub (connecting it once is enough) to keep tooling current without manual version tracking.

## OpenAPI Generation

TypeSpec definitions live under `typespec/` and are split by Laravel route file so later endpoint work can proceed in parallel.

- `typespec/services/identity-api.tsp` -> `routes/identity_api.php`
- `typespec/services/account-api.tsp` -> `routes/account_api.php`
- `typespec/services/monetization-api.tsp` -> `routes/monetization_api.php`
- `typespec/services/site-management-public-api.tsp` -> `routes/site_management_api.php`
- `typespec/services/wiki-private-api.tsp` -> `routes/wiki_api.php`
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
