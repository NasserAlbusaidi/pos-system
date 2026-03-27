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


FROM php:8.4-fpm-bookworm AS app

WORKDIR /var/www/html

# Install system deps: nginx, supervisord, MySQL client libs, GD libs (for intervention/image)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql bcmath gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy process manager and server configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy app source + frontend build artifacts
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Production environment defaults (NO secrets — all secrets injected at runtime via Cloud Run env vars)
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log \
    DB_CONNECTION=mysql \
    SENTRY_ENVIRONMENT=production \
    SENTRY_TRACES_SAMPLE_RATE=0.1

# Build-time key for artisan commands only (NOT a real secret — thrown away after build)
ARG BUILD_APP_KEY="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA="

RUN APP_KEY="${BUILD_APP_KEY}" SENTRY_LARAVEL_DSN="https://dummy@sentry.io/0" \
    composer install --prefer-dist --no-interaction --optimize-autoloader --no-scripts --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && APP_KEY="${BUILD_APP_KEY}" SENTRY_LARAVEL_DSN="https://dummy@sentry.io/0" php artisan package:discover --ansi \
    && APP_KEY="${BUILD_APP_KEY}" SENTRY_LARAVEL_DSN="https://dummy@sentry.io/0" php artisan optimize:clear \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x artisan

COPY docker/start.sh /usr/local/bin/start-laravel
RUN chmod +x /usr/local/bin/start-laravel

EXPOSE 8080

CMD ["start-laravel"]
