# ── Build stage: frontend assets ──────────────────────────────────
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ── Production stage ─────────────────────────────────────────────
# Pre-built image with pdo_mysql, mbstring, gd, zip, bcmath, intl, pcntl, etc.
FROM serversideup/php:8.2-cli

USER root

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application code
COPY . .

# Copy built frontend assets from build stage
COPY --from=frontend /app/public/build ./public/build

# Create storage directories
RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Generate a temporary key for build-time artisan commands
RUN cp .env.example .env \
    && echo "APP_KEY=base64:$(head -c 32 /dev/urandom | base64)" >> .env \
    && composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && php artisan view:cache \
    && php artisan event:cache \
    && rm .env

EXPOSE ${PORT:-8080}

CMD php artisan migrate --force && \
    php artisan storage:link 2>/dev/null; \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
