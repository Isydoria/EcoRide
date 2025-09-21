<?php
/**
 * Script de debug Railway
 */

echo "<h2>🔍 Debug Railway Environment</h2>";

echo "<h3>Variables $_ENV:</h3>";
echo "<pre>";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'MYSQL') !== false) {
        echo "$key = " . (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) . "\n";
    }
}
echo "</pre>";

echo "<h3>Variables getenv():</h3>";
echo "<pre>";
$mysql_vars = ['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD', 'MYSQL_PORT'];
foreach ($mysql_vars as $var) {
    $value = getenv($var);
    echo "$var = " . ($value ? (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) : 'NULL') . "\n";
}
echo "</pre>";

echo "<h3>Variables superglobales:</h3>";
echo "<pre>";
echo "Railway variables count: " . count(array_filter(array_keys($_ENV), function($k) { return strpos($k, 'RAILWAY') !== false; })) . "\n";
echo "MySQL variables count: " . count(array_filter(array_keys($_ENV), function($k) { return strpos($k, 'MYSQL') !== false; })) . "\n";
echo "</pre>";

// Test connexion directe
$host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? null;
$dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? null;
$username = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? null;
$password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? null;

if ($host && $dbname && $username && $password) {
    echo "<h3>🔌 Test de connexion MySQL:</h3>";
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "<p style='color: green;'>✅ Connexion MySQL réussie !</p>";

        // Test simple
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "<p>Test query result: " . $result['test'] . "</p>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur connexion: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Variables manquantes pour test connexion</p>";
}
?>