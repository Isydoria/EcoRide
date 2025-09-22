<?php
// Script rapide pour créer l'utilisateur demo manquant
require_once 'config/database.php';

try {
    $pdo = db();

    // Hash du mot de passe demo123
    $password = password_hash('demo123', PASSWORD_DEFAULT);

    // Insérer l'utilisateur demo
    $sql = "INSERT INTO utilisateur (pseudo, email, password, role, credit) VALUES (?, ?, ?, 'utilisateur', 50)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(['demo', 'demo@ecoride.fr', $password]);

    if ($result) {
        echo "✅ Utilisateur demo@ecoride.fr créé avec succès !<br>";
        echo "Mot de passe : demo123<br>";
        echo "<a href='connexion.php'>Se connecter maintenant</a>";
    } else {
        echo "❌ Erreur lors de la création";
    }

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>