<?php
/**
 * FICHIER: config/init.php
 * Initialisation de l'application EcoRide
 */

// Gestion des erreurs (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration du fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration des chemins
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// URL de base (à adapter selon votre configuration)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$folder = '/ecoride'; // Adapter selon votre structure
define('BASE_URL', $protocol . $host . $folder);

// Inclure les fichiers de configuration
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/functions.php';

// Démarrer la session sécurisée
startSecureSession();

// Générer un token CSRF si nécessaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Configuration des constantes de l'application
define('CREDITS_INSCRIPTION', 20);
define('COMMISSION_PLATEFORME', 2);
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_PSEUDO_LENGTH', 20);
define('MIN_PSEUDO_LENGTH', 3);

// Configuration MongoDB (pour plus tard)
define('MONGODB_URI', 'mongodb://localhost:27017');
define('MONGODB_DATABASE', 'ecoride_nosql');

/**
 * Autoloader simple pour les classes (si besoin)
 */
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/classes/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Fonction pour vérifier les permissions d'accès
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/connexion.php');
    }
}

/**
 * Fonction pour vérifier le rôle
 */
function requireRole($role) {
    requireLogin();
    if (getCurrentUserRole() !== $role) {
        $_SESSION['error'] = 'Accès non autorisé';
        redirect('/index.php');
    }
}

/**
 * Fonction pour vérifier si l'utilisateur est conducteur
 */
function isConducteur() {
    if (!isLoggedIn()) return false;
    
    try {
        $stmt = db()->prepare("
            SELECT accepte_conducteur 
            FROM preferences_conducteur 
            WHERE id_utilisateur = ?
        ");
        $stmt->execute([getCurrentUserId()]);
        $result = $stmt->fetch();
        return $result && $result['accepte_conducteur'] == 1;
    } catch (PDOException $e) {
        logError('Erreur vérification conducteur: ' . $e->getMessage());
        return false;
    }
}
?>