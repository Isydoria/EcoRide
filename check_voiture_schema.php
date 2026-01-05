<?php
/**
 * Script pour vérifier le schéma de la table voiture sur PostgreSQL
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== VERIFICATION SCHEMA TABLE VOITURE ===\n\n";

require_once 'config/init.php';

try {
    $pdo = db();

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Driver: $driver\n\n";

    // Récupérer les colonnes de la table voiture
    if ($driver === 'pgsql') {
        $stmt = $pdo->query("
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_name = 'voiture'
            ORDER BY ordinal_position
        ");

        echo "=== COLONNES DE LA TABLE VOITURE (PostgreSQL) ===\n";
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($columns)) {
            echo "❌ Aucune colonne trouvée (la table existe-t-elle ?)\n";
        } else {
            echo "✅ " . count($columns) . " colonnes trouvées:\n\n";
            foreach ($columns as $col) {
                echo "  - {$col['column_name']} ({$col['data_type']}) ";
                echo $col['is_nullable'] === 'YES' ? "NULL" : "NOT NULL";
                echo "\n";
            }
        }
    } else {
        // MySQL
        $stmt = $pdo->query("DESCRIBE voiture");
        echo "=== COLONNES DE LA TABLE VOITURE (MySQL) ===\n";
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "✅ " . count($columns) . " colonnes trouvées:\n\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
        }
    }

    echo "\n=== TEST REQUETE SANS created_at ===\n";
    $stmt = $pdo->query("SELECT * FROM voiture LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sample) {
        echo "✅ Exemple de données:\n";
        foreach ($sample as $key => $value) {
            echo "  - $key: $value\n";
        }
    } else {
        echo "⚠️ Aucune donnée dans la table\n";
    }

    echo "\n=== FIN VERIFICATION ===\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
