# =========================
# BankaAI Production Makefile
# =========================

PROD_COMPOSE=docker compose --env-file .env -f docker-compose.prod.yml
DEV_COMPOSE=docker compose

prod_start:
	cp .env.prod.example .env
	cp ./api/.env.example ./api/.env
	$(PROD_COMPOSE) up -d
	$(PROD_COMPOSE) exec api php artisan migrate --force --no-interaction
	$(PROD_COMPOSE) exec api php artisan db:seed --force --no-interaction

dev_start:
	cp .env.example .env
	cp ./api/.env.example ./api/.env
	$(DEV_COMPOSE) up
	$(DEV_COMPOSE) exec api php artisan migrate --force --no-interaction
	$(DEV_COMPOSE) exec api php artisan db:seed --force --no-interaction

prod_build:
	$(PROD_COMPOSE) build

dev_build:
	$(DEV_COMPOSE) build

prod_push:
	$(PROD_COMPOSE) push

prod_pull:
	$(PROD_COMPOSE) pull

prod_reset:
	$(PROD_COMPOSE) down -v

dev_reset:
	$(DEV_COMPOSE)  down -v

prod_login:
	echo $(GITHUB_TOKEN) | docker login ghcr.io -u TrachukBohdan --password-stdin

prod_logout:
	docker logout ghcr.io