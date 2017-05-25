FROM php:7.0-apache
COPY . /var/www/html/
# RUN docker-php-ext-enable rewrite
RUN a2enmod rewrite
