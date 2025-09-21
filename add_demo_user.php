<?php
// Script simple pour ajouter un utilisateur de test
$host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? null;
$dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? null;
$username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? null;
$password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? null;

if (!$host || !$dbname || !$username || !$password) {
    die('❌ Variables Railway non trouvées');
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>👤 Ajout utilisateur demo</h2>";

    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT utilisateur_id FROM utilisateur WHERE email = :email");
    $stmt->execute(['email' => 'demo@ecoride.fr']);

    if ($stmt->fetch()) {
        echo "<p>✅ L'utilisateur demo@ecoride.fr existe déjà</p>";
    } else {
        // Créer l'utilisateur demo
        $stmt = $pdo->prepare("
            INSERT INTO utilisateur (pseudo, email, password, credit, role, statut)
            VALUES (:pseudo, :email, :password, :credit, 'utilisateur', 'actif')
        ");

        $stmt->execute([
            'pseudo' => 'demo',
            'email' => 'demo@ecoride.fr',
            'password' => password_hash('demo123', PASSWORD_DEFAULT),
            'credit' => 50
        ]);

        echo "<p>✅ Utilisateur demo créé avec succès !</p>";
        echo "<p><strong>Email:</strong> demo@ecoride.fr</p>";
        echo "<p><strong>Mot de passe:</strong> demo123</p>";
    }

    echo '<p><a href="/" style="color: #2ECC71;">← Retour accueil</a></p>';

} catch (Exception $e) {
    echo "<p>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>