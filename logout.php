<?php
/**
 * FICHIER: logout.php
 * Déconnexion de l'utilisateur
 */
require_once 'config/init.php';

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire le cookie "Se souvenir de moi" si présent
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header("Location: index.php");
exit();
?>