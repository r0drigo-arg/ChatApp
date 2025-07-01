
FROM composer:latest AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --optimize-autoloader


FROM php:8.4-apache AS app

RUN apt-get update && apt-get install -y sqlite3

RUN a2enmod rewrite
COPY ./configuration/server/server.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY --from=vendor /app/vendor/ ./vendor/

COPY ./public ./public
COPY ./src ./src

WORKDIR /database

COPY ./configuration/database/init.sql /database/init.sql

RUN touch app.db && sqlite3 app.db < init.sql \
    && chown -R www-data:www-data /database

ENV DATABASE_PATH="/database/app.db"

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
