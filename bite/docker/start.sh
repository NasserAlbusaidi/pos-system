#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database
touch database/database.sqlite

# Generate APP_KEY at runtime if not provided via environment.
if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="$(php artisan key:generate --show --no-interaction)"
    echo "Generated APP_KEY at startup."
fi

# Keep schema up to date; data is pre-seeded at build time for demo.
php artisan migrate --force --no-interaction

exec php -d variables_order=EGPCS artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
