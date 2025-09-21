<?php
// NE PAS inclure d'autres fichiers pour le moment
header('Content-Type: application/json; charset=utf-8');

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
    
    // Tester la connexion pour jean@email.com
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $stmt->execute(['jean@email.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Vérifier le mot de passe
        if (password_verify('Test123!', $user['mot_de_passe'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Connexion OK',
                'user' => $user['pseudo']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Mot de passe incorrect'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non trouvé'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
