<?php
/**
 * API de connexion simple
 * Compatible MySQL (local) et PostgreSQL (Render)
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
    
    // ==========================================
    // 🔄 REQUÊTE COMPATIBLE MySQL ET PostgreSQL
    // ==========================================
    // Essayer d'abord avec statut (MySQL local)
    // Si erreur, réessayer sans statut (PostgreSQL Render)
    
    $user = null;
    $stmt = null;
    
    try {
        // Tentative 1 : AVEC colonne statut (MySQL)
        $query = "SELECT * FROM utilisateur WHERE email = :email AND statut = 'actif'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        // Si erreur liée à la colonne statut, réessayer sans
        if (strpos($e->getMessage(), 'statut') !== false || 
            strpos($e->getMessage(), 'column') !== false ||
            strpos($e->getMessage(), 'Undefined column') !== false) {
            
            error_log("ℹ️ Colonne 'statut' non trouvée, tentative sans statut");
            
            // Tentative 2 : SANS colonne statut (PostgreSQL)
            $query = "SELECT * FROM utilisateur WHERE email = :email";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Autre erreur SQL, la propager
            throw $e;
        }
    }
    
    // Vérifier si l'utilisateur existe
    if (!$user) {
        throw new Exception('Email ou mot de passe incorrect');
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $user['mot_de_passe'])) {
        throw new Exception('Email ou mot de passe incorrect');
    }
    
    // Connexion réussie : créer la session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_pseudo'] = $user['pseudo'];
    $_SESSION['user_role'] = $user['role'] ?? 'passager';
    $_SESSION['user_credits'] = $user['credits'] ?? 50.00;
    $_SESSION['is_conducteur'] = isset($user['is_conducteur']) ? (bool)$user['is_conducteur'] : false;
    
    // Logger l'activité (si MongoDB Fake disponible)
    if (function_exists('mongodb')) {
        try {
            mongodb()->logActivity(
                $user['id'],
                'login',
                [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            );
        } catch (Exception $e) {
            // Erreur MongoDB non bloquante
            error_log("⚠️ Erreur MongoDB logging: " . $e->getMessage());
        }
    }
    
    // Réponse de succès
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'pseudo' => $user['pseudo'],
            'role' => $_SESSION['user_role'],
            'credits' => $_SESSION['user_credits']
        ],
        'redirect' => $user['role'] === 'administrateur' ? '/admin/dashboard.php' :
                     ($user['role'] === 'employe' ? '/employee/dashboard.php' : '/user/dashboard.php')
    ]);
    
} catch (PDOException $e) {
    // Erreur base de données
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]);
    
} catch (Exception $e) {
    // Erreur métier (validation, authentification)
    error_log("Login error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>