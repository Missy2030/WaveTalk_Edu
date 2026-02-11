FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copier tout le projet dans /var/www/
COPY . /var/www/

# Le dossier public devient la racine web
RUN sed -i 's|/var/www/html|/var/www/public|g' /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

EXPOSE 80
