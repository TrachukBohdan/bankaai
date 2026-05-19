# Development image for the Laravel API.
# Uses php:8.4-fpm and listens on port 9000 for FastCGI traffic from nginx.
# PHP 8.4 is required because Laravel 13 ships with Symfony 8 components.
#
# Source code is bind-mounted at runtime via docker-compose; only composer.json/lock
# are copied here so `vendor/` is baked into the image and survives the bind mount
# via a named volume (see docker-compose.yml).

FROM php:8.4-fpm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_NO_INTERACTION=1

RUN set -eux; \
    apt-get update -o Acquire::Retries=5; \
    for i in 1 2 3; do \
        apt-get install -y --no-install-recommends \
            -o Acquire::Retries=5 \
            -o Acquire::http::No-Cache=true \
            git \
            unzip \
            ca-certificates \
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
        intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress \
 && rm -rf "$COMPOSER_HOME/cache"

EXPOSE 9000

# Make storage and bootstrap/cache world-writable so php-fpm workers (running as
# www-data) can write logs/cache regardless of host uid/gid on the bind mount.
# Then regenerate the autoloader against the bind-mounted source, and exec php-fpm.
CMD ["sh", "-c", "chmod -R 0777 storage bootstrap/cache 2>/dev/null || true; composer dump-autoload --optimize --no-scripts; exec php-fpm"]
