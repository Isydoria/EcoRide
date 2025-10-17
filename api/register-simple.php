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
    require_once __DIR__ . '/../config/init.php';
    require_once __DIR__ . '/../config/rate-limiter.php';
    $pdo = db();

    if (!$pdo) {
        jsonResponse(false, 'Impossible d\'obtenir la connexion PDO');
    }

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

} catch(Exception $e) {
    jsonResponse(false, 'Erreur de connexion à la base de données', null, $e->getMessage());
}

// ✅ Rate Limiting - Protection anti-spam
$rateLimiter = new RateLimiter($pdo);
$clientIP = RateLimiter::getClientIP();

$rateCheck = $rateLimiter->check($clientIP, 'register', 3, 900); // 3 inscriptions max par 15 min

if (!$rateCheck['allowed']) {
    jsonResponse(false, $rateCheck['message']);
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

// Validation mot de passe renforcée
if (empty($password)) {
    $errors[] = 'Le mot de passe est obligatoire';
} elseif (strlen($password) < 12) {
    $errors[] = 'Le mot de passe doit contenir au moins 12 caractères';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
} elseif (!preg_match('/[a-z]/', $password)) {
    $errors[] = 'Le mot de passe doit contenir au moins une minuscule';
} elseif (!preg_match('/[0-9]/', $password)) {
    $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
} elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*...)';
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
    if ($isPostgreSQL) {
        // PostgreSQL
        $stmt = $pdo->prepare("
            INSERT INTO utilisateur (
                pseudo,
                email,
                password,
                credits,
                role,
                is_active,
                date_inscription
            ) VALUES (
                :pseudo,
                :email,
                :password,
                20,
                'utilisateur',
                true,
                CURRENT_TIMESTAMP
            )
            RETURNING utilisateur_id
        ");

        $stmt->bindParam(':pseudo', $pseudo);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        $result = $stmt->execute();

        if (!$result) {
            jsonResponse(false, 'Erreur lors de l\'insertion', null, $stmt->errorInfo());
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['utilisateur_id'];

    } else {
        // MySQL
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
    }

} catch(PDOException $e) {
    jsonResponse(false, 'Erreur lors de l\'inscription', null, $e->getMessage());
}

// ✅ Inscription réussie - Réinitialiser le compteur rate limiting
$rateLimiter->reset($clientIP, 'register');

// ✅ ÉTAPE 8 : Créer la session utilisateur
try {
    // Régénérer l'ID de session pour éviter la fixation de session
    session_regenerate_id(true);

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
// Détection de l'environnement pour adapter les chemins
$isDocker = getenv('DOCKER_ENV') === 'true';
$baseUrl = $isDocker ? '' : '/ecoride';

jsonResponse(true, 'Inscription réussie ! Bienvenue sur EcoRide 🎉', [
    'user_id' => $user_id,
    'pseudo' => $pseudo,
    'redirect' => $baseUrl . '/user/dashboard.php'
]);
?>