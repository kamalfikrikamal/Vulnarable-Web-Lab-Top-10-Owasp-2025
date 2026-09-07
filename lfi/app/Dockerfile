FROM php:8.2-apache

# Konfigurasi PHP SENGAJA dilonggarkan untuk keperluan lab (mirip default PHP era lama):
# - display_errors On: warning include()/fopen() ikut tampil (path disclosure, bagian dari
#   pembelajaran).
# - allow_url_include On: dibutuhkan supaya wrapper seperti php://input bisa dipakai lewat
#   include() di Lab 7 (LFI to RCE). php://filter tidak butuh setting ini.
RUN { \
      echo 'display_errors = On'; \
      echo 'allow_url_fopen = On'; \
      echo 'allow_url_include = On'; \
    } > /usr/local/etc/php/conf.d/lab-lfi.ini

COPY src/ /var/www/html/
COPY secret/ /var/www/secret/

RUN mkdir -p /var/www/app_data \
    && chown -R www-data:www-data /var/www/app_data /var/www/secret

EXPOSE 80
