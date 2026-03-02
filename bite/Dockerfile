FROM php:8.2-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip libsqlite3-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction \
    && composer dump-autoload --optimize

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache database \
    && touch database/database.sqlite \
    && chmod -R 775 storage bootstrap/cache database

RUN cp .env.example .env \
    && sed -i 's/APP_ENV=local/APP_ENV=production/' .env \
    && sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env \
    && echo "APP_KEY=base64:$(head -c 32 /dev/urandom | base64)" >> .env \
    && php artisan package:discover --ansi 2>&1 \
    && php artisan view:cache 2>&1 \
    && php artisan event:cache 2>&1 \
    && rm .env

EXPOSE ${PORT:-8080}

CMD cp .env.example .env 2>/dev/null; \
    php artisan migrate --force && \
    php artisan db:seed --class=DatabaseSeeder --force 2>/dev/null; \
    php artisan storage:link 2>/dev/null; \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
