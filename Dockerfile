FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y unzip git curl

# Install PHP extensions (MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer globally
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project
COPY . .

# Install dependencies (FIXED)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts

# Start Symfony with cache warmup
CMD php bin/console cache:warmup --env=prod 2>/dev/null; php -S 0.0.0.0:$PORT -t public