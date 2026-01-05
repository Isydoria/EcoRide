<?php
/**
 * Script de diagnostic: Analyser les doublons potentiels
 * Affiche tous les trajets qui semblent similaires
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

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnostic Doublons</title>";
    echo "<style>
        body{font-family:Arial;padding:20px;background:#f5f5f5;}
        h1{color:#2c3e50;}h2{color:#3498db;margin-top:30px;}
        table{background:white;border-collapse:collapse;width:100%;margin:20px 0;font-size:12px;}
        th,td{border:1px solid #ddd;padding:8px;text-align:left;}
        th{background:#3498db;color:white;}
        .duplicate{background:#ffe6e6;}
        .ok{background:#e6ffe6;}
        p{background:white;padding:10px;border-left:4px solid #3498db;margin:10px 0;}
    </style></head><body>";
    echo "<h1>🔍 Diagnostic des Doublons</h1>";

    // ==================================================
    // ÉTAPE 1: TOUS LES TRAJETS
    // ==================================================
    echo "<h2>📋 Tous les trajets dans la base</h2>";

    $trajets = $pdo->query("
        SELECT
            c.covoiturage_id,
            c.conducteur_id,
            u.pseudo as conducteur,
            c.voiture_id,
            v.marque || ' ' || v.modele as vehicule,
            c.ville_depart,
            c.ville_arrivee,
            TO_CHAR(c.date_depart, 'YYYY-MM-DD HH24:MI') as date_depart,
            TO_CHAR(c.date_arrivee, 'YYYY-MM-DD HH24:MI') as date_arrivee,
            EXTRACT(EPOCH FROM (c.date_arrivee - c.date_depart))/3600 as duree_h,
            c.places_disponibles,
            c.prix_par_place,
            c.statut
        FROM covoiturage c
        INNER JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
        INNER JOIN voiture v ON c.voiture_id = v.voiture_id
        ORDER BY c.ville_depart, c.ville_arrivee, c.date_depart, c.covoiturage_id
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Total : " . count($trajets) . " trajets</strong></p>";

    echo "<table>";
    echo "<tr>
        <th>ID</th>
        <th>Conducteur</th>
        <th>Véhicule</th>
        <th>Trajet</th>
        <th>Date Départ</th>
        <th>Date Arrivée</th>
        <th>Durée</th>
        <th>Places</th>
        <th>Prix</th>
        <th>Statut</th>
    </tr>";

    $previous = null;
    foreach ($trajets as $t) {
        // Comparer avec le trajet précédent
        $isDuplicate = false;
        if ($previous) {
            if ($previous['conducteur_id'] == $t['conducteur_id'] &&
                $previous['ville_depart'] == $t['ville_depart'] &&
                $previous['ville_arrivee'] == $t['ville_arrivee'] &&
                substr($previous['date_depart'], 0, 13) == substr($t['date_depart'], 0, 13)) { // Même jour et même heure
                $isDuplicate = true;
            }
        }

        $rowClass = $isDuplicate ? 'duplicate' : 'ok';

        echo "<tr class='{$rowClass}'>";
        echo "<td>{$t['covoiturage_id']}</td>";
        echo "<td>{$t['conducteur']}</td>";
        echo "<td>{$t['vehicule']}</td>";
        echo "<td>{$t['ville_depart']} → {$t['ville_arrivee']}</td>";
        echo "<td>{$t['date_depart']}</td>";
        echo "<td>{$t['date_arrivee']}</td>";
        echo "<td>" . round($t['duree_h'], 1) . "h</td>";
        echo "<td>{$t['places_disponibles']}</td>";
        echo "<td>{$t['prix_par_place']}€</td>";
        echo "<td>{$t['statut']}</td>";
        echo "</tr>";

        $previous = $t;
    }
    echo "</table>";

    // ==================================================
    // ÉTAPE 2: GROUPES DE TRAJETS SIMILAIRES
    // ==================================================
    echo "<h2>🔍 Analyse des doublons potentiels</h2>";

    echo "<h3>Même conducteur + même trajet + même date + même heure</h3>";

    $duplicates = $pdo->query("
        SELECT
            conducteur_id,
            ville_depart,
            ville_arrivee,
            DATE_TRUNC('hour', date_depart) as date_heure,
            COUNT(*) as nb_trajets,
            ARRAY_AGG(covoiturage_id ORDER BY covoiturage_id) as ids,
            ARRAY_AGG(TO_CHAR(date_depart, 'HH24:MI:SS')) as heures_exactes,
            ARRAY_AGG(EXTRACT(EPOCH FROM (date_arrivee - date_depart))/3600) as durees
        FROM covoiturage
        GROUP BY conducteur_id, ville_depart, ville_arrivee, DATE_TRUNC('hour', date_depart)
        HAVING COUNT(*) > 1
        ORDER BY nb_trajets DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (count($duplicates) === 0) {
        echo "<p class='ok' style='border-left-color:#27ae60;'>✅ Aucun doublon détecté</p>";
    } else {
        echo "<p style='border-left-color:#f39c12;'>⚠️ " . count($duplicates) . " groupe(s) de doublons potentiels détectés</p>";

        echo "<table>";
        echo "<tr><th>Conducteur ID</th><th>Trajet</th><th>Date+Heure</th><th>Nb</th><th>IDs</th><th>Heures exactes</th><th>Durées</th></tr>";
        foreach ($duplicates as $dup) {
            $ids = trim($dup['ids'], '{}');
            $heures = trim($dup['heures_exactes'], '{}');
            $durees = trim($dup['durees'], '{}');

            echo "<tr class='duplicate'>";
            echo "<td>{$dup['conducteur_id']}</td>";
            echo "<td>{$dup['ville_depart']} → {$dup['ville_arrivee']}</td>";
            echo "<td>{$dup['date_heure']}</td>";
            echo "<td><strong>{$dup['nb_trajets']}</strong></td>";
            echo "<td>{$ids}</td>";
            echo "<td>{$heures}</td>";
            echo "<td>{$durees}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // ==================================================
    // ÉTAPE 3: DOUBLONS EXACTS
    // ==================================================
    echo "<h3>Doublons EXACTS (même timestamp au milliseconde près)</h3>";

    $exactDuplicates = $pdo->query("
        SELECT
            conducteur_id,
            voiture_id,
            ville_depart,
            ville_arrivee,
            date_depart,
            COUNT(*) as nb_doublons,
            ARRAY_AGG(covoiturage_id ORDER BY covoiturage_id) as ids
        FROM covoiturage
        GROUP BY conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart
        HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (count($exactDuplicates) === 0) {
        echo "<p class='ok' style='border-left-color:#27ae60;'>✅ Aucun doublon EXACT détecté</p>";
    } else {
        echo "<p style='border-left-color:#e74c3c;'>❌ " . count($exactDuplicates) . " groupe(s) de doublons EXACTS détectés</p>";

        echo "<table>";
        echo "<tr><th>Conducteur ID</th><th>Voiture ID</th><th>Trajet</th><th>Date</th><th>Nb</th><th>IDs à supprimer</th></tr>";
        foreach ($exactDuplicates as $dup) {
            $ids = trim($dup['ids'], '{}');

            echo "<tr class='duplicate'>";
            echo "<td>{$dup['conducteur_id']}</td>";
            echo "<td>{$dup['voiture_id']}</td>";
            echo "<td>{$dup['ville_depart']} → {$dup['ville_arrivee']}</td>";
            echo "<td>{$dup['date_depart']}</td>";
            echo "<td><strong>{$dup['nb_doublons']}</strong></td>";
            echo "<td>{$ids}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h2>💡 Recommandations</h2>";
    echo "<ul>";
    if (count($exactDuplicates) > 0) {
        echo "<li>✅ Exécutez <code>cleanup_duplicates.php</code> pour supprimer les doublons EXACTS</li>";
    }
    if (count($duplicates) > 0 && count($exactDuplicates) === 0) {
        echo "<li>⚠️ Les doublons détectés ont des timestamps différents (quelques secondes d'écart)</li>";
        echo "<li>💡 Il faudrait un script de nettoyage plus permissif basé sur l'heure arrondie</li>";
    }
    if (count($duplicates) === 0 && count($exactDuplicates) === 0) {
        echo "<li>✅ La base de données est propre, aucun doublon détecté</li>";
    }
    echo "</ul>";

    echo "<p><a href='/'>← Retour à l'accueil</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
