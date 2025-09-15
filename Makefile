make check: cs-fix phpstan phpunit

cs-fix:
	docker-compose run --rm php composer cs-fix

phpstan:
	docker-compose run --rm php composer phpstan

phpunit:
	docker-compose run --rm php composer test
