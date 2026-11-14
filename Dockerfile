# Multi-stage Dockerfile for Laravel with SQLite
FROM php:8.3-cli-alpine

# Install system dependencies, SQLite, and Node.js
RUN apk add --no-cache \
    sqlite \
    sqlite-dev \
    nodejs \
    npm \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_sqlite pcntl bcmath zip

# Copy Composer binary from official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy environment example to production environment file
RUN cp .env.example .env

# Install Composer dependencies (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install frontend dependencies and build production assets
RUN npm ci || npm install
RUN npm run build

# Prepare SQLite database file and set permissions
RUN mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
RUN touch database/database.sqlite
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Default port
ENV PORT=8080
EXPOSE 8080

# Generate key, run migrations & seed demo data, then start Laravel server
CMD ["sh", "-c", "php artisan key:generate --force && php artisan config:clear && php artisan migrate:fresh --seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
