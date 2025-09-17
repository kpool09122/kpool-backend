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