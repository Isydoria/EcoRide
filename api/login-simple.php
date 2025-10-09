<?php
/**
 * api/login-simple.php
 * Version simplifiée et fonctionnelle de la connexion
 * + Logs MongoDB pour historique activité
 */

// Désactiver l'affichage des erreurs en production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer le buffer de sortie
ob_start();

// Headers pour JSON
header('Content-Type: application/json; charset=utf-8');

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Méthode non autorisée']));
}

// Connexion à la base de données avec la classe Database
require_once '../config/init.php';

try {
    $pdo = db();
} catch(Exception $e) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']));
}

// Démarrer la session
session_start();

// Récupérer les données
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']) ? true : false;

// Validation des données
if (empty($email) || empty($password)) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Email et mot de passe requis']));
}

// Validation format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Format email invalide']));
}

try {
    // Rechercher l'utilisateur par email (✅ CORRIGÉ : statut au lieu de actif)
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = :email AND statut = 'actif'");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        ob_clean();
        die(json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']));
    }

    // ✅ CORRECTION : Utiliser 'password' au lieu de 'mot_de_passe'
    if (!password_verify($password, $user['password'])) {
        ob_clean();
        die(json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']));
    }
    
    // Connexion réussie - Créer la session
    $_SESSION['user_id'] = $user['utilisateur_id'];
    $_SESSION['user_pseudo'] = $user['pseudo'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_credits'] = $user['credit'];
    $_SESSION['is_driver'] = false; // À mettre à jour selon la logique métier
    
    // 🆕 LOGGER LA CONNEXION DANS MONGODB
    if (function_exists('mongodb')) {
        try {
            $mongo = mongodb();
            $mongo->logActivity($user['utilisateur_id'], 'login', [
                'email' => $user['email'],
                'pseudo' => $user['pseudo'],
                'role' => $user['role'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Si MongoDB échoue, on continue quand même
            error_log("MongoDB log error: " . $e->getMessage());
        }
    }
    
    // Remember me
    if ($remember) {
        // Cookie sécurisé pour 30 jours
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'redirect' => $user['role'] === 'administrateur' ? '/ecoride/admin/dashboard.php' : '/ecoride/user/dashboard.php',
        'user' => [
            'id' => $user['utilisateur_id'],
            'pseudo' => $user['pseudo'],
            'email' => $user['email'],
            'role' => $user['role'],
            'credits' => $user['credit']
        ]
    ]);
    
} 

catch(PDOException $e) {
    ob_clean();
    error_log("Login error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Erreur lors de la connexion']));
}
?>