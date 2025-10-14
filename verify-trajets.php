<?php
/**
 * verify-trajets.php
 * Script de diagnostic pour vérifier la présence des trajets dans la base de données
 */

header('Content-Type: text/html; charset=utf-8');

require_once 'config/init.php';

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    echo "<h1>🔍 Diagnostic Trajets - " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</h1>";
    echo "<style>body { font-family: Arial; padding: 20px; } pre { background: #f4f4f4; padding: 10px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #4CAF50; color: white; }</style>";

    // 1. Compter tous les trajets
    echo "<h2>1️⃣ Nombre total de trajets</h2>";
    $count = $pdo->query("SELECT COUNT(*) FROM covoiturage")->fetchColumn();
    echo "<p><strong>Total trajets :</strong> $count</p>";

    // 2. Trajets Paris → Lyon
    echo "<h2>2️⃣ Trajets Paris → Lyon</h2>";
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM covoiturage
        WHERE LOWER(ville_depart) LIKE '%paris%'
        AND LOWER(ville_arrivee) LIKE '%lyon%'
    ");
    $parisLyon = $stmt->fetchColumn();
    echo "<p><strong>Paris → Lyon :</strong> $parisLyon trajets</p>";

    // 3. Trajets pour le 15/10/2025
    echo "<h2>3️⃣ Trajets le 15/10/2025</h2>";
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM covoiturage
        WHERE DATE(date_depart) = '2025-10-15'
    ");
    $date1510 = $stmt->fetchColumn();
    echo "<p><strong>Trajets le 15/10/2025 :</strong> $date1510</p>";

    // 4. Trajets Paris → Lyon le 15/10/2025
    echo "<h2>4️⃣ Trajets Paris → Lyon le 15/10/2025</h2>";
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM covoiturage
        WHERE LOWER(ville_depart) LIKE '%paris%'
        AND LOWER(ville_arrivee) LIKE '%lyon%'
        AND DATE(date_depart) = '2025-10-15'
    ");
    $parisLyonDate = $stmt->fetchColumn();
    echo "<p><strong>Paris → Lyon le 15/10/2025 :</strong> $parisLyonDate</p>";

    // 5. Vérifier les conditions du WHERE de l'API
    echo "<h2>5️⃣ Test des conditions WHERE de l'API</h2>";

    if ($isPostgreSQL) {
        $stmt = $pdo->query("
            SELECT
                t.covoiturage_id,
                t.ville_depart,
                t.ville_arrivee,
                DATE(t.date_depart) as date_depart,
                t.places_disponibles,
                t.statut,
                u.is_active,
                u.pseudo
            FROM covoiturage t
            INNER JOIN utilisateur u ON t.id_conducteur = u.utilisateur_id
            WHERE LOWER(t.ville_depart) LIKE LOWER('%Paris%')
            AND LOWER(t.ville_arrivee) LIKE LOWER('%Lyon%')
            AND DATE(t.date_depart) = '2025-10-15'
            LIMIT 10
        ");
    } else {
        $stmt = $pdo->query("
            SELECT
                t.covoiturage_id,
                t.ville_depart,
                t.ville_arrivee,
                DATE(t.date_depart) as date_depart,
                t.places_disponibles,
                t.statut,
                u.statut as user_statut,
                u.pseudo
            FROM covoiturage t
            INNER JOIN utilisateur u ON t.conducteur_id = u.utilisateur_id
            WHERE LOWER(t.ville_depart) LIKE LOWER('%Paris%')
            AND LOWER(t.ville_arrivee) LIKE LOWER('%Lyon%')
            AND DATE(t.date_depart) = '2025-10-15'
            LIMIT 10
        ");
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Résultats avec JOIN utilisateur :</strong> " . count($results) . "</p>";

    if (count($results) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Départ</th><th>Arrivée</th><th>Date</th><th>Places</th><th>Statut</th><th>" . ($isPostgreSQL ? 'is_active' : 'user_statut') . "</th><th>Conducteur</th></tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['covoiturage_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ville_depart']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ville_arrivee']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date_depart']) . "</td>";
            echo "<td>" . htmlspecialchars($row['places_disponibles']) . "</td>";
            echo "<td>" . htmlspecialchars($row['statut']) . "</td>";
            echo "<td>" . htmlspecialchars($row[$isPostgreSQL ? 'is_active' : 'user_statut']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pseudo']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 6. Vérifier avec TOUTES les conditions de l'API
    echo "<h2>6️⃣ Test COMPLET avec toutes les conditions de l'API</h2>";

    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT
                t.covoiturage_id,
                t.ville_depart,
                t.ville_arrivee,
                DATE(t.date_depart) as date_depart,
                t.places_disponibles,
                t.statut,
                u.is_active,
                u.pseudo,
                v.marque,
                v.modele
            FROM covoiturage t
            INNER JOIN utilisateur u ON t.id_conducteur = u.utilisateur_id
            INNER JOIN vehicule v ON t.id_vehicule = v.vehicule_id
            WHERE LOWER(t.ville_depart) LIKE LOWER(:ville_depart)
            AND LOWER(t.ville_arrivee) LIKE LOWER(:ville_arrivee)
            AND DATE(t.date_depart) = :date_depart
            AND t.places_disponibles > 0
            AND t.statut = 'planifie'
            AND u.is_active = true
        ");

        $stmt->execute([
            'ville_depart' => '%Paris%',
            'ville_arrivee' => '%Lyon%',
            'date_depart' => '2025-10-15'
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT
                t.covoiturage_id,
                t.ville_depart,
                t.ville_arrivee,
                DATE(t.date_depart) as date_depart,
                t.places_disponibles,
                t.statut,
                u.statut as user_statut,
                u.pseudo,
                v.marque,
                v.modele
            FROM covoiturage t
            INNER JOIN utilisateur u ON t.conducteur_id = u.utilisateur_id
            INNER JOIN voiture v ON t.voiture_id = v.voiture_id
            WHERE LOWER(t.ville_depart) LIKE LOWER(:ville_depart)
            AND LOWER(t.ville_arrivee) LIKE LOWER(:ville_arrivee)
            AND DATE(t.date_depart) = :date_depart
            AND t.places_disponibles > 0
            AND t.statut = 'planifie'
            AND u.statut = 'actif'
        ");

        $stmt->execute([
            'ville_depart' => '%Paris%',
            'ville_arrivee' => '%Lyon%',
            'date_depart' => '2025-10-15'
        ]);
    }

    $fullResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Résultats avec TOUTES les conditions :</strong> " . count($fullResults) . "</p>";

    if (count($fullResults) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Départ</th><th>Arrivée</th><th>Date</th><th>Places</th><th>Statut</th><th>" . ($isPostgreSQL ? 'is_active' : 'user_statut') . "</th><th>Conducteur</th><th>Véhicule</th></tr>";
        foreach ($fullResults as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['covoiturage_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ville_depart']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ville_arrivee']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date_depart']) . "</td>";
            echo "<td>" . htmlspecialchars($row['places_disponibles']) . "</td>";
            echo "<td>" . htmlspecialchars($row['statut']) . "</td>";
            echo "<td>" . htmlspecialchars($row[$isPostgreSQL ? 'is_active' : 'user_statut']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pseudo']) . "</td>";
            echo "<td>" . htmlspecialchars($row['marque']) . " " . htmlspecialchars($row['modele']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 7. Afficher quelques trajets bruts
    echo "<h2>7️⃣ Aperçu des 10 premiers trajets (brut)</h2>";

    if ($isPostgreSQL) {
        $stmt = $pdo->query("
            SELECT
                covoiturage_id,
                ville_depart,
                ville_arrivee,
                date_depart,
                statut,
                places_disponibles,
                id_conducteur
            FROM covoiturage
            ORDER BY date_depart ASC
            LIMIT 10
        ");
    } else {
        $stmt = $pdo->query("
            SELECT
                covoiturage_id,
                ville_depart,
                ville_arrivee,
                date_depart,
                statut,
                places_disponibles,
                conducteur_id
            FROM covoiturage
            ORDER BY date_depart ASC
            LIMIT 10
        ");
    }

    $rawTrajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rawTrajets) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Départ</th><th>Arrivée</th><th>Date complète</th><th>Statut</th><th>Places</th><th>Conducteur ID</th></tr>";
        foreach ($rawTrajets as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['covoiturage_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ville_depart']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ville_arrivee']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date_depart']) . "</td>";
            echo "<td>" . htmlspecialchars($row['statut']) . "</td>";
            echo "<td>" . htmlspecialchars($row['places_disponibles']) . "</td>";
            echo "<td>" . htmlspecialchars($row[$isPostgreSQL ? 'id_conducteur' : 'conducteur_id']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><strong style='color: red;'>⚠️ AUCUN TRAJET DANS LA BASE DE DONNÉES !</strong></p>";
        echo "<p>Vous devez exécuter le script d'initialisation :</p>";
        echo "<ul>";
        if ($isPostgreSQL) {
            echo "<li><a href='/init-demo-data.php'>init-demo-data.php</a> (PostgreSQL/Render)</li>";
        } else {
            echo "<li><a href='/ecoride/init-demo-data-local.php'>init-demo-data-local.php</a> (MySQL/Local)</li>";
        }
        echo "</ul>";
    }

    echo "<hr>";
    echo "<h2>✅ Diagnostic terminé</h2>";

    if ($count == 0) {
        echo "<p style='color: red; font-weight: bold;'>❌ La base de données est VIDE. Exécutez le script d'initialisation correspondant à votre environnement.</p>";
    } elseif (count($fullResults) == 0) {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ Des trajets existent mais AUCUN ne correspond aux conditions de recherche. Vérifiez les conditions WHERE de l'API.</p>";
    } else {
        echo "<p style='color: green; font-weight: bold;'>✅ Des trajets correspondent à la recherche ! Le problème est ailleurs (peut-être dans le frontend ou les paramètres envoyés).</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
