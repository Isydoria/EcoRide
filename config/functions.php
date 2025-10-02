<?php
/**
 * FICHIER: config/functions.php
 * Fonctions utilitaires pour l'application EcoRide
 */

/**
 * Nettoyer les données d'entrée
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Vérifier si l'email est valide
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Démarrer une session sécurisée
 */
function startSecureSession() {
    if (session_status() == PHP_SESSION_NONE) {
        // Configuration sécurisée de la session
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        
        session_start();
        
        // Régénérer l'ID de session périodiquement
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

/**
 * Vérifier si un utilisateur est connecté
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Obtenir l'ID de l'utilisateur connecté
 */
function getCurrentUserId() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtenir le rôle de l'utilisateur connecté
 */
function getCurrentUserRole() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_role'] ?? null;
}

/**
 * Obtenir les crédits de l'utilisateur connecté
 */
function getCurrentUserCredits() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_credits'] ?? 0;
}

/**
 * Vérifier le rôle
 */
if (!function_exists('hasRole')) {
    function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
}

/**
 * Rediriger vers une URL
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        // Détection automatique de l'environnement
        $isDocker = getenv('DOCKER_ENV') === 'true';
        
        // Si l'URL commence par /, adapter selon l'environnement
        if (strpos($url, '/') === 0) {
            if ($isDocker) {
                // Docker : pas de sous-dossier
                // L'URL reste telle quelle
            } else {
                // WampServer : ajouter /ecoride
                $url = '/ecoride' . $url;
            }
        }
        
        header("Location: $url");
        exit();
    }
}

/**
 * Sécuriser les sorties HTML (alias pour e())
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sécuriser les sorties HTML (alias pour e())
 */
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Vérifier si la requête est AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Envoyer une réponse JSON
 */
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

/**
 * Générer un token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier le token CSRF
 */
function verifyCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && 
        hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formater une date en français
 */
function formatDateFr($date) {
    if (empty($date)) return '';
    
    // Si IntlDateFormatter est disponible
    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE
        );
        return $formatter->format(new DateTime($date));
    }
    
    // Sinon, formatage basique
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
             'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $dt = new DateTime($date);
    return $dt->format('j ') . $mois[$dt->format('n')] . $dt->format(' Y');
}

/**
 * Formater l'heure
 */
function formatTime($time) {
    if (empty($time)) return '';
    return date('H\hi', strtotime($time));
}

/**
 * Calculer le temps restant avant un trajet
 */
function getTimeUntilDeparture($dateTime) {
    $now = new DateTime();
    $departure = new DateTime($dateTime);
    $interval = $now->diff($departure);
    
    if ($interval->days > 0) {
        return "Dans " . $interval->days . " jour" . ($interval->days > 1 ? "s" : "");
    } elseif ($interval->h > 0) {
        return "Dans " . $interval->h . " heure" . ($interval->h > 1 ? "s" : "");
    } else {
        return "Dans " . $interval->i . " minute" . ($interval->i > 1 ? "s" : "");
    }
}

/**
 * Vérifier si un véhicule est écologique
 */
function isEcologique($typeCarburant) {
    $ecologiques = ['electrique', 'hybride', 'hydrogene'];
    return in_array(strtolower($typeCarburant), $ecologiques);
}

/**
 * Calculer la durée entre deux heures
 */
function calculerDuree($depart, $arrivee) {
    $dep = new DateTime($depart);
    $arr = new DateTime($arrivee);
    $diff = $dep->diff($arr);
    
    $heures = $diff->h + ($diff->days * 24);
    $minutes = $diff->i;
    
    if ($heures > 0) {
        return $heures . 'h' . ($minutes > 0 ? sprintf('%02d', $minutes) : '');
    } else {
        return $minutes . 'min';
    }
}

/**
 * Logger les erreurs
 */
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    $logMessage .= PHP_EOL;
    
    error_log($logMessage, 3, $logFile);
}

/**
 * Fonction pour obtenir rapidement la connexion PDO
 * Compatible avec l'ancien code qui utilise db()
 */
function db() {
    $database = Database::getInstance();
    return $database->getConnection();
}

/**
 * Message flash dans la session
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Récupérer et supprimer le message flash
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Vérifier si l'utilisateur est admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrateur';
}

/**
 * Vérifier si l'utilisateur est employé
 */
function isEmployee() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employe';
}

/**
 * Vérifier si l'utilisateur est conducteur
 */
function isDriver() {
    return isset($_SESSION['is_driver']) && $_SESSION['is_driver'] === true;
}

/**
 * Obtenir le pseudo de l'utilisateur
 */
function getUserPseudo() {
    return $_SESSION['user_pseudo'] ?? 'Utilisateur';
}
?>