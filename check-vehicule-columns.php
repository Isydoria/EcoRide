<?php
require_once 'config/init.php';
header('Content-Type: text/html; charset=utf-8');

$pdo = db();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

echo "<h1>📋 Structure table vehicule (PostgreSQL)</h1>";
echo "<p>Driver: $driver</p>";

$stmt = $pdo->query("
    SELECT column_name, data_type
    FROM information_schema.columns
    WHERE table_name = 'vehicule'
    ORDER BY ordinal_position
");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Colonne</th><th>Type</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['column_name']}</td><td>{$col['data_type']}</td></tr>";
}
echo "</table>";
?>
