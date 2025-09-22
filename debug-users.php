<?php
// Script de debug pour voir les utilisateurs et tester les mots de passe
require_once 'config/database.php';

try {
    $pdo = db();

    echo "<h2>🔍 Debug Utilisateurs - Base Locale</h2>";

    // Afficher tous les utilisateurs
    $users = $pdo->query("SELECT utilisateur_id, pseudo, email, password, role, credit FROM utilisateur")->fetchAll();

    echo "<h3>Utilisateurs en base :</h3>";
    foreach ($users as $user) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<strong>ID:</strong> " . $user['utilisateur_id'] . "<br>";
        echo "<strong>Pseudo:</strong> " . htmlspecialchars($user['pseudo']) . "<br>";
        echo "<strong>Email:</strong> " . htmlspecialchars($user['email']) . "<br>";
        echo "<strong>Rôle:</strong> " . $user['role'] . "<br>";
        echo "<strong>Crédits:</strong> " . $user['credit'] . "<br>";
        echo "<strong>Hash password:</strong> " . substr($user['password'], 0, 30) . "...<br>";
        echo "</div>";
    }

    // Test de vérification mot de passe
    echo "<h3>🧪 Test mots de passe :</h3>";

    $test_passwords = ['Test123!', 'demo123', 'Ec0R1de!'];

    foreach ($users as $user) {
        echo "<h4>Test pour " . htmlspecialchars($user['email']) . " :</h4>";
        foreach ($test_passwords as $pwd) {
            $verify = password_verify($pwd, $user['password']);
            echo "- Mot de passe '<strong>$pwd</strong>' : " . ($verify ? "✅ MATCH" : "❌ NO MATCH") . "<br>";
        }
        echo "<br>";
    }

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>