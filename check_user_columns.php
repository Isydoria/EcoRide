<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== COLONNES TABLE UTILISATEUR ===\n\n";

require_once 'config/init.php';

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    echo "Driver: $driver\n\n";

    if ($driver === 'pgsql') {
        $stmt = $pdo->query("
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_name = 'utilisateur'
            ORDER BY ordinal_position
        ");

        echo "Colonnes:\n";
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            echo "  - {$col['column_name']} ({$col['data_type']})\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
?>
