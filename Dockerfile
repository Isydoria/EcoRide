# On part d'une image PHP avec Apache déjà configuré
FROM php:8.2-apache

# On installe les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql mysqli

# On active la réécriture d'URL (pour les jolies URLs)
RUN a2enmod rewrite

# On copie notre projet dans le container
COPY . /var/www/html/

# On donne les bonnes permissions
RUN chown -R www-data:www-data /var/www/html