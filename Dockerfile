# ==========================================
# 🐳 DOCKERFILE - EcoRide Platform
# ==========================================
# Image PHP 8.2 + Apache pour environnement de production
# Compatible avec Railway et déploiements cloud

FROM php:8.2-apache

# ==========================================
# 📦 MÉTADONNÉES
# ==========================================
LABEL maintainer="EcoRide Team"
LABEL version="1.0.0"
LABEL description="EcoRide ecological carpooling platform"

# ==========================================
# 🔧 INSTALLATION DES DÉPENDANCES SYSTÈME
# ==========================================
RUN apt-get update && apt-get install -y \
    # Outils de base
    git \
    curl \
    zip \
    unzip \
    # Bibliothèques pour extensions PHP
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    # Nettoyage du cache apt
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ==========================================
# 🐘 INSTALLATION DES EXTENSIONS PHP
# ==========================================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
        opcache

# ==========================================
# ⚙️ CONFIGURATION PHP (php.ini)
# ==========================================
RUN { \
        echo 'upload_max_filesize = 10M'; \
        echo 'post_max_size = 10M'; \
        echo 'max_execution_time = 300'; \
        echo 'memory_limit = 256M'; \
        echo 'date.timezone = Europe/Paris'; \
        echo 'display_errors = On'; \
        echo 'error_reporting = E_ALL'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# ==========================================
# 🌐 CONFIGURATION APACHE
# ==========================================
# Activer mod_rewrite pour URL rewriting
RUN a2enmod rewrite

# Copier la configuration Apache personnalisée
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# ==========================================
# 📂 COPIE DES FICHIERS DE L'APPLICATION
# ==========================================
WORKDIR /var/www/html

# Copier tout le code source
COPY . /var/www/html/

# ==========================================
# 🔐 PERMISSIONS
# ==========================================
# Donner les bonnes permissions à Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Créer le dossier pour MongoDB Fake s'il n'existe pas
RUN mkdir -p /var/www/html/mongodb_data \
    && chown -R www-data:www-data /var/www/html/mongodb_data \
    && chmod -R 775 /var/www/html/mongodb_data

# ==========================================
# 🚀 DÉMARRAGE
# ==========================================
EXPOSE 80

# Variable d'environnement pour indiquer qu'on est dans Docker
ENV DOCKER_ENV=true

CMD ["apache2-foreground"]