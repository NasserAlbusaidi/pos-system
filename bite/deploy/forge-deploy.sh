#!/usr/bin/env bash
# Bite-POS — Laravel Forge deploy script (paste into Forge → Site → Deploy Script).
# Forge injects $FORGE_* and runs this from the checked-out repo root.
# One-time steps NOT here (run once after first deploy): APP_KEY, Sourdough seed,
# enable Forge Scheduler. See docs/DEPLOYMENT-FORGE.md.
set -euo pipefail

cd "/home/forge/$FORGE_SITE_NAME/bite"

git pull origin "$FORGE_SITE_BRANCH"

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Frontend assets (Vite). Node is installed by the Forge App Server recipe.
npm ci
npm run build

if [ -f artisan ]; then
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan storage:link
    $FORGE_PHP artisan bite:production-check
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan bite:schema-check
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan view:cache
fi

# Reload PHP-FPM last so the app only serves the new release after migrations
# and cached bootstrap files are ready. The lock avoids concurrent reloads.
( flock -w 10 9 || exit 1
  echo 'Reloading PHP-FPM...'
  sudo -S service "$FORGE_PHP_FPM" reload ) 9>/tmp/fpmlock
