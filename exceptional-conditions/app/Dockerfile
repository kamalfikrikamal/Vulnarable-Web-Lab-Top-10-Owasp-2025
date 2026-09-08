FROM php:8.2-apache

COPY src/ /var/www/html/

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

EXPOSE 80
