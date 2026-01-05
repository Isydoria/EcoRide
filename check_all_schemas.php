<?php
/**
 * Script pour vérifier le schéma de toutes les tables importantes
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== VERIFICATION SCHEMAS POSTGRESQL ===\n\n";

require_once 'config/init.php';

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Driver: $driver\n\n";

    // Tables à vérifier
    $tables = ['voiture', 'covoiturage', 'participation', 'utilisateur', 'avis'];

    foreach ($tables as $table) {
        echo "=== TABLE: $table ===\n";

        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_name = :table_name
                ORDER BY ordinal_position
            ");
            $stmt->execute(['table_name' => $table]);
        } else {
            $stmt = $pdo->query("DESCRIBE $table");
        }

        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($columns)) {
            echo "❌ Table non trouvée\n\n";
            continue;
        }

        echo "Colonnes:\n";
        foreach ($columns as $col) {
            $colName = $driver === 'pgsql' ? $col['column_name'] : $col['Field'];
            $colType = $driver === 'pgsql' ? $col['data_type'] : $col['Type'];
            echo "  - $colName ($colType)\n";
        }

        // Vérifier si created_at existe
        $hasCreatedAt = false;
        $hasDateCreation = false;
        foreach ($columns as $col) {
            $colName = $driver === 'pgsql' ? $col['column_name'] : $col['Field'];
            if ($colName === 'created_at') $hasCreatedAt = true;
            if ($colName === 'date_creation') $hasDateCreation = true;
        }

        echo "\n";
        if ($hasCreatedAt) {
            echo "✅ Colonne created_at: OUI\n";
        } else {
            echo "❌ Colonne created_at: NON\n";
        }

        if ($hasDateCreation) {
            echo "✅ Colonne date_creation: OUI\n";
        } else {
            echo "❌ Colonne date_creation: NON\n";
        }

        echo "\n---\n\n";
    }

    echo "=== FIN VERIFICATION ===\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
?>
