FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
