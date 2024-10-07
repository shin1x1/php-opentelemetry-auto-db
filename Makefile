.PHONY: install
install:
	docker compose up -d
	docker compose exec php-fpm composer install

.PHONY: test
test:
	docker compose exec php-fpm ./vendor/bin/phpunit

.PHONY: clean
clean:
	docker compose down -v
