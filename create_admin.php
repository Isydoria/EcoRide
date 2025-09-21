<?php
// Script pour créer le compte administrateur
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

    echo "<h2>👨‍💼 Création compte administrateur</h2>";

    // Vérifier si l'admin existe déjà
    $stmt = $pdo->prepare("SELECT utilisateur_id FROM utilisateur WHERE email = :email");
    $stmt->execute(['email' => 'admin@ecoride.fr']);

    if ($stmt->fetch()) {
        echo "<p>✅ Compte administrateur existe déjà</p>";
    } else {
        // Créer l'administrateur
        $stmt = $pdo->prepare("
            INSERT INTO utilisateur (pseudo, email, password, credit, role, statut)
            VALUES (:pseudo, :email, :password, :credit, 'administrateur', 'actif')
        ");

        $stmt->execute([
            'pseudo' => 'admin',
            'email' => 'admin@ecoride.fr',
            'password' => password_hash('Ec0R1de!', PASSWORD_DEFAULT),
            'credit' => 1000
        ]);

        echo "<p>✅ Compte administrateur créé avec succès !</p>";
    }

    echo "<h3>🔐 Informations de connexion :</h3>";
    echo "<p><strong>Login :</strong> admin</p>";
    echo "<p><strong>Email :</strong> admin@ecoride.fr</p>";
    echo "<p><strong>Mot de passe :</strong> Ec0R1de!</p>";
    echo "<p><strong>Rôle :</strong> administrateur</p>";

    echo '<p><a href="/" style="color: #2ECC71;">← Retour accueil</a></p>';

} catch (Exception $e) {
    echo "<p>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>