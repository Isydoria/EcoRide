# ==========================================
# 🐳 DOCKERFILE - EcoRide Platform (Render)
# ==========================================
# Image PHP 8.2 + Apache optimisée pour Render.com

FROM php:8.2-apache

# ==========================================
# 📦 MÉTADONNÉES
# ==========================================
LABEL maintainer="EcoRide Team"
LABEL version="1.0.0"
LABEL description="EcoRide ecological carpooling platform - Render.com deployment"

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
    # PostgreSQL client (pour Render)
    libpq-dev \
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
        pdo_pgsql \
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
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /var/log/php_errors.log'; \
        echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Configuration OPcache pour production
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# ==========================================
# 🌐 CONFIGURATION APACHE
# ==========================================
# Activer mod_rewrite pour URL rewriting
RUN a2enmod rewrite headers expires deflate

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

# Créer le dossier pour MongoDB Fake
RUN mkdir -p /var/www/html/mongodb_data \
    && chown -R www-data:www-data /var/www/html/mongodb_data \
    && chmod -R 775 /var/www/html/mongodb_data

# Créer le dossier pour les logs
RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod -R 775 /var/www/html/logs

# ==========================================
# 🚀 DÉMARRAGE
# ==========================================
# Render utilise la variable $PORT (dynamique)
# Apache doit écouter sur ce port

# Exposer le port (pour documentation, Render utilise $PORT)
EXPOSE 80

# Variables d'environnement
ENV DOCKER_ENV=true
ENV APP_ENV=production
ENV PORT=80

# Script de démarrage personnalisé
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]