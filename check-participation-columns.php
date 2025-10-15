<?php
/**
 * check-participation-columns.php
 * Vérifier les colonnes exactes de la table participation
 */

header('Content-Type: text/html; charset=utf-8');

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("❌ DATABASE_URL non définie. Script pour PostgreSQL/Render uniquement.");
}

try {
    $parts = parse_url($dbUrl);
    $dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) . ";dbname=" . ltrim($parts['path'], '/') . ";sslmode=require";
    $db = new PDO($dsn, $parts['user'], $parts['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h1>📋 Structure de la table PARTICIPATION (PostgreSQL)</h1>";
    echo "<style>body { font-family: Arial; padding: 20px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #4CAF50; color: white; }</style>";

    // Obtenir toutes les colonnes
    $stmt = $db->query("
        SELECT column_name, data_type, character_maximum_length, column_default, is_nullable
        FROM information_schema.columns
        WHERE table_name = 'participation'
        ORDER BY ordinal_position
    ");

    $columns = $stmt->fetchAll();

    echo "<h2>Colonnes trouvées :</h2>";
    echo "<table>";
    echo "<tr><th>Nom</th><th>Type</th><th>Max Length</th><th>Défaut</th><th>Nullable</th></tr>";

    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['column_name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['data_type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['character_maximum_length'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($col['column_default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['is_nullable']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Afficher un exemple d'INSERT correct
    echo "<h2>✅ Exemple d'INSERT correct :</h2>";
    echo "<pre style='background: #f4f4f4; padding: 15px;'>";
    echo "INSERT INTO participation (\n";

    $colNames = array_column($columns, 'column_name');
    $requiredCols = array_filter($colNames, function($col) {
        return !in_array($col, ['participation_id', 'created_at', 'updated_at', 'date_creation']);
    });

    echo "    " . implode(",\n    ", $requiredCols) . "\n";
    echo ") VALUES (\n";
    echo "    ...\n";
    echo ")";
    echo "</pre>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Erreur</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
