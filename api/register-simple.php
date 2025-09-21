<?php
/**
 * api/register-simple.php
 * Version simplifiée et fonctionnelle de l'inscription
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

// Connexion directe à la base
try {
    // Connexion Railway adaptative
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

// Récupérer et valider les données
$pseudo = isset($_POST['pseudo']) ? trim($_POST['pseudo']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
$terms = isset($_POST['terms']) ? true : false;

// Tableau pour stocker les erreurs
$errors = [];

// Validation du pseudo
if (empty($pseudo)) {
    $errors[] = 'Le pseudo est obligatoire';
} elseif (strlen($pseudo) < 3 || strlen($pseudo) > 20) {
    $errors[] = 'Le pseudo doit contenir entre 3 et 20 caractères';
} elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $pseudo)) {
    $errors[] = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores';
}

// Validation de l'email
if (empty($email)) {
    $errors[] = 'L\'email est obligatoire';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format d\'email invalide';
}

// Validation du mot de passe
if (empty($password)) {
    $errors[] = 'Le mot de passe est obligatoire';
} elseif (strlen($password) < 8) {
    $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
    $errors[] = 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre';
}

// Validation de la confirmation du mot de passe
if ($password !== $password_confirm) {
    $errors[] = 'Les mots de passe ne correspondent pas';
}

// Validation des conditions d'utilisation
if (!$terms) {
    $errors[] = 'Vous devez accepter les conditions d\'utilisation';
}

// Si des erreurs existent, les retourner
if (!empty($errors)) {
    ob_clean();
    die(json_encode([
        'success' => false, 
        'message' => implode('<br>', $errors)
    ]));
}

try {
    // Vérifier si le pseudo existe déjà
    $stmt = $pdo->prepare("SELECT utilisateur_id FROM utilisateur WHERE pseudo = :pseudo");
    $stmt->execute(['pseudo' => $pseudo]);
    if ($stmt->fetch()) {
        ob_clean();
        die(json_encode(['success' => false, 'message' => 'Ce pseudo est déjà utilisé']));
    }
    
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT utilisateur_id FROM utilisateur WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        ob_clean();
        die(json_encode(['success' => false, 'message' => 'Cet email est déjà associé à un compte']));
    }
    
    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Insérer le nouvel utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO utilisateur (
            pseudo, email, password, credit, role, statut, created_at
        ) VALUES (
            :pseudo, :email, :password, :credit, 'utilisateur', 'actif', NOW()
        )
    ");
    
    $result = $stmt->execute([
        'pseudo' => $pseudo,
        'email' => $email,
        'password' => $hashedPassword,
        'credit' => 20 // Crédits de bienvenue
    ]);
    
    if (!$result) {
        throw new Exception('Erreur lors de la création du compte');
    }
    
    $userId = $pdo->lastInsertId();
    
    // Créer les préférences par défaut pour l'utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO preferences_conducteur (
            id_utilisateur, accepte_conducteur, accepte_fumeur, accepte_animaux, 
            accepte_musique, accepte_discussion
        ) VALUES (
            :user_id, FALSE, FALSE, FALSE, TRUE, TRUE
        )
    ");
    $stmt->execute(['user_id' => $userId]);
    
    // Enregistrer la transaction dans la table transactions
    $stmt = $pdo->prepare("
        INSERT INTO transactions (
            id_utilisateur, montant, type_transaction, description, type_reference
        ) VALUES (
            :user_id, 20, 'credit', 'Bonus d\'inscription', 'bonus'
        )
    ");
    $stmt->execute(['user_id' => $userId]);
    
    // Valider la transaction
    $pdo->commit();
    
    // Connexion automatique après inscription
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_pseudo'] = $pseudo;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_credits'] = 20;
    $_SESSION['user_role'] = 'utilisateur';
    
    // Régénérer l'ID de session pour la sécurité
    session_regenerate_id(true);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Compte créé avec succès ! Vous avez reçu 20 crédits gratuits.',
        'data' => [
            'redirect' => '../user/dashboard.php'
        ]
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue lors de la création du compte : ' . $e->getMessage()
    ]);
}
?>