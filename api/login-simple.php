<?php
/**
 * api/login-simple.php
 * Version simplifiée et fonctionnelle de la connexion
 */

// Désactiver l'affichage des erreurs en production
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Démarrer le buffer de sortie
ob_start();

// Headers pour JSON
header('Content-Type: application/json; charset=utf-8');

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Méthode non autorisée']));
}

// Connexion Railway adaptative
try {
    $host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? 'localhost';
    $dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecoride_db';
    $username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? 'root';
    $password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']));
}

// Démarrer la session
session_start();

// Récupérer les données
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']) ? true : false;

// Validation
if (empty($email) || empty($password)) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Format d\'email invalide']));
}

try {
    // Rechercher l'utilisateur
    $stmt = $pdo->prepare("
        SELECT id_utilisateur, pseudo, email, mot_de_passe, credits, role, actif
        FROM utilisateurs 
        WHERE email = :email
        LIMIT 1
    ");
    
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier l'utilisateur et le mot de passe
    if (!$user) {
        ob_clean();
        die(json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']));
    }
    
    if (!password_verify($password, $user['mot_de_passe'])) {
        ob_clean();
        die(json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']));
    }
    
    if (!$user['actif']) {
        ob_clean();
        die(json_encode(['success' => false, 'message' => 'Votre compte a été désactivé']));
    }
    
    // Connexion réussie - Créer les sessions
    $_SESSION['user_id'] = $user['id_utilisateur'];
    $_SESSION['user_pseudo'] = $user['pseudo'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_credits'] = $user['credits'];
    $_SESSION['user_role'] = $user['role'];
    
    // Régénérer l'ID de session pour la sécurité
    session_regenerate_id(true);
    
    // Si "Se souvenir de moi" est coché (optionnel)
    if ($remember) {
        // Créer un cookie sécurisé (à implémenter plus tard)
        // setcookie('remember_token', $token, time() + (86400 * 30), "/", "", true, true);
    }
    
    // Déterminer la redirection selon le rôle
    $redirectUrl = 'user/dashboard.php';
    if ($user['role'] === 'administrateur') {
        $redirectUrl = 'admin/dashboard.php';
    } elseif ($user['role'] === 'employe') {
        $redirectUrl = 'employe/dashboard.php';
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie ! Redirection en cours...',
        'data' => [
            'redirect' => $redirectUrl,
            'user' => $user['pseudo']
        ]
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue : ' . $e->getMessage()
    ]);
}
?>