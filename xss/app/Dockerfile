FROM php:8.2-apache

RUN a2enmod rewrite

COPY src/ /var/www/html/

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

EXPOSE 80
