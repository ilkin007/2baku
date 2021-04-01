#!/usr/bin/env sh

mkfile_dir := $(dir $(realpath $(firstword $(MAKEFILE_LIST))))

composer install:
	cd parser/Selenium && composer install
start:
	docker-compose up -d && make composer install
stop:
	docker-compose down
restart:
	docker-compose down && docker-compose up -d
test:
	docker-compose exec php "/var/www/html/bin/behat"
fixtures:
	docker-compose exec php php /var/www/html/Behat/ApplyFixtures.php
composer update:
	cd parser/Selenium && composer update
