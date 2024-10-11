.PHONY: install
install:
	docker compose up -d
	docker compose exec php-fpm composer install

.PHONY: test
test:
	docker compose run --rm php-fpm ./vendor/bin/phpunit --do-not-cache-result

.PHONY: clean
clean:
	docker compose down -v

.PHONY: phpstan
phpstan:
	docker compose run --rm php-fpm ./vendor/bin/phpstan

.PHONY: php-cs-fixer-fix
php-cs-fixer-fix:
	docker compose run --rm php-fpm ./vendor/bin/php-cs-fixer fix --diff -v
