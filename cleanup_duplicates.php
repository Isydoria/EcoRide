<?php
/**
 * Script de nettoyage: Suppression des doublons dans la base PostgreSQL
 * À exécuter UNE SEULE FOIS après avoir lancé init-demo-data.php plusieurs fois
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

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Nettoyage Doublons</title>";
    echo "<style>
        body{font-family:Arial;padding:20px;background:#f5f5f5;}
        h1{color:#2c3e50;}h2{color:#3498db;margin-top:30px;}
        p{background:white;padding:10px;border-left:4px solid #3498db;margin:10px 0;}
        .success{border-left-color:#27ae60;}
        .warning{border-left-color:#f39c12;}
        .error{border-left-color:#e74c3c;}
        table{background:white;border-collapse:collapse;width:100%;margin:20px 0;}
        th,td{border:1px solid #ddd;padding:8px;text-align:left;}
        th{background:#3498db;color:white;}
    </style></head><body>";
    echo "<h1>🧹 Nettoyage des Doublons</h1>";

    $pdo->beginTransaction();

    // ==================================================
    // ÉTAPE 1: IDENTIFIER LES DOUBLONS DANS COVOITURAGE
    // ==================================================
    echo "<h2>🔍 Étape 1: Identification des doublons</h2>";

    $duplicates = $pdo->query("
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

    if (count($duplicates) === 0) {
        echo "<p class='success'>✅ Aucun doublon détecté dans la table covoiturage</p>";
    } else {
        echo "<p class='warning'>⚠️ " . count($duplicates) . " groupe(s) de doublons détectés</p>";

        echo "<table>";
        echo "<tr><th>Conducteur ID</th><th>Voiture ID</th><th>Trajet</th><th>Date</th><th>Doublons</th><th>IDs</th></tr>";
        foreach ($duplicates as $dup) {
            $ids = trim($dup['ids'], '{}');
            echo "<tr>";
            echo "<td>{$dup['conducteur_id']}</td>";
            echo "<td>{$dup['voiture_id']}</td>";
            echo "<td>{$dup['ville_depart']} → {$dup['ville_arrivee']}</td>";
            echo "<td>{$dup['date_depart']}</td>";
            echo "<td>{$dup['nb_doublons']}</td>";
            echo "<td>{$ids}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // ==================================================
    // ÉTAPE 2: SUPPRIMER LES DOUBLONS (garder le premier)
    // ==================================================
    echo "<h2>🗑️ Étape 2: Suppression des doublons</h2>";

    $totalDeleted = 0;

    foreach ($duplicates as $dup) {
        // Extraire les IDs (format PostgreSQL array: {1,2,3})
        $idsString = trim($dup['ids'], '{}');
        $ids = explode(',', $idsString);

        // Garder le premier ID, supprimer les autres
        $keepId = array_shift($ids);

        if (!empty($ids)) {
            $idsToDelete = implode(',', $ids);

            echo "<p>Trajet {$dup['ville_depart']} → {$dup['ville_arrivee']} :</p>";
            echo "<p class='success'>  ✅ Conservation du trajet ID {$keepId}</p>";
            echo "<p class='warning'>  🗑️ Suppression des trajets ID: {$idsToDelete}</p>";

            // Supprimer les doublons (CASCADE supprimera automatiquement participations et avis)
            $stmt = $pdo->prepare("DELETE FROM covoiturage WHERE covoiturage_id = ANY(ARRAY[" . $idsToDelete . "])");
            $stmt->execute();

            $deletedCount = $stmt->rowCount();
            $totalDeleted += $deletedCount;

            echo "<p class='success'>  ✅ {$deletedCount} trajet(s) supprimé(s)</p>";
        }
    }

    if ($totalDeleted > 0) {
        echo "<p class='success'><strong>✅ Total: {$totalDeleted} trajet(s) en doublon supprimé(s)</strong></p>";
    }

    // ==================================================
    // ÉTAPE 3: VÉRIFIER LES ORPHELINS
    // ==================================================
    echo "<h2>🔍 Étape 3: Vérification de l'intégrité</h2>";

    // Compter les participations orphelines (ne devrait pas en avoir grâce à CASCADE)
    $orphanParticipations = $pdo->query("
        SELECT COUNT(*) as nb
        FROM participation p
        LEFT JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
        WHERE c.covoiturage_id IS NULL
    ")->fetch();

    if ($orphanParticipations['nb'] > 0) {
        echo "<p class='error'>❌ {$orphanParticipations['nb']} participation(s) orpheline(s) détectée(s)</p>";
    } else {
        echo "<p class='success'>✅ Aucune participation orpheline</p>";
    }

    // Compter les avis orphelins
    $orphanAvis = $pdo->query("
        SELECT COUNT(*) as nb
        FROM avis a
        LEFT JOIN covoiturage c ON a.covoiturage_id = c.covoiturage_id
        WHERE c.covoiturage_id IS NULL
    ")->fetch();

    if ($orphanAvis['nb'] > 0) {
        echo "<p class='error'>❌ {$orphanAvis['nb']} avis orphelin(s) détecté(s)</p>";
    } else {
        echo "<p class='success'>✅ Aucun avis orphelin</p>";
    }

    // ==================================================
    // ÉTAPE 4: STATISTIQUES FINALES
    // ==================================================
    echo "<h2>📊 Étape 4: Statistiques finales</h2>";

    $stats = [
        'covoiturages' => $pdo->query("SELECT COUNT(*) FROM covoiturage")->fetchColumn(),
        'participations' => $pdo->query("SELECT COUNT(*) FROM participation")->fetchColumn(),
        'avis' => $pdo->query("SELECT COUNT(*) FROM avis")->fetchColumn(),
        'utilisateurs' => $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn(),
        'voitures' => $pdo->query("SELECT COUNT(*) FROM voiture")->fetchColumn()
    ];

    echo "<table>";
    echo "<tr><th>Table</th><th>Nombre d'enregistrements</th></tr>";
    foreach ($stats as $table => $count) {
        echo "<tr><td>" . ucfirst($table) . "</td><td>{$count}</td></tr>";
    }
    echo "</table>";

    // ==================================================
    // FINALISATION
    // ==================================================
    $pdo->commit();

    echo "<h2 class='success'>✅ Nettoyage terminé avec succès !</h2>";
    echo "<p><strong>Résumé :</strong></p>";
    echo "<ul>";
    echo "<li>✅ " . count($duplicates) . " groupe(s) de doublons traité(s)</li>";
    echo "<li>✅ {$totalDeleted} trajet(s) en doublon supprimé(s)</li>";
    echo "<li>✅ Base de données nettoyée et cohérente</li>";
    echo "</ul>";
    echo "<p class='warning'><strong>⚠️ IMPORTANT :</strong> Ne lancez <code>init-demo-data.php</code> qu'UNE SEULE FOIS pour éviter les doublons !</p>";
    echo "<p><a href='/'>← Retour à l'accueil</a></p>";

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
