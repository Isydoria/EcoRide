<?php
/**
 * Script pour corriger automatiquement les connexions DB dans les APIs
 */

echo "<h2>🔧 Correction automatique des connexions API</h2>";

$api_files = [
    'api/get-trajet-detail.php',
    'api/participer-trajet.php',
    'api/register-simple.php',
    'api/search-trajets.php',
    'api/test-db.php',
    'api/test-direct.php'
];

$old_connection = "    \$pdo = new PDO(
        'mysql:host=localhost;dbname=ecoride_db;charset=utf8mb4',
        'root',
        ''
    );";

$new_connection = "    // Connexion Railway adaptative
    \$host = \$_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? 'localhost';
    \$dbname = \$_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecoride_db';
    \$username = \$_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? 'root';
    \$password = \$_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';

    \$pdo = new PDO(
        \"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\",
        \$username,
        \$password
    );";

$fixed_count = 0;

foreach ($api_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);

        // Pattern plus flexible pour les connexions
        $pattern = '/\$pdo = new PDO\(\s*\'mysql:host=localhost;dbname=ecoride_db;charset=utf8mb4\',\s*\'root\',\s*\'\'\s*\);/';

        if (preg_match($pattern, $content)) {
            $new_content = preg_replace($pattern, $new_connection, $content);
            file_put_contents($file, $new_content);
            echo "<p>✅ Corrigé: $file</p>";
            $fixed_count++;
        } else {
            echo "<p>⚠️  Pattern non trouvé dans: $file</p>";
        }
    } else {
        echo "<p>❌ Fichier non trouvé: $file</p>";
    }
}

echo "<p><strong>🎉 $fixed_count fichiers corrigés !</strong></p>";
echo '<p><a href="/" style="color: #2ECC71;">← Tester l\'application</a></p>';
?>