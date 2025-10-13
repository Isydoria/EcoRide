<?php
/**
 * Script d'initialisation ULTRA-SIMPLE
 * Utilise pg_connect au lieu de PDO
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Init Simple</title>
    <style>
        body { font-family: monospace; background: #111; color: #0f0; padding: 20px; }
        .ok { color: #0f0; }
        .error { color: #f00; }
        pre { background: #000; padding: 10px; border: 1px solid #333; margin: 10px 0; }
        h2 { color: #0ff; }
    </style>
</head>
<body>
<h1>🚀 Init PostgreSQL Simple</h1>

<?php

if (!getenv('RENDER')) {
    die("<p class='error'>❌ Render uniquement</p></body></html>");
}

echo "<p class='ok'>✅ Render détecté</p>";

// Parser DATABASE_URL
$dbUrl = getenv('DATABASE_URL');
if (!$dbUrl) {
    die("<p class='error'>❌ DATABASE_URL non défini</p></body></html>");
}

$parts = parse_url($dbUrl);
$host = $parts['host'];
$port = $parts['port'] ?? 5432;
$dbname = ltrim($parts['path'], '/');
$user = $parts['user'];
$pass = $parts['pass'];

echo "<pre>";
echo "Host: $host\n";
echo "Port: $port\n";
echo "DB: $dbname\n";
echo "User: $user\n";
echo "</pre>";

// Tenter connexion avec pg_connect
echo "<h2>Tentative connexion pg_connect...</h2>";

$connString = "host=$host port=$port dbname=$dbname user=$user password=$pass sslmode=require";

try {
    if (!function_exists('pg_connect')) {
        // Fallback: utiliser PDO avec DSN explicite
        echo "<p class='error'>pg_connect indisponible, tentative PDO...</p>";

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        echo "<pre>DSN: $dsn</pre>";

        // Créer PDO avec options explicites
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        $db = new PDO($dsn, $user, $pass, $options);
        echo "<p class='ok'>✅ Connexion PDO réussie !</p>";

    } else {
        $db = pg_connect($connString);
        if (!$db) {
            throw new Exception("Connexion échouée");
        }
        echo "<p class='ok'>✅ Connexion pg_connect réussie !</p>";

        // Convertir en PDO pour le reste du script
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        $db = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    // Créer tables
    echo "<h2>📋 Création tables</h2>";

    $db->exec("DROP TABLE IF EXISTS paiement, message, avis, preference, participation, covoiturage, vehicule, utilisateur CASCADE");
    echo "<p class='ok'>✓ Nettoyage effectué</p>";

    // Table utilisateur
    $db->exec("
        CREATE TABLE utilisateur (
            utilisateur_id SERIAL PRIMARY KEY,
            pseudo VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            telephone VARCHAR(20),
            role VARCHAR(20) DEFAULT 'utilisateur',
            credits INTEGER DEFAULT 20,
            is_conducteur BOOLEAN DEFAULT FALSE,
            date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    echo "<p class='ok'>✓ utilisateur</p>";

    // Insérer admin
    echo "<h2>👤 Création utilisateurs</h2>";

    $stmt = $db->prepare("INSERT INTO utilisateur (pseudo, email, password, role, credits, is_conducteur) VALUES (?, ?, ?, ?, ?, ?)");

    $stmt->execute(['Admin', 'admin@ecoride.fr', password_hash('Ec0R1de!', PASSWORD_DEFAULT), 'administrateur', 100, 't']);
    echo "<p class='ok'>✓ Admin créé</p>";

    $stmt->execute(['Demo', 'demo@ecoride.fr', password_hash('demo123', PASSWORD_DEFAULT), 'utilisateur', 50, 't']);
    echo "<p class='ok'>✓ Demo créé</p>";

    $stmt->execute(['Jean Dupont', 'jean@example.com', password_hash('Test123!', PASSWORD_DEFAULT), 'utilisateur', 50, 't']);
    echo "<p class='ok'>✓ Jean créé</p>";

    // Vérifier
    echo "<h2>✅ Vérification</h2>";
    $count = $db->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
    echo "<pre>Utilisateurs créés: $count</pre>";

    $users = $db->query("SELECT pseudo, email, role, credits FROM utilisateur ORDER BY role DESC")->fetchAll();

    echo "<pre>";
    foreach ($users as $u) {
        printf("%-15s %-25s %-15s %d crédits\n", $u['pseudo'], $u['email'], $u['role'], $u['credits']);
    }
    echo "</pre>";

    echo "<h2 class='ok'>✅ Succès !</h2>";
    echo "<p>Mots de passe:</p>";
    echo "<pre>";
    echo "admin@ecoride.fr → Ec0R1de!\n";
    echo "demo@ecoride.fr  → demo123\n";
    echo "jean@example.com → Test123!\n";
    echo "</pre>";

    echo "<p><a href='/connexion.php' style='color:#0f0'>➡️ Se connecter</a></p>";

} catch (PDOException $e) {
    echo "<h2 class='error'>❌ Erreur PDO</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Erreur</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

?>
</body>
</html>
