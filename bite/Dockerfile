# ── Build stage: frontend assets ──────────────────────────────────
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ── Production stage ─────────────────────────────────────────────
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        gd \
        bcmath \
        intl \
        pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

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

# Run post-install scripts and cache
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && php artisan view:cache \
    && php artisan event:cache

# Create storage directories and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE ${PORT:-8080}

CMD php artisan migrate --force && \
    php artisan storage:link 2>/dev/null; \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
