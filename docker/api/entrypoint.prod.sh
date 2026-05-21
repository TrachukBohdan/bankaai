#!/bin/sh
#
# Production entrypoint for the Laravel API container.
#
#   1. Make sure storage and bootstrap/cache exist and are writable (defensive
#      in case the deploy host attaches an empty volume on top of these paths).
#   2. Wait for MySQL to actually accept connections, then run migrations.
#   3. Cache config, routes, views and events for production speed.
#   4. Hand off to php-fpm (the image's CMD).
#
# The script runs as root so it can chown the storage tree; php-fpm itself
# starts its master as root and forks workers as www-data.

set -eu

cd /var/www/html

# --- 1. Writable runtime directories --------------------------------------
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 0775 storage bootstrap/cache

# --- 2. Wait for DB then migrate ------------------------------------------
echo "[entrypoint] waiting for database '${DB_HOST:-db}:${DB_PORT:-3306}'..."
ATTEMPTS=0
MAX_ATTEMPTS=60
until php -r "
    try {
        new PDO(
            'mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        exit(0);
    } catch (Throwable \$e) {
        exit(1);
    }
"; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -ge "$MAX_ATTEMPTS" ]; then
        echo "[entrypoint] database did not become reachable after ${MAX_ATTEMPTS} attempts; aborting." >&2
        exit 1
    fi
    sleep 1
done
echo "[entrypoint] database reachable after ${ATTEMPTS}s."

# --- 3. Cache for production ---------------------------------------------
echo "[entrypoint] caching config, routes, views, events..."
php artisan optimize

# --- 4. Hand off ----------------------------------------------------------
echo "[entrypoint] starting php-fpm..."
exec "$@"
