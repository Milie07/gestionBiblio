FROM php:8.3-apache

# Extensions PHP requises
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    libssl-dev \
    pkg-config \
    && docker-php-ext-install pdo pdo_mysql zip

# Extension MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Configuration PHP
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

EXPOSE 80
