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

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Running with Docker

1. Start the services:
```bash
docker-compose up -d
```

2. Install PHP dependencies:
```bash
docker-compose exec php composer install
```

3. Run tests:
```bash
# Run all tests (including database tests)
make test

# Run tests without database
make test-no-db

# Run only database tests
make test-db
```

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

## License
All rights reserved. Unauthorized forks, copying, distribution, modification, or commercial use of this project are strictly prohibited without explicit written permission from the project owner.

## ライセンス
全著作権所有。プロジェクトオーナーによる明示的な書面での許可がない限り、このプロジェクトのフォーク、複製、配布、改変、商業利用はいかなる場合も固く禁じられています。

## 라이선스
모든 권리 보유. 프로젝트 소유자의 명시적인 서면 허락 없이 본 프로젝트의 포크, 복제, 배포, 수정 또는 상업적 사용을 일절 금합니다.
