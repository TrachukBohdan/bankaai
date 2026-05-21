# =========================
# BankaAI Production Makefile
# =========================

PROD_COMPOSE=docker compose --env-file .env -f docker-compose.prod.yml
DEV_COMPOSE=docker compose

prod_start:
	cp .env.prod.example .env
	cp ./api/.env.example ./api/.env
	$(PROD_COMPOSE) up
	php artisan migrate --force --no-interaction
	php artisan db:seed --force --no-interaction

dev_start:
	cp .env.example .env
	cp ./api/.env.example ./api/.env
	$(PROD_COMPOSE) up
	php artisan migrate --force --no-interaction
	php artisan db:seed --force --no-interaction


prod_build:
	$(PROD_COMPOSE) build

dev_build:
	$(DEV_COMPOSE) build

prod_push:
	$(PROD_COMPOSE) push


prod_reset:
	$(PROD_COMPOSE) down -v

dev_reset:
	$(DEV_COMPOSE)  down -v