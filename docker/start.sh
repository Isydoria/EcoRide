#!/bin/bash
# ==========================================
# 🚀 SCRIPT DE DÉMARRAGE APACHE - Render.com
# ==========================================
# Configure Apache pour écouter sur le port fourni par Render ($PORT)

set -e

# Récupérer le port fourni par Render (par défaut 10000)
PORT=${PORT:-80}

echo "🚀 Démarrage d'Apache sur le port $PORT..."

# Modifier la configuration Apache pour utiliser le bon port
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/*.conf

# Afficher la configuration pour debug
echo "📝 Configuration Apache ports.conf :"
cat /etc/apache2/ports.conf | grep Listen

echo "📝 Configuration VirtualHost :"
grep -E "VirtualHost|ServerName" /etc/apache2/sites-available/000-default.conf

echo "✅ Apache configuré pour le port $PORT"

# Démarrer Apache en premier plan
echo "🌐 Démarrage d'Apache..."
exec apache2-foreground