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
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtenir l'ID de l'utilisateur connecté
 */
function getCurrentUserId() {
    startSecureSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtenir le rôle de l'utilisateur connecté
 */
function getCurrentUserRole() {
    startSecureSession();
    return $_SESSION['user_role'] ?? null;
}

/**
 * Obtenir les crédits de l'utilisateur connecté
 */
function getCurrentUserCredits() {
    startSecureSession();
    return $_SESSION['user_credits'] ?? 0;
}

/**
 * Rediriger vers une URL
 */
function redirect($url) {
    // Si l'URL commence par /, ajouter le dossier du projet
    if (strpos($url, '/') === 0) {
        $url = '/ecoride' . $url;
    }
    header("Location: $url");
    exit();
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
    startSecureSession();
    return isset($_SESSION['csrf_token']) && 
        hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formater une date en français
 */
function formatDateFr($date) {
    $formatter = new IntlDateFormatter(
        'fr_FR',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE
    );
    return $formatter->format(new DateTime($date));
}

/**
 * Formater l'heure
 */
function formatTime($time) {
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
?>