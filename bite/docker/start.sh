#!/usr/bin/env sh
set -eu

cd /var/www/html

# Ensure writable directories exist
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Generate APP_KEY at runtime if not provided via environment (per D-10)
if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="$(php artisan key:generate --show --no-interaction)"
    echo "Generated APP_KEY at startup."
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
