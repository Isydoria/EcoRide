<?php
/**
 * Script pour ajouter les colonnes de modération à la table avis (PostgreSQL)
 * Rend la structure PostgreSQL identique à MySQL pour la modération des avis
 *
 * À exécuter une seule fois sur : https://ecoride-om7c.onrender.com/add-moderation-columns.php
 */

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("❌ DATABASE_URL non définie - Ce script est uniquement pour PostgreSQL sur Render");
}

try {
    $parts = parse_url($dbUrl);
    $dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) . ";dbname=" . ltrim($parts['path'], '/') . ";sslmode=require";
    $db = new PDO($dsn, $parts['user'], $parts['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Ajout colonnes modération</title>";
    echo "<style>body{font-family:Arial;padding:20px;max-width:800px;margin:0 auto;}h1{color:#2c3e50;}pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;}.success{color:#27ae60;}.error{color:#e74c3c;}.warning{color:#f39c12;}</style></head><body>";

    echo "<h1>🔧 Ajout des colonnes de modération</h1>";
    echo "✅ Connexion PostgreSQL réussie<br><br>";

    // Vérifier d'abord si les colonnes existent déjà
    echo "<h2>🔍 Vérification de la structure actuelle</h2>";
    $stmt = $db->query("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'avis'
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll();

    $existingColumns = array_column($columns, 'column_name');

    echo "<pre>";
    echo "Colonnes actuelles de la table 'avis':\n";
    foreach ($columns as $col) {
        echo "  - {$col['column_name']} ({$col['data_type']})\n";
    }
    echo "</pre>";

    // Colonnes à ajouter
    $columnsToAdd = [
        'statut' => [
            'exists' => in_array('statut', $existingColumns),
            'sql' => "ALTER TABLE avis ADD COLUMN statut VARCHAR(20) DEFAULT 'en_attente'"
        ],
        'valide_par' => [
            'exists' => in_array('valide_par', $existingColumns),
            'sql' => "ALTER TABLE avis ADD COLUMN valide_par INTEGER REFERENCES utilisateur(utilisateur_id)"
        ],
        'date_validation' => [
            'exists' => in_array('date_validation', $existingColumns),
            'sql' => "ALTER TABLE avis ADD COLUMN date_validation TIMESTAMP"
        ]
    ];

    echo "<br><h2>➕ Ajout des colonnes manquantes</h2>";

    $addedCount = 0;
    $skippedCount = 0;

    foreach ($columnsToAdd as $colName => $colInfo) {
        if ($colInfo['exists']) {
            echo "<p class='warning'>⚠️ Colonne '{$colName}' existe déjà - ignorée</p>";
            $skippedCount++;
        } else {
            try {
                $db->exec($colInfo['sql']);
                echo "<p class='success'>✅ Colonne '{$colName}' ajoutée avec succès</p>";
                $addedCount++;
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Erreur lors de l'ajout de '{$colName}': {$e->getMessage()}</p>";
            }
        }
    }

    // Mise à jour des avis existants pour leur donner le statut "en_attente" par défaut
    if ($addedCount > 0 && in_array('statut', array_keys($columnsToAdd)) && !$columnsToAdd['statut']['exists']) {
        echo "<br><h2>🔄 Mise à jour des avis existants</h2>";
        try {
            $stmt = $db->exec("UPDATE avis SET statut = 'en_attente' WHERE statut IS NULL");
            echo "<p class='success'>✅ Tous les avis existants ont été mis à 'en_attente'</p>";
        } catch (PDOException $e) {
            echo "<p class='warning'>⚠️ Erreur lors de la mise à jour: {$e->getMessage()}</p>";
        }
    }

    // Vérification finale
    echo "<br><h2>✔️ Vérification finale</h2>";
    $stmt = $db->query("
        SELECT column_name, data_type, column_default
        FROM information_schema.columns
        WHERE table_name = 'avis'
        ORDER BY ordinal_position
    ");
    $finalColumns = $stmt->fetchAll();

    echo "<pre>";
    echo "Structure finale de la table 'avis':\n";
    foreach ($finalColumns as $col) {
        $default = $col['column_default'] ? " (défaut: {$col['column_default']})" : "";
        echo "  - {$col['column_name']} ({$col['data_type']}){$default}\n";
    }
    echo "</pre>";

    echo "<br><h2>📊 Résumé</h2>";
    echo "<ul>";
    echo "<li><strong>{$addedCount}</strong> colonnes ajoutées</li>";
    echo "<li><strong>{$skippedCount}</strong> colonnes déjà existantes</li>";
    echo "<li><strong>" . count($finalColumns) . "</strong> colonnes au total dans la table 'avis'</li>";
    echo "</ul>";

    echo "<br><h2>🎉 Opération terminée !</h2>";
    echo "<p>Le dashboard employé peut maintenant gérer la modération des avis.</p>";
    echo "<p><a href='employee/dashboard.php'>→ Accéder au dashboard employé</a></p>";

    echo "<br><h3>⚠️ Sécurité</h3>";
    echo "<p style='background:#fff3cd;padding:10px;border-radius:5px;border-left:4px solid #ffc107;'>";
    echo "Ce script doit être <strong>supprimé après exécution</strong> pour des raisons de sécurité.";
    echo "</p>";

} catch (PDOException $e) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erreur</title></head><body>";
    echo "<h2 style='color:#e74c3c;'>❌ Erreur PDO</h2>";
    echo "<p>Message : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Code : " . htmlspecialchars($e->getCode()) . "</p>";
}

echo "</body></html>";
