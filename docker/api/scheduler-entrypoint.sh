#!/bin/sh
# Boots the Laravel scheduler and queues an initial sync so data is fresh
# without waiting for the first 15-minute tick.
set -eu

cd /var/www/html

echo "[scheduler] queuing initial rate and branch sync…"
php artisan rates:sync --no-interaction 2>/dev/null || true
php artisan branches:sync --no-interaction 2>/dev/null || true

echo "[scheduler] starting schedule:work (periodic tasks from routes/console.php)…"
exec php artisan schedule:work
