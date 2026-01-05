<?php
/**
 * Script de nettoyage avancé: Suppression des doublons avec timestamps similaires
 * Supprime les trajets avec même conducteur + même trajet + même heure (arrondie)
 */

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("❌ DATABASE_URL non définie - Ce script est pour Render uniquement");
}

try {
    $parts = parse_url($dbUrl);
    $dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) . ";dbname=" . ltrim($parts['path'], '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parts['user'], $parts['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Nettoyage Doublons Avancé</title>";
    echo "<style>
        body{font-family:Arial;padding:20px;background:#f5f5f5;}
        h1{color:#2c3e50;}h2{color:#3498db;margin-top:30px;}
        p{background:white;padding:10px;border-left:4px solid #3498db;margin:10px 0;}
        .success{border-left-color:#27ae60;}
        .warning{border-left-color:#f39c12;}
        .error{border-left-color:#e74c3c;}
        table{background:white;border-collapse:collapse;width:100%;margin:20px 0;}
        th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px;}
        th{background:#3498db;color:white;}
        ul{background:white;padding:20px;margin:10px 0;}
    </style></head><body>";
    echo "<h1>🧹 Nettoyage des Doublons (Avancé)</h1>";
    echo "<p class='warning'>⚠️ Ce script supprime les trajets en doublon basés sur : même conducteur + même trajet + même heure (arrondie)</p>";

    $pdo->beginTransaction();

    // ==================================================
    // ÉTAPE 1: STATISTIQUES AVANT NETTOYAGE
    // ==================================================
    echo "<h2>📊 Statistiques AVANT nettoyage</h2>";

    $statsBefore = [
        'covoiturages' => $pdo->query("SELECT COUNT(*) FROM covoiturage")->fetchColumn(),
        'participations' => $pdo->query("SELECT COUNT(*) FROM participation")->fetchColumn(),
        'avis' => $pdo->query("SELECT COUNT(*) FROM avis")->fetchColumn()
    ];

    echo "<ul>";
    echo "<li>Covoiturages : <strong>{$statsBefore['covoiturages']}</strong></li>";
    echo "<li>Participations : <strong>{$statsBefore['participations']}</strong></li>";
    echo "<li>Avis : <strong>{$statsBefore['avis']}</strong></li>";
    echo "</ul>";

    // ==================================================
    // ÉTAPE 2: IDENTIFIER LES DOUBLONS (avec heure arrondie)
    // ==================================================
    echo "<h2>🔍 Étape 1: Identification des doublons</h2>";

    $duplicates = $pdo->query("
        SELECT
            conducteur_id,
            ville_depart,
            ville_arrivee,
            DATE_TRUNC('hour', date_depart) as date_heure,
            COUNT(*) as nb_trajets,
            ARRAY_AGG(covoiturage_id ORDER BY covoiturage_id) as ids,
            ARRAY_AGG(TO_CHAR(date_depart, 'YYYY-MM-DD HH24:MI:SS')) as dates_exactes,
            ARRAY_AGG(ROUND(EXTRACT(EPOCH FROM (date_arrivee - date_depart))/3600, 1)) as durees
        FROM covoiturage
        GROUP BY conducteur_id, ville_depart, ville_arrivee, DATE_TRUNC('hour', date_depart)
        HAVING COUNT(*) > 1
        ORDER BY nb_trajets DESC, conducteur_id, ville_depart
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (count($duplicates) === 0) {
        echo "<p class='success'>✅ Aucun doublon détecté</p>";
        $pdo->rollBack();
        echo "</body></html>";
        exit;
    }

    echo "<p class='warning'>⚠️ <strong>" . count($duplicates) . " groupe(s) de doublons</strong> détectés</p>";

    echo "<table>";
    echo "<tr>
        <th>Conducteur ID</th>
        <th>Trajet</th>
        <th>Date+Heure</th>
        <th>Nb</th>
        <th>IDs</th>
        <th>Dates exactes</th>
        <th>Durées (h)</th>
    </tr>";

    $totalGroups = 0;
    $totalToDelete = 0;

    foreach ($duplicates as $dup) {
        $ids = trim($dup['ids'], '{}');
        $dates = str_replace('"', '', trim($dup['dates_exactes'], '{}'));
        $durees = trim($dup['durees'], '{}');

        echo "<tr>";
        echo "<td>{$dup['conducteur_id']}</td>";
        echo "<td>{$dup['ville_depart']} → {$dup['ville_arrivee']}</td>";
        echo "<td>{$dup['date_heure']}</td>";
        echo "<td><strong>{$dup['nb_trajets']}</strong></td>";
        echo "<td style='font-size:10px;'>{$ids}</td>";
        echo "<td style='font-size:10px;'>{$dates}</td>";
        echo "<td>{$durees}</td>";
        echo "</tr>";

        $totalGroups++;
        $totalToDelete += ($dup['nb_trajets'] - 1); // On garde 1, on supprime les autres
    }

    echo "</table>";
    echo "<p class='warning'>📌 Total à supprimer : <strong>{$totalToDelete} trajets</strong> (on garde le premier de chaque groupe)</p>";

    // ==================================================
    // ÉTAPE 3: SUPPRIMER LES DOUBLONS
    // ==================================================
    echo "<h2>🗑️ Étape 2: Suppression des doublons</h2>";

    $totalDeleted = 0;
    $groupNumber = 0;

    foreach ($duplicates as $dup) {
        $groupNumber++;

        // Extraire les IDs (format PostgreSQL array: {1,2,3})
        $idsString = trim($dup['ids'], '{}');
        $ids = explode(',', $idsString);

        // Garder le PREMIER ID (le plus ancien), supprimer les autres
        $keepId = array_shift($ids);

        if (!empty($ids)) {
            $idsToDelete = implode(',', $ids);

            echo "<p><strong>Groupe {$groupNumber}/{$totalGroups}:</strong> {$dup['ville_depart']} → {$dup['ville_arrivee']} ({$dup['date_heure']})</p>";
            echo "<p class='success'>  ✅ Conservation du trajet ID <strong>{$keepId}</strong></p>";
            echo "<p class='warning'>  🗑️ Suppression des trajets ID: <strong>{$idsToDelete}</strong></p>";

            // Supprimer les doublons (CASCADE supprimera automatiquement participations et avis)
            $stmt = $pdo->prepare("DELETE FROM covoiturage WHERE covoiturage_id = ANY(ARRAY[" . $idsToDelete . "])");
            $stmt->execute();

            $deletedCount = $stmt->rowCount();
            $totalDeleted += $deletedCount;

            echo "<p class='success'>  ✅ {$deletedCount} trajet(s) supprimé(s)</p>";
        }
    }

    echo "<p class='success'><strong>✅ TOTAL: {$totalDeleted} trajet(s) en doublon supprimé(s)</strong></p>";

    // ==================================================
    // ÉTAPE 4: STATISTIQUES APRÈS NETTOYAGE
    // ==================================================
    echo "<h2>📊 Statistiques APRÈS nettoyage</h2>";

    $statsAfter = [
        'covoiturages' => $pdo->query("SELECT COUNT(*) FROM covoiturage")->fetchColumn(),
        'participations' => $pdo->query("SELECT COUNT(*) FROM participation")->fetchColumn(),
        'avis' => $pdo->query("SELECT COUNT(*) FROM avis")->fetchColumn()
    ];

    echo "<table>";
    echo "<tr><th>Table</th><th>Avant</th><th>Après</th><th>Supprimés</th></tr>";
    echo "<tr>
        <td>Covoiturages</td>
        <td>{$statsBefore['covoiturages']}</td>
        <td>{$statsAfter['covoiturages']}</td>
        <td><strong>" . ($statsBefore['covoiturages'] - $statsAfter['covoiturages']) . "</strong></td>
    </tr>";
    echo "<tr>
        <td>Participations</td>
        <td>{$statsBefore['participations']}</td>
        <td>{$statsAfter['participations']}</td>
        <td><strong>" . ($statsBefore['participations'] - $statsAfter['participations']) . "</strong></td>
    </tr>";
    echo "<tr>
        <td>Avis</td>
        <td>{$statsBefore['avis']}</td>
        <td>{$statsAfter['avis']}</td>
        <td><strong>" . ($statsBefore['avis'] - $statsAfter['avis']) . "</strong></td>
    </tr>";
    echo "</table>";

    // ==================================================
    // ÉTAPE 5: VÉRIFICATION FINALE
    // ==================================================
    echo "<h2>🔍 Étape 3: Vérification finale</h2>";

    $remainingDuplicates = $pdo->query("
        SELECT COUNT(*) as nb
        FROM (
            SELECT
                conducteur_id,
                ville_depart,
                ville_arrivee,
                DATE_TRUNC('hour', date_depart) as date_heure,
                COUNT(*) as nb_trajets
            FROM covoiturage
            GROUP BY conducteur_id, ville_depart, ville_arrivee, DATE_TRUNC('hour', date_depart)
            HAVING COUNT(*) > 1
        ) AS duplicates
    ")->fetch();

    if ($remainingDuplicates['nb'] > 0) {
        echo "<p class='error'>❌ {$remainingDuplicates['nb']} groupe(s) de doublons restant(s) - Relancez le script</p>";
    } else {
        echo "<p class='success'>✅ Aucun doublon restant - Base de données propre !</p>";
    }

    // ==================================================
    // FINALISATION
    // ==================================================
    $pdo->commit();

    echo "<h2 class='success'>✅ Nettoyage terminé avec succès !</h2>";
    echo "<ul>";
    echo "<li>✅ {$totalGroups} groupe(s) de doublons traité(s)</li>";
    echo "<li>✅ {$totalDeleted} trajet(s) en doublon supprimé(s)</li>";
    echo "<li>✅ " . ($statsBefore['participations'] - $statsAfter['participations']) . " participation(s) associée(s) supprimée(s)</li>";
    echo "<li>✅ " . ($statsBefore['avis'] - $statsAfter['avis']) . " avis associé(s) supprimé(s)</li>";
    echo "<li>✅ Base de données nettoyée et cohérente</li>";
    echo "</ul>";

    echo "<p class='warning'><strong>⚠️ IMPORTANT :</strong></p>";
    echo "<ul>";
    echo "<li>Ne lancez <code>init-demo-data.php</code> qu'<strong>UNE SEULE FOIS</strong></li>";
    echo "<li>Si vous devez réinitialiser, supprimez d'abord TOUTES les données</li>";
    echo "</ul>";

    echo "<p><a href='/' style='display:inline-block;background:#3498db;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>← Retour à l'accueil</a></p>";
    echo "<p><a href='/diagnostic_duplicates.php' style='display:inline-block;background:#27ae60;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔍 Vérifier à nouveau</a></p>";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<h2 class='error'>❌ Erreur</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
