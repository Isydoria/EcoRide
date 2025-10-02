<?php
// config/init.php
// Initialisation de l'application EcoRide

// Démarrage de session (si pas déjà démarrée)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration des erreurs (en développement)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/Paris');

// Détection de l'environnement Docker
$isDocker = getenv('DOCKER_ENV') === 'true' || (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === '172.18.0.4');

// Chargement de la bonne configuration de base de données
if (!class_exists('Database')) {
    if ($isDocker && file_exists(__DIR__ . '/database_docker.php')) {
        // Si on est dans Docker et que le fichier docker existe
        require_once __DIR__ . '/database_docker.php';
    } else {
        // Sinon on utilise la config normale (WampServer)
        require_once __DIR__ . '/database.php';
    }
}

// Chargement des fonctions utilitaires si elles existent
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}

// Constantes de l'application (seulement si pas déjà définies)
if (!defined('APP_NAME')) {
    define('APP_NAME', 'EcoRide');
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', $isDocker ? 'http://localhost:8080' : 'http://localhost/ecoride');
}

// Message de debug (à retirer en production)
if ($isDocker) {
    error_log("✅ EcoRide - Running in Docker environment");
} else {
    error_log("✅ EcoRide - Running in local environment (WampServer)");
}
?>