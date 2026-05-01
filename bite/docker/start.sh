#!/usr/bin/env sh
set -eu

cd /var/www/html

# Ensure writable directories exist
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Production must use one stable key across all instances. Runtime-generated
# production keys break encrypted sessions and any encrypted persisted data.
if [ -z "${APP_KEY:-}" ]; then
    if [ "${APP_ENV:-local}" = "production" ]; then
        echo "FATAL: APP_KEY must be set in production. Set a stable Laravel APP_KEY secret before starting the container." >&2
        echo "Generate one with: php artisan key:generate --show" >&2
        exit 1
    fi

    export APP_KEY="$(php artisan key:generate --show --no-interaction)"
    echo "WARNING: Generated APP_KEY at startup for APP_ENV=${APP_ENV:-local}. Do not use runtime-generated keys in production." >&2
fi

# Run migrations on every deploy (seeding only on first deploy — not here)
php artisan migrate --force --no-interaction

# Cache config and routes for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure PHP-FPM socket directory exists
mkdir -p /run

# Launch Nginx + PHP-FPM via supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
