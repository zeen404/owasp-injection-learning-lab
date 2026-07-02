FROM php:8.2-apache

# libxml is built-in, no extra install needed
RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . .
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
