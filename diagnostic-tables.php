<?php
/**
 * Script de diagnostic des tables PostgreSQL sur Render
 * URL: https://ecoride-om7c.onrender.com/diagnostic-tables.php
 */

require_once 'config/init.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Diagnostic des tables PostgreSQL</h1>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    pre { background: white; padding: 15px; border-radius: 8px; border: 1px solid #ddd; }
    .success { color: #27ae60; font-weight: bold; }
    .error { color: #e74c3c; font-weight: bold; }
    .warning { color: #f39c12; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 15px 0; background: white; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #3498db; color: white; }
    .action-btn { background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
    .action-btn:hover { background: #c0392b; }
</style>";

echo "<pre>";

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    echo "✅ Connexion établie\n";
    echo "📊 Driver: <span class='success'>$driver</span>\n\n";

    if ($driver !== 'pgsql') {
        die("<span class='error'>❌ Ce script est uniquement pour PostgreSQL (Render)</span>");
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 LISTE DES TABLES\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Liste toutes les tables
    $stmt = $pdo->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        ORDER BY table_name;
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "📁 $table\n";
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔍 ANALYSE TABLE: avis\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Vérifier si table avis existe
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = 'avis'
        );
    ");
    $avisExists = $stmt->fetchColumn();

    if ($avisExists) {
        echo "<span class='success'>✅ Table 'avis' existe</span>\n\n";

        // Afficher les colonnes
        $stmt = $pdo->query("
            SELECT
                column_name,
                data_type,
                character_maximum_length,
                is_nullable,
                column_default
            FROM information_schema.columns
            WHERE table_name = 'avis'
            ORDER BY ordinal_position;
        ");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Colonnes de la table 'avis':\n";
        echo "</pre>";
        echo "<table>";
        echo "<tr><th>Colonne</th><th>Type</th><th>Nullable</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            $type = $col['data_type'];
            if ($col['character_maximum_length']) {
                $type .= "({$col['character_maximum_length']})";
            }
            echo "<tr>";
            echo "<td><strong>{$col['column_name']}</strong></td>";
            echo "<td>{$type}</td>";
            echo "<td>{$col['is_nullable']}</td>";
            echo "<td>" . ($col['column_default'] ?: '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<pre>";

        // Vérifier les bonnes colonnes
        $columnNames = array_column($columns, 'column_name');
        $hasEvaluateur = in_array('evaluateur_id', $columnNames);
        $hasEvalue = in_array('evalue_id', $columnNames);
        $hasAuteur = in_array('auteur_id', $columnNames);
        $hasDestinataire = in_array('destinataire_id', $columnNames);

        echo "\nAnalyse des colonnes:\n";
        if ($hasEvaluateur && $hasEvalue) {
            echo "<span class='success'>✅ Structure PostgreSQL correcte (evaluateur_id, evalue_id)</span>\n";
        } elseif ($hasAuteur && $hasDestinataire) {
            echo "<span class='error'>❌ Structure MySQL détectée (auteur_id, destinataire_id)</span>\n";
            echo "<span class='warning'>⚠️ La table doit être recréée avec la structure PostgreSQL</span>\n";
        } else {
            echo "<span class='warning'>⚠️ Structure inconnue ou incomplète</span>\n";
        }

        // Compter les enregistrements
        $stmt = $pdo->query("SELECT COUNT(*) FROM avis");
        $count = $stmt->fetchColumn();
        echo "\n📊 Nombre d'avis: $count\n";

    } else {
        echo "<span class='error'>❌ Table 'avis' n'existe pas</span>\n";
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔍 ANALYSE TABLE: participation\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Vérifier table participation
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = 'participation'
        );
    ");
    $participationExists = $stmt->fetchColumn();

    if ($participationExists) {
        echo "<span class='success'>✅ Table 'participation' existe</span>\n\n";

        // Afficher les colonnes
        $stmt = $pdo->query("
            SELECT
                column_name,
                data_type,
                is_nullable
            FROM information_schema.columns
            WHERE table_name = 'participation'
            ORDER BY ordinal_position;
        ");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Colonnes: " . implode(", ", array_column($columns, 'column_name')) . "\n\n";

        // Vérifier le constraint statut_reservation
        $stmt = $pdo->query("
            SELECT check_clause
            FROM information_schema.check_constraints
            WHERE constraint_name LIKE '%statut%'
            AND table_name = 'participation';
        ");
        $constraint = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($constraint) {
            echo "Constraint statut_reservation:\n";
            echo "  " . $constraint['check_clause'] . "\n\n";

            if (strpos($constraint['check_clause'], 'terminee') !== false) {
                echo "<span class='success'>✅ Statut 'terminee' présent</span>\n";
            } else {
                echo "<span class='warning'>⚠️ Statut 'terminee' absent</span>\n";
            }
        }

        // Compter les participations
        $stmt = $pdo->query("SELECT COUNT(*) FROM participation");
        $count = $stmt->fetchColumn();
        echo "\n📊 Nombre de participations: $count\n";

    } else {
        echo "<span class='error'>❌ Table 'participation' n'existe pas</span>\n";
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "💡 RECOMMANDATIONS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $issues = [];

    if (!$avisExists) {
        $issues[] = "Créer la table 'avis' avec la structure PostgreSQL";
    } elseif ($hasAuteur && $hasDestinataire) {
        $issues[] = "Supprimer et recréer la table 'avis' avec les bonnes colonnes";
    }

    if (!$participationExists) {
        $issues[] = "Créer la table 'participation'";
    }

    if (empty($issues)) {
        echo "<span class='success'>✅ Toutes les tables sont correctes !</span>\n";
    } else {
        echo "<span class='warning'>Actions nécessaires:</span>\n";
        foreach ($issues as $i => $issue) {
            echo "  " . ($i + 1) . ". $issue\n";
        }
    }

} catch (Exception $e) {
    echo "<span class='error'>❌ ERREUR: " . $e->getMessage() . "</span>\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
}

echo "</pre>";

echo "<hr>";
echo "<h2>🔧 Actions de correction</h2>";
echo "<p>Si des problèmes ont été détectés, exécutez le script de correction :</p>";
echo "<form method='get' action='/fix-tables.php' style='display: inline;'>";
echo "<button type='submit' class='action-btn'>🔧 Corriger les tables</button>";
echo "</form>";

echo "<p style='margin-top: 30px;'>";
echo "<a href='/init-avis-table.php'>→ Script d'initialisation simple</a> | ";
echo "<a href='/user/dashboard.php?section=avis'>→ Tester le système d'avis</a> | ";
echo "<a href='/'>← Retour accueil</a>";
echo "</p>";
?>
