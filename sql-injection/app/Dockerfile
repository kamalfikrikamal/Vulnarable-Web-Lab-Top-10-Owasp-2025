FROM php:8.2-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

COPY src/ /var/www/html/

EXPOSE 80
