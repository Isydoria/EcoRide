<?php
// config/init.php
// Initialisation de l'application EcoRide

// Démarrage de session (si pas déjà démarrée)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration des erreurs selon l'environnement
$app_env = getenv('APP_ENV') ?: 'development';

if ($app_env === 'production') {
    // Production : masquer les erreurs
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    // Développement : afficher les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Timezone
date_default_timezone_set('Europe/Paris');

// ==========================================
// 🔍 DÉTECTION DE L'ENVIRONNEMENT
// ==========================================
$isRender = getenv('RENDER') === 'true' || getenv('RENDER_SERVICE_NAME') !== false;
$isFlyIO = getenv('FLY_APP_NAME') !== false;
$isDocker = getenv('DOCKER_ENV') === 'true' || (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === '172.18.0.4');
$isWampServer = !$isRender && !$isFlyIO && !$isDocker;

// Log de l'environnement détecté
if ($isRender) {
    error_log("🎨 Environnement détecté : Render.com (Production)");
} elseif ($isFlyIO) {
    error_log("🚀 Environnement détecté : Fly.io (Production)");
} elseif ($isDocker) {
    error_log("🐳 Environnement détecté : Docker (Local)");
} else {
    error_log("💻 Environnement détecté : WampServer (Local)");
}

// ==========================================
// 🗄️ CHARGEMENT DE LA CONFIGURATION BDD
// ==========================================
if (!class_exists('Database')) {
    if ($isRender && file_exists(__DIR__ . '/database_render.php')) {
        // Render.com : PostgreSQL
        require_once __DIR__ . '/database_render.php';
        error_log("✅ Configuration database_render.php chargée (PostgreSQL)");
    } elseif ($isFlyIO && file_exists(__DIR__ . '/database_flyio.php')) {
        // Fly.io : PostgreSQL
        require_once __DIR__ . '/database_flyio.php';
        error_log("✅ Configuration database_flyio.php chargée (PostgreSQL)");
    } elseif ($isDocker && file_exists(__DIR__ . '/database_docker.php')) {
        // Docker local : MySQL
        require_once __DIR__ . '/database_docker.php';
        error_log("✅ Configuration database_docker.php chargée (MySQL)");
    } else {
        // WampServer : MySQL
        require_once __DIR__ . '/database.php';
        error_log("✅ Configuration database.php chargée (MySQL)");
    }
}

// ==========================================
// 🔧 FONCTION HELPER DB
// ==========================================
if (!function_exists('db')) {
    function db() {
        static $database = null;
        if ($database === null) {
            $database = new Database();
        }
        return $database->getConnection();
    }
}

// ==========================================
// 📚 CHARGEMENT DES FONCTIONS UTILITAIRES
// ==========================================
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}

// ==========================================
// 🎯 CONSTANTES DE L'APPLICATION
// ==========================================
if (!defined('APP_NAME')) {
    define('APP_NAME', 'EcoRide');
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}
if (!defined('BASE_URL')) {
    if ($isFlyIO) {
        // Fly.io : utiliser le nom de l'app
        $flyAppName = getenv('FLY_APP_NAME') ?: 'ecoride-covoiturage';
        define('BASE_URL', 'https://' . $flyAppName . '.fly.dev');
    } elseif ($isDocker) {
        define('BASE_URL', 'http://localhost:8080');
    } else {
        define('BASE_URL', 'http://localhost/ecoride');
    }
}

// ==========================================
// 🍃 CHARGEMENT MONGODB FAKE
// ==========================================
if (file_exists(__DIR__ . '/mongodb_fake.php')) {
    require_once __DIR__ . '/mongodb_fake.php';
    
    // Logger le chargement de MongoDB
    if (function_exists('mongodb')) {
        error_log("✅ MongoDB Fake chargé pour PHP " . PHP_VERSION);
    }
}

// ==========================================
// 📊 INFORMATIONS DE DEBUG (développement uniquement)
// ==========================================
if ($app_env !== 'production') {
    error_log("====================================");
    error_log("🎯 EcoRide - Informations système");
    error_log("====================================");
    error_log("PHP Version: " . PHP_VERSION);
    error_log("Environnement: " . $app_env);
    error_log("Base URL: " . BASE_URL);
    error_log("Fly.io: " . ($isFlyIO ? 'Oui' : 'Non'));
    error_log("Docker: " . ($isDocker ? 'Oui' : 'Non'));
    error_log("====================================");
}
?>