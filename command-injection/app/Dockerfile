FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends iputils-ping curl \
    && rm -rf /var/lib/apt/lists/*

COPY src/ /var/www/html/

EXPOSE 80
