FROM php:8.2-apache

RUN a2enmod rewrite

COPY src/ /var/www/html/
COPY apache/uploads_safe.conf /etc/apache2/conf-available/uploads_safe.conf
COPY apache/php-legacy-extensions.conf /etc/apache2/conf-available/php-legacy-extensions.conf
RUN a2enconf uploads_safe php-legacy-extensions

RUN mkdir -p /var/www/html/uploads /var/www/html/uploads_safe \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/uploads_safe

EXPOSE 80
