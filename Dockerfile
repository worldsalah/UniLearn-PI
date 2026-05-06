FROM php:8.2-cli

# Install MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

# Install system tools
RUN apt-get update && apt-get install -y unzip git curl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php

WORKDIR /app
COPY . .

# Install Symfony dependencies
RUN php composer.phar install --no-dev --optimize-autoloader

# Clear cache for production
RUN php bin/console cache:clear --env=prod || true

# Start server
CMD php -S 0.0.0.0:$PORT -t public