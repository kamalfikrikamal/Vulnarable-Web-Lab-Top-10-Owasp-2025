FROM php:8.2-apache

COPY src/ /var/www/html/
COPY apache/backup_listing.conf /etc/apache2/conf-available/backup_listing.conf
RUN a2enconf backup_listing

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

EXPOSE 80
