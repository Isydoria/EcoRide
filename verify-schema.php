<?php
/**
 * verify-schema.php
 * Script de diagnostic pour vérifier la structure des tables
 */

header('Content-Type: text/html; charset=utf-8');

require_once 'config/init.php';

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    echo "<h1>🔍 Diagnostic Structure Tables - " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</h1>";
    echo "<style>body { font-family: Arial; padding: 20px; } pre { background: #f4f4f4; padding: 10px; } table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #4CAF50; color: white; }</style>";

    // 1. Colonnes de la table utilisateur
    echo "<h2>👤 Structure de la table UTILISATEUR</h2>";

    if ($isPostgreSQL) {
        $stmt = $pdo->query("
            SELECT column_name, data_type, character_maximum_length, is_nullable, column_default
            FROM information_schema.columns
            WHERE table_name = 'utilisateur'
            ORDER BY ordinal_position
        ");
    } else {
        $stmt = $pdo->query("SHOW COLUMNS FROM utilisateur");
    }

    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($columns) > 0) {
        echo "<table>";
        if ($isPostgreSQL) {
            echo "<tr><th>Nom Colonne</th><th>Type</th><th>Longueur Max</th><th>Nullable</th><th>Défaut</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($col['column_name']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($col['data_type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['character_maximum_length'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($col['is_nullable']) . "</td>";
                echo "<td>" . htmlspecialchars($col['column_default'] ?? '-') . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($col['Extra'] ?? '') . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";

        // Vérifier les colonnes importantes
        echo "<h3>✅ Vérifications</h3>";
        echo "<ul>";

        $colNames = array_column($columns, $isPostgreSQL ? 'column_name' : 'Field');

        // Photo
        if (in_array('photo', $colNames)) {
            echo "<li>✅ Colonne <strong>photo</strong> présente (MySQL)</li>";
        } elseif (in_array('photo_profil', $colNames)) {
            echo "<li>✅ Colonne <strong>photo_profil</strong> présente (PostgreSQL)</li>";
        } else {
            echo "<li>❌ Aucune colonne photo trouvée !</li>";
        }

        // Crédits
        if (in_array('credit', $colNames)) {
            echo "<li>✅ Colonne <strong>credit</strong> présente (MySQL)</li>";
        } elseif (in_array('credits', $colNames)) {
            echo "<li>✅ Colonne <strong>credits</strong> présente (PostgreSQL)</li>";
        } else {
            echo "<li>❌ Aucune colonne crédits trouvée !</li>";
        }

        // Date inscription
        if (in_array('created_at', $colNames)) {
            echo "<li>✅ Colonne <strong>created_at</strong> présente (MySQL)</li>";
        } elseif (in_array('date_inscription', $colNames)) {
            echo "<li>✅ Colonne <strong>date_inscription</strong> présente (PostgreSQL)</li>";
        } else {
            echo "<li>❌ Aucune colonne date trouvée !</li>";
        }

        // is_active vs statut
        if (in_array('is_active', $colNames)) {
            echo "<li>✅ Colonne <strong>is_active</strong> présente (PostgreSQL)</li>";
        } elseif (in_array('statut', $colNames)) {
            echo "<li>✅ Colonne <strong>statut</strong> présente (MySQL)</li>";
        } else {
            echo "<li>❌ Aucune colonne statut/is_active trouvée !</li>";
        }

        echo "</ul>";

    } else {
        echo "<p style='color: red;'>❌ Table utilisateur introuvable !</p>";
    }

    // 2. Colonnes de la table covoiturage
    echo "<h2>🚗 Structure de la table COVOITURAGE</h2>";

    if ($isPostgreSQL) {
        $stmt = $pdo->query("
            SELECT column_name, data_type, character_maximum_length
            FROM information_schema.columns
            WHERE table_name = 'covoiturage'
            ORDER BY ordinal_position
        ");
    } else {
        $stmt = $pdo->query("SHOW COLUMNS FROM covoiturage");
    }

    $covColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($covColumns) > 0) {
        echo "<table>";
        echo "<tr><th>Colonne</th><th>Type</th></tr>";
        foreach ($covColumns as $col) {
            $colName = $isPostgreSQL ? $col['column_name'] : $col['Field'];
            $colType = $isPostgreSQL ? $col['data_type'] : $col['Type'];
            echo "<tr><td><strong>" . htmlspecialchars($colName) . "</strong></td><td>" . htmlspecialchars($colType) . "</td></tr>";
        }
        echo "</table>";

        echo "<h3>✅ Vérifications covoiturage</h3>";
        echo "<ul>";

        $covColNames = array_column($covColumns, $isPostgreSQL ? 'column_name' : 'Field');

        if (in_array('conducteur_id', $covColNames)) {
            echo "<li>✅ Colonne <strong>conducteur_id</strong> présente (MySQL)</li>";
        } elseif (in_array('id_conducteur', $covColNames)) {
            echo "<li>✅ Colonne <strong>id_conducteur</strong> présente (PostgreSQL)</li>";
        } else {
            echo "<li>❌ Aucune colonne conducteur trouvée !</li>";
        }

        if (in_array('voiture_id', $covColNames)) {
            echo "<li>✅ Colonne <strong>voiture_id</strong> présente (MySQL)</li>";
        } elseif (in_array('id_vehicule', $covColNames)) {
            echo "<li>✅ Colonne <strong>id_vehicule</strong> présente (PostgreSQL)</li>";
        } else {
            echo "<li>❌ Aucune colonne véhicule trouvée !</li>";
        }

        echo "</ul>";
    }

    // 3. Liste de toutes les tables
    echo "<h2>📋 Liste des tables</h2>";

    if ($isPostgreSQL) {
        $stmt = $pdo->query("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'public'
            ORDER BY table_name
        ");
    } else {
        $stmt = $pdo->query("SHOW TABLES");
    }

    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";

    echo "<hr>";
    echo "<h2>✅ Diagnostic terminé</h2>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
