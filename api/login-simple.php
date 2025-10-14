<?php
/**
 * API de connexion - Compatible MySQL et PostgreSQL
 * Fonctionne en local (WampServer/Docker) et sur Render
 */

// Démarrage de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger la configuration
require_once '../config/init.php';

// Headers JSON
header('Content-Type: application/json; charset=utf-8');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

try {
    // Récupérer les données POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    // Validation des données
    if (empty($email) || empty($password)) {
        throw new Exception('Email et mot de passe requis');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email invalide');
    }
    
    // Connexion à la base de données
    $conn = db();

    // Détecter le type de base de données
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    // ==========================================
    // 🔄 REQUÊTE COMPATIBLE MySQL ET PostgreSQL
    // ==========================================
    if ($isPostgreSQL) {
        // PostgreSQL : utilise is_active (boolean)
        $query = "SELECT * FROM utilisateur WHERE email = :email AND is_active = true";
    } else {
        // MySQL : utilise statut (enum)
        $query = "SELECT * FROM utilisateur WHERE email = :email AND statut = 'actif'";
    }

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'utilisateur existe
    if (!$user) {
        throw new Exception('Email ou mot de passe incorrect');
    }
    
    // ==========================================
    // 🔐 VÉRIFICATION MOT DE PASSE
    // Compatible "password" ET "mot_de_passe"
    // ==========================================
    $passwordField = isset($user['mot_de_passe']) ? 'mot_de_passe' : 'password';
    $storedPassword = $user[$passwordField];
    
    if (!password_verify($password, $storedPassword)) {
        throw new Exception('Email ou mot de passe incorrect');
    }
    
    // ==========================================
    // ✅ CONNEXION RÉUSSIE - CRÉER LA SESSION
    // ==========================================
    
    // ID utilisateur (compatible utilisateur_id OU id)
    $userId = $user['id'] ?? $user['utilisateur_id'];
    
    // Crédits (compatible credit OU credits)
    $userCredits = $user['credits'] ?? $user['credit'] ?? 50;
    
    // is_conducteur (peut ne pas exister)
    $isConducteur = isset($user['is_conducteur']) ? (bool)$user['is_conducteur'] : false;
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_pseudo'] = $user['pseudo'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_credits'] = $userCredits;
    $_SESSION['is_conducteur'] = $isConducteur;
    
    // Logger l'activité (si MongoDB Fake disponible)
    if (function_exists('mongodb')) {
        try {
            mongodb()->logActivity(
                $userId,
                'login',
                [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            );
        } catch (Exception $e) {
            // Erreur MongoDB non bloquante
            error_log("⚠️ MongoDB logging: " . $e->getMessage());
        }
    }
    
    // Déterminer la redirection selon le rôle
    // Détection de l'environnement pour adapter les chemins
    $isDocker = getenv('DOCKER_ENV') === 'true';
    $baseUrl = $isDocker ? '' : '/ecoride';

    $redirectUrl = $baseUrl . '/user/dashboard.php';

    if ($user['role'] === 'administrateur') {
        $redirectUrl = $baseUrl . '/admin/dashboard.php';
    } elseif ($user['role'] === 'employe') {
        $redirectUrl = $baseUrl . '/employee/dashboard.php';
    }
    
    // Réponse de succès
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'user' => [
            'id' => $userId,
            'email' => $user['email'],
            'pseudo' => $user['pseudo'],
            'role' => $user['role'],
            'credits' => $userCredits
        ],
        'redirect' => $redirectUrl
    ]);
    
} catch (PDOException $e) {
    // Erreur base de données
    error_log("❌ Login DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]);
    
} catch (Exception $e) {
    // Erreur métier (validation, authentification)
    error_log("⚠️ Login error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>