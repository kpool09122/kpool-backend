# PHPUnit and Database Commands
.PHONY: test test-no-db check db-up db-down db-restart db-logs db-shell wait-for-test-db

PHPUNIT=./vendor/bin/phpunit

define DOCKER_RUN
	docker-compose run --rm php bash -c "$(1)"
endef

wait-for-test-db: ## Wait for testing database to be ready
	@echo "Waiting for testing database to be ready..."
	@sleep 5

test: ## Run all PHPUnit tests (DB tests + non-DB tests). Usage: `make test [filter=TestClassName] [keepdb=1]`
	docker-compose up -d testing_db
	$(MAKE) wait-for-test-db
	@set -e; \
	trap "$(if $(keepdb),,docker-compose stop testing_db && \
	      docker-compose rm -v -f testing_db)" EXIT; \
	$(call DOCKER_RUN, $(PHPUNIT) $(if $(filter),--filter=$(filter)) --coverage-html coverage-html)

test-no-db: ## Run PHPUnit tests excluding 'useDb' group. Usage: `make test-no-db [filter=TestClassName]`
	docker-compose stop testing_db 2>/dev/null || true
	@set -e; \
	trap "" EXIT; \
	$(call DOCKER_RUN, $(PHPUNIT) $(if $(filter),--filter=$(filter)) --exclude-group useDb --coverage-html coverage-html)

check: ## Run code style fix, static analysis, and all tests
	@echo "Running code style fix..."
	$(MAKE) cs-fix
	@echo "Running static analysis..."
	$(MAKE) phpstan
	@echo "Running all tests..."
	$(MAKE) test

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

help: ## Show this help message
	@echo 'Usage: make [target] [options]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)
