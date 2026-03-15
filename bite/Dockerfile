FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY lang ./lang
COPY postcss.config.js tailwind.config.js vite.config.js ./
COPY storage/framework/views ./storage/framework/views

# Avoid stale compiled Blade files from affecting Tailwind content scanning.
RUN rm -f storage/framework/views/*.php
RUN npm run build


FROM php:8.4-cli-bookworm AS app

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=frontend /app/public/build ./public/build

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite

# Throwaway key for build-time artisan commands only (migrate, seed, cache).
# The real key is generated at container startup in start.sh.
ARG BUILD_APP_KEY="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA="

RUN APP_KEY="${BUILD_APP_KEY}" \
    composer install --prefer-dist --no-interaction --optimize-autoloader --no-scripts --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && rm -f database/database.sqlite \
    && touch database/database.sqlite \
    && APP_KEY="${BUILD_APP_KEY}" php artisan migrate --force --seed --no-interaction \
    && APP_KEY="${BUILD_APP_KEY}" php artisan optimize:clear \
    && chmod +x artisan

COPY docker/start.sh /usr/local/bin/start-laravel
RUN chmod +x /usr/local/bin/start-laravel

EXPOSE 8080

CMD ["start-laravel"]
