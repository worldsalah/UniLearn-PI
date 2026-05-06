FROM php:8.2-cli

RUN apt-get update && apt-get install -y unzip git curl

RUN docker-php-ext-install pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts

COPY . .

RUN mkdir -p var/cache var/log var/sessions && chmod -R 777 var

RUN php bin/console cache:warmup --env=prod 2>/dev/null || true

EXPOSE 8080

CMD ["sh", "-c", "php bin/console cache:warmup --env=prod 2>/dev/null; php -S 0.0.0.0:${PORT:-8080} -t public"]
