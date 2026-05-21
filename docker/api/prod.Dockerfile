# syntax=docker/dockerfile:1.7
#
# Production image for the Laravel API.
#
# Multi-stage:
#   1. `vendor`  — pulls Composer dependencies WITHOUT dev requires and
#                  WITHOUT scripts. Lives in the throwaway composer:2 image.
#   2. `runtime` — slim php:8.4-fpm with only the production extensions,
#                  the application source baked in, opcache enabled and
#                  timestamps invalidated (immutable image).
#
# Build context is the repository ROOT (not ./api) so the SPA Dockerfile can
# use the same convention; see docker-compose.prod.yml.

# -----------------------------------------------------------------------------
# Stage 1: Composer dependencies (no dev, no scripts, no autoloader yet)
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY api/composer.json api/composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress \
        --no-interaction \
 && composer clear-cache

# -----------------------------------------------------------------------------
# Stage 2: PHP-FPM runtime
# -----------------------------------------------------------------------------
FROM php:8.4-fpm AS runtime

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

RUN set -eux; \
    apt-get update -o Acquire::Retries=5; \
    for i in 1 2 3; do \
        apt-get install -y --no-install-recommends \
            -o Acquire::Retries=5 \
            -o Acquire::http::No-Cache=true \
            libzip-dev \
            libicu-dev \
            libonig-dev \
        && break || { echo "apt attempt $i failed, retrying..."; rm -rf /var/lib/apt/lists/*; apt-get update -o Acquire::Retries=5; sleep 3; }; \
    done; \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        zip \
        intl \
        opcache

# Switch to the production php.ini and layer our overrides on top.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/api/php-prod.ini "$PHP_INI_DIR/conf.d/zz-prod.ini"

# Bundle composer so the entrypoint can dump-autoload if needed; cost is ~3 MB.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 1) Application source (filtered by .dockerignore)
COPY api/ /var/www/html/

# 2) Optimised vendor from stage 1
COPY --from=vendor /app/vendor /var/www/html/vendor

# 3) Generate a class-map authoritative autoloader for prod.
#    --classmap-authoritative skips runtime PSR-4 lookups entirely.
RUN composer dump-autoload \
        --classmap-authoritative \
        --no-scripts \
        --no-dev \
 && rm -rf tests \
 && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 0775 storage bootstrap/cache

COPY docker/api/entrypoint.prod.sh /usr/local/bin/entrypoint.sh
COPY docker/api/scheduler-entrypoint.sh /usr/local/bin/scheduler-entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/scheduler-entrypoint.sh

# php-fpm listens on 9000 (default); only the web service needs to reach it.
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "--nodaemonize"]
