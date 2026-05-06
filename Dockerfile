FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y unzip git curl

# Install PHP extensions (MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer globally
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for caching
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts

# Copy the rest of the project
COPY . .

# Create writable var directory and set permissions
RUN mkdir -p var/cache var/log var/sessions && chmod -R 777 var

# Generate Doctrine proxies at build time (doesn't need DB)
RUN php bin/console cache:warmup --env=prod 2>/dev/null || true

# Start Symfony - warm cache again at runtime (DB available then)
CMD sh -c "php bin/console cache:warmup --env=prod 2>/dev/null; php -S 0.0.0.0:$PORT -t public"
