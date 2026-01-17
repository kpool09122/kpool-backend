# PHPUnit and Database Commands
.PHONY: test test-no-db check db-up db-down db-restart db-logs db-shell wait-for-test-db cs-fix phpstan mail-up mail-down process-due-transfers

PHPUNIT=./vendor/bin/phpunit

SLEEP_SECONDS ?= 5

ifeq ($(OS),Windows_NT)
SLEEP_COMMAND := powershell -Command "Start-Sleep -Seconds $(SLEEP_SECONDS)"
else
SLEEP_COMMAND := sleep $(SLEEP_SECONDS)
endif

PHPUNIT_ARGS = $(PHPUNIT) $(if $(filter),--filter=$(filter)) --coverage-html coverage-html

define RUN_TEST_COMMAND_UNIX
set -e; \
trap "$(if $(keepdb),,docker-compose stop testing_db redis && docker-compose rm -v -f testing_db redis)" EXIT; \
$(call DOCKER_RUN,$(PHPUNIT_ARGS))
endef

RUN_TEST_COMMAND_WINDOWS = powershell -ExecutionPolicy Bypass -File scripts/run-tests.ps1 $(if $(filter),-Filter "$(filter)",) $(if $(keepdb),-KeepDb,)

ifeq ($(OS),Windows_NT)
RUN_TEST_COMMAND = $(RUN_TEST_COMMAND_WINDOWS)
else
RUN_TEST_COMMAND = $(RUN_TEST_COMMAND_UNIX)
endif

define DOCKER_RUN
	docker-compose run --rm php bash -c "$(1)"
endef

wait-for-test-db: ## Wait for testing database to be ready
	@echo "Waiting for testing database to be ready..."
	@$(SLEEP_COMMAND)

# Run code style fix, static analysis, and all tests in sequence
check: cs-fix phpstan test ## Run code style fix, static analysis, and all tests

# Run all PHPUnit tests (DB tests + non-DB tests). Usage: `make test [filter=TestClassName] [keepdb=1]`
test:
	docker-compose up -d testing_db redis
	$(MAKE) wait-for-test-db
	@$(RUN_TEST_COMMAND)

# Run PHPUnit tests excluding 'useDb' group. Usage: `make test-no-db [filter=TestClassName]`
test-no-db:
	docker-compose stop testing_db 2>/dev/null || true
	@set -e; \
	trap "" EXIT; \
	$(call DOCKER_RUN, $(PHPUNIT) $(if $(filter),--filter=$(filter)) --exclude-group useDb --coverage-html coverage-html)

# PostgreSQL Database Commands (Testing)
db-up: ## Start PostgreSQL testing service
	docker-compose up -d testing_db

db-down: ## Stop all services
	docker-compose down

db-restart: ## Restart PostgreSQL testing service
	docker-compose restart testing_db

db-logs: ## View PostgreSQL testing logs
	docker-compose logs -f testing_db

db-shell: ## Connect to PostgreSQL testing shell
	docker-compose exec testing_db psql -U kpool -d kpool

db-clean: ## Stop services and remove testing database volumes
	docker-compose down -v --remove-orphans

mail-up: ## Start Mailpit and php (php will pull up testing_db via depends_on)
	docker-compose up -d mail php

mail-down: ## Stop Mailpit and php
	docker-compose stop mail php

# Build and start all services
up: ## Start all services
	docker-compose up -d

# Install dependencies
install: ## Install composer dependencies
	docker-compose run --rm php composer install

# Run PHPStan analysis
phpstan: ## Run PHPStan static analysis
	docker-compose run --rm php composer phpstan

# Fix code style
cs-fix: ## Fix code style using PHP CS Fixer
	docker-compose run --rm php composer cs-fix

# Process due transfers. Usage: `make process-due-transfers [date=2024-02-10] [dry-run=1]`
process-due-transfers: ## Execute due transfer batch processing
	docker-compose up -d testing_db redis
	$(MAKE) wait-for-test-db
	docker-compose run --rm php php artisan settlement:process-due-transfers $(if $(date),--date=$(date)) $(if $(dry-run),--dry-run)

help: ## Show this help message
	@echo 'Usage: make [target] [options]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)
