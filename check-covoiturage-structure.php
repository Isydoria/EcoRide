<?php
require_once 'config/init.php';
header('Content-Type: text/plain; charset=utf-8');

$pdo = db();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

echo "Driver: $driver\n\n";

if ($driver === 'pgsql') {
    $stmt = $pdo->query("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'covoiturage'
        ORDER BY ordinal_position;
    ");
    echo "Colonnes de la table covoiturage:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['column_name']} ({$row['data_type']})\n";
    }
} else {
    $stmt = $pdo->query("DESCRIBE covoiturage");
    echo "Colonnes de la table covoiturage:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
}
?>
