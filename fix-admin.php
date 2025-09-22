<?php
// Script pour recréer l'admin avec un mot de passe qui fonctionne
require_once 'config/database.php';

try {
    $pdo = db();

    // Nouveau hash pour "Test123!"
    $new_hash = password_hash('Test123!', PASSWORD_DEFAULT);

    echo "<h2>🔧 Correction mot de passe admin</h2>";

    // Mettre à jour l'admin
    $sql = "UPDATE utilisateur SET password = ? WHERE email = 'admin@ecoride.fr'";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$new_hash]);

    if ($result) {
        echo "✅ Mot de passe admin mis à jour !<br>";
        echo "<strong>Compte :</strong> admin@ecoride.fr<br>";
        echo "<strong>Mot de passe :</strong> Test123!<br>";
        echo "<br><a href='connexion.php'>🔗 Tester la connexion</a>";

        // Test immédiat
        if (password_verify('Test123!', $new_hash)) {
            echo "<br><br>✅ Vérification : Le nouveau hash fonctionne !";
        } else {
            echo "<br><br>❌ Erreur : Le hash ne fonctionne pas";
        }
    } else {
        echo "❌ Erreur lors de la mise à jour";
    }

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>