<?php
// NE PAS inclure d'autres fichiers pour le moment
header('Content-Type: application/json; charset=utf-8');

// Connexion directe à la base
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=ecoride_db;charset=utf8mb4',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tester la connexion pour jean@email.com
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
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
