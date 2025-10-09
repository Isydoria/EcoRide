<?php
/**
 * api/register-simple.php
 * Inscription utilisateur - VERSION FINALE CORRIGÉE
 */

// Configuration stricte des erreurs (TEMPORAIRE pour debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

// Headers JSON
header('Content-Type: application/json; charset=utf-8');

// Fonction helper pour les réponses JSON
function jsonResponse($success, $message, $data = null, $debug = null) {
    ob_clean();
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($debug !== null) {
        $response['debug'] = $debug;
    }
    echo json_encode($response);
    exit;
}

// Vérifier méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Méthode non autorisée');
}

// Démarrer session
session_start();

// ✅ ÉTAPE 1 : Connexion à la base de données
try {
    // Chemin absolu vers config
    $configPath = __DIR__ . '/../config/database.php';
    
    if (!file_exists($configPath)) {
        jsonResponse(false, 'Fichier de configuration introuvable', null, "Chemin: $configPath");
    }
    
    require_once $configPath;
    
    // Tester la connexion
    $pdo = Database::getInstance()->getPDO();
    
    if (!$pdo) {
        jsonResponse(false, 'Impossible d\'obtenir la connexion PDO');
    }
    
} catch(Exception $e) {
    jsonResponse(false, 'Erreur de connexion à la base de données', null, $e->getMessage());
}

// ✅ ÉTAPE 2 : Récupérer les données POST
$pseudo = isset($_POST['pseudo']) ? trim($_POST['pseudo']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
$terms = isset($_POST['terms']);

// ✅ ÉTAPE 3 : Validations côté serveur
$errors = [];

// Validation pseudo
if (empty($pseudo)) {
    $errors[] = 'Le pseudo est obligatoire';
} elseif (strlen($pseudo) < 3 || strlen($pseudo) > 20) {
    $errors[] = 'Le pseudo doit contenir entre 3 et 20 caractères';
} elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $pseudo)) {
    $errors[] = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores';
}

// Validation email
if (empty($email)) {
    $errors[] = 'L\'email est obligatoire';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format d\'email invalide';
}

// Validation mot de passe
if (empty($password)) {
    $errors[] = 'Le mot de passe est obligatoire';
} elseif (strlen($password) < 8) {
    $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
} elseif ($password !== $password_confirm) {
    $errors[] = 'Les mots de passe ne correspondent pas';
}

// Validation CGU
if (!$terms) {
    $errors[] = 'Vous devez accepter les conditions d\'utilisation';
}

// Si erreurs de validation
if (!empty($errors)) {
    jsonResponse(false, implode(', ', $errors));
}

// ✅ ÉTAPE 4 : Vérifier si email existe déjà
try {
    $stmt = $pdo->prepare("SELECT utilisateur_id FROM utilisateur WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        jsonResponse(false, 'Cet email est déjà utilisé');
    }
    
} catch(PDOException $e) {
    jsonResponse(false, 'Erreur lors de la vérification de l\'email', null, $e->getMessage());
}

// ✅ ÉTAPE 5 : Vérifier si pseudo existe déjà
try {
    $stmt = $pdo->prepare("SELECT utilisateur_id FROM utilisateur WHERE pseudo = :pseudo");
    $stmt->bindParam(':pseudo', $pseudo);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        jsonResponse(false, 'Ce pseudo est déjà utilisé');
    }
    
} catch(PDOException $e) {
    jsonResponse(false, 'Erreur lors de la vérification du pseudo', null, $e->getMessage());
}

// ✅ ÉTAPE 6 : Hasher le mot de passe
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ✅ ÉTAPE 7 : Insérer l'utilisateur dans la base
try {
    $stmt = $pdo->prepare("
        INSERT INTO utilisateur (
            pseudo, 
            email, 
            password, 
            credit, 
            role, 
            statut,
            created_at
        ) VALUES (
            :pseudo, 
            :email, 
            :password, 
            20, 
            'utilisateur', 
            'actif',
            NOW()
        )
    ");
    
    $stmt->bindParam(':pseudo', $pseudo);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    
    $result = $stmt->execute();
    
    if (!$result) {
        jsonResponse(false, 'Erreur lors de l\'insertion', null, $stmt->errorInfo());
    }
    
    $user_id = $pdo->lastInsertId();
    
} catch(PDOException $e) {
    jsonResponse(false, 'Erreur lors de l\'inscription', null, $e->getMessage());
}

// ✅ ÉTAPE 8 : Créer la session utilisateur
try {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['pseudo'] = $pseudo;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'utilisateur';
    $_SESSION['credits'] = 20;
    $_SESSION['logged_in'] = true;
    
} catch(Exception $e) {
    jsonResponse(false, 'Erreur lors de la création de la session', null, $e->getMessage());
}

// ✅ ÉTAPE 9 : Réponse de succès
jsonResponse(true, 'Inscription réussie ! Bienvenue sur EcoRide 🎉', [
    'user_id' => $user_id,
    'pseudo' => $pseudo,
    'redirect' => '/ecoride/user/dashboard.php'
]);
?>