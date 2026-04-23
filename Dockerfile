FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    python3 python3-pip \
    && docker-php-ext-install zip pdo pdo_mysql \
    && pip3 install reportlab --break-system-packages

COPY . /var/www/html/
RUN chmod 755 /var/www/html/uploads /var/www/html/data

EXPOSE 80
