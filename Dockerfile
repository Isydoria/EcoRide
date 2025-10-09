# Dockerfile - Version mise à jour
FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Activer la réécriture d'URL
RUN a2enmod rewrite

# Copier le projet
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html