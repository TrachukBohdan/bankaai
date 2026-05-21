# =========================
# BankaAI Production Makefile
# =========================

COMPOSE=docker compose --env-file .env.prod -f docker-compose.prod.yml

start:
	cp .env.prod.example .env.prod
	cp ./api/.env.example ./api/.env
	$(COMPOSE) up
	php artisan migrate --force --no-interaction
	php artisan db:seed --force --no-interaction

build:
	$(COMPOSE) build

push:
	$(COMPOSE) push