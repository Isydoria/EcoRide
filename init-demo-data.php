<?php
/**
 * Script d'initialisation des données de démonstration
 * - Véhicules, trajets, participations, avis
 * - Dates jusqu'à fin février 2026
 * - Trajets multiples avec mêmes départs/arrivées pour tester les filtres
 * - Utilisateurs employés pour dashboard admin
 */

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("❌ DATABASE_URL non définie");
}

try {
    $parts = parse_url($dbUrl);
    $dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) . ";dbname=" . ltrim($parts['path'], '/') . ";sslmode=require";
    $db = new PDO($dsn, $parts['user'], $parts['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    echo "✅ Connexion réussie<br><br>";

    // 1. EMPLOYÉS
    echo "<h2>👥 Création des employés</h2>";
    $employees = [
        ['Sophie Martin', 'sophie.martin@ecoride.fr', 'Sophie2025!'],
        ['Lucas Dubois', 'lucas.dubois@ecoride.fr', 'Lucas2025!'],
        ['Emma Bernard', 'emma.bernard@ecoride.fr', 'Emma2025!']
    ];

    $stmt = $db->prepare("INSERT INTO utilisateur (pseudo, email, password, role, credits) VALUES (?, ?, ?, 'employe', 50) ON CONFLICT (email) DO NOTHING RETURNING utilisateur_id");

    foreach ($employees as $emp) {
        $stmt->execute([$emp[0], $emp[1], password_hash($emp[2], PASSWORD_DEFAULT)]);
        echo "✓ {$emp[0]}<br>";
    }

    // 2. VÉHICULES
    echo "<br><h2>🚗 Création des véhicules</h2>";
    $users = $db->query("SELECT utilisateur_id FROM utilisateur WHERE role != 'administrateur' ORDER BY utilisateur_id")->fetchAll();

    $vehicles = [
        ['Renault', 'Clio', 'AB-123-CD', 4, 'Essence'],
        ['Peugeot', '308', 'EF-456-GH', 4, 'Diesel'],
        ['Citroën', 'C3', 'IJ-789-KL', 4, 'Essence'],
        ['VW', 'Golf', 'MN-012-OP', 5, 'Diesel'],
        ['Toyota', 'Yaris', 'QR-345-ST', 4, 'Hybride'],
        ['Renault', 'Zoe', 'UV-678-WX', 4, 'Électrique'],
        ['Peugeot', '208', 'YZ-901-AB', 4, 'Essence'],
        ['Fiat', '500', 'CD-234-EF', 4, 'Essence']
    ];

    $stmt = $db->prepare("INSERT INTO voiture (utilisateur_id, marque, modele, immatriculation, places_disponibles, type_vehicule) VALUES (?, ?, ?, ?, ?, ?) RETURNING voiture_id");

    $vehicleIds = [];
    foreach ($vehicles as $i => $v) {
        $userId = $users[$i % count($users)]['utilisateur_id'];
        $stmt->execute([$userId, $v[0], $v[1], $v[2], $v[3], strtolower($v[4])]);
        $vehicleIds[$userId] = $stmt->fetch()['voiture_id'];
        echo "✓ {$v[0]} {$v[1]} ({$v[2]})<br>";
    }

    // 3. TRAJETS
    echo "<br><h2>🛣️ Création des trajets</h2>";

    $trajets = [
        // PARIS-LYON (même date 15/10 pour filtres)
        ['Paris', 'Lyon', '2025-10-15 08:00:00', '2025-10-15 12:30:00', 15, 3],
        ['Paris', 'Lyon', '2025-10-15 14:00:00', '2025-10-15 18:30:00', 20, 2],
        ['Paris', 'Lyon', '2025-10-15 19:00:00', '2025-10-15 23:30:00', 18, 4],
        ['Paris', 'Lyon', '2025-10-20 09:00:00', '2025-10-20 13:30:00', 15, 3],

        // MARSEILLE-NICE (même date 18/10)
        ['Marseille', 'Nice', '2025-10-18 10:00:00', '2025-10-18 12:30:00', 25, 2],
        ['Marseille', 'Nice', '2025-10-18 15:00:00', '2025-10-18 17:30:00', 30, 3],
        ['Marseille', 'Nice', '2025-10-22 11:00:00', '2025-10-22 13:30:00', 25, 2],

        // TOULOUSE-BORDEAUX (même date 25/10)
        ['Toulouse', 'Bordeaux', '2025-10-25 08:30:00', '2025-10-25 10:45:00', 20, 3],
        ['Toulouse', 'Bordeaux', '2025-10-25 16:00:00', '2025-10-25 18:15:00', 22, 2],

        // NOVEMBRE
        ['Lyon', 'Marseille', '2025-11-10 07:00:00', '2025-11-10 10:00:00', 18, 3],
        ['Bordeaux', 'Paris', '2025-11-12 06:00:00', '2025-11-12 11:30:00', 25, 2],
        ['Nice', 'Lyon', '2025-11-15 13:00:00', '2025-11-15 16:30:00', 30, 3],
        ['Strasbourg', 'Paris', '2025-11-18 08:00:00', '2025-11-18 12:00:00', 20, 4],
        ['Lille', 'Bruxelles', '2025-11-20 10:00:00', '2025-11-20 11:30:00', 15, 2],

        // DÉCEMBRE
        ['Paris', 'Strasbourg', '2025-12-01 07:30:00', '2025-12-01 11:30:00', 22, 3],
        ['Lyon', 'Grenoble', '2025-12-05 09:00:00', '2025-12-05 10:30:00', 12, 4],
        ['Marseille', 'Montpellier', '2025-12-10 14:00:00', '2025-12-10 16:00:00', 15, 2],
        ['Nantes', 'Rennes', '2025-12-15 08:00:00', '2025-12-15 09:30:00', 10, 3],
        ['Paris', 'Lille', '2025-12-20 10:00:00', '2025-12-20 11:30:00', 18, 2],

        // JANVIER 2026
        ['Lyon', 'Paris', '2026-01-05 07:00:00', '2026-01-05 11:30:00', 20, 3],
        ['Nice', 'Marseille', '2026-01-08 15:00:00', '2026-01-08 17:30:00', 25, 2],
        ['Bordeaux', 'Toulouse', '2026-01-10 09:00:00', '2026-01-10 11:15:00', 18, 4],
        ['Paris', 'Nantes', '2026-01-15 08:00:00', '2026-01-15 11:45:00', 22, 2],
        ['Strasbourg', 'Lyon', '2026-01-20 13:00:00', '2026-01-20 17:00:00', 25, 3],

        // FÉVRIER 2026
        ['Paris', 'Lyon', '2026-02-01 08:00:00', '2026-02-01 12:30:00', 15, 3],
        ['Marseille', 'Nice', '2026-02-05 10:00:00', '2026-02-05 12:30:00', 28, 2],
        ['Toulouse', 'Bordeaux', '2026-02-08 14:00:00', '2026-02-08 16:15:00', 20, 3],
        ['Lyon', 'Grenoble', '2026-02-12 09:00:00', '2026-02-12 10:30:00', 12, 4],
        ['Paris', 'Lille', '2026-02-15 07:30:00', '2026-02-15 09:00:00', 16, 2],
        ['Nice', 'Monaco', '2026-02-18 11:00:00', '2026-02-18 11:45:00', 10, 3],
        ['Bordeaux', 'Biarritz', '2026-02-20 10:00:00', '2026-02-20 12:00:00', 18, 2],
        ['Marseille', 'Aix-en-Provence', '2026-02-22 16:00:00', '2026-02-22 16:45:00', 8, 4],
        ['Lyon', 'Annecy', '2026-02-25 08:00:00', '2026-02-25 10:00:00', 15, 3],
        ['Paris', 'Reims', '2026-02-28 09:00:00', '2026-02-28 10:30:00', 12, 2]
    ];

    $stmt = $db->prepare("INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, prix_par_place, places_disponibles, statut) VALUES (?, ?, ?, ?, ?::date, ?::time, ?, ?, 'disponible') RETURNING covoiturage_id");

    $trajetIds = [];
    foreach ($trajets as $i => $t) {
        $userId = $users[$i % count($users)]['utilisateur_id'];
        $vehicleId = $vehicleIds[$userId] ?? array_values($vehicleIds)[0];

        // Extraire date et heure
        $dateTime = new DateTime($t[2]);
        $date = $dateTime->format('Y-m-d');
        $heure = $dateTime->format('H:i:s');

        $stmt->execute([$userId, $vehicleId, $t[0], $t[1], $date, $heure, $t[4], $t[5]]);
        $trajetIds[] = $stmt->fetch()['covoiturage_id'];
        echo "✓ {$t[0]} → {$t[1]} ({$date} {$heure})<br>";
    }

    // 4. PARTICIPATIONS
    echo "<br><h2>🎫 Participations</h2>";
    $stmt = $db->prepare("INSERT INTO participation (covoiturage_id, passager_id, places_reservees, statut_reservation) VALUES (?, ?, 1, ?)");

    $count = 0;
    foreach ($trajetIds as $i => $tid) {
        if ($i % 3 < 2) {
            $passengerId = $users[($i + 2) % count($users)]['utilisateur_id'];
            $statut = ($i % 4 == 0) ? 'en_attente' : 'confirmee';
            try {
                $stmt->execute([$tid, $passengerId, $statut]);
                $count++;
            } catch (Exception $e) {}
        }
    }
    echo "✓ {$count} participations créées<br>";

    // 5. AVIS
    echo "<br><h2>⭐ Avis</h2>";
    $comments = [
        "Excellent conducteur !",
        "Trajet agréable",
        "Très ponctuel",
        "Je recommande",
        "Super expérience",
        "Parfait !",
        "Très sympathique",
        "Conduite sûre"
    ];

    $stmt = $db->prepare("INSERT INTO avis (evaluateur_id, evalue_id, covoiturage_id, note, commentaire) VALUES (?, ?, ?, ?, ?)");

    $avisCount = 0;
    foreach ($trajetIds as $i => $tid) {
        if ($i % 4 < 2) {
            $evaluateurId = $users[($i + 1) % count($users)]['utilisateur_id'];
            $evalueId = $users[$i % count($users)]['utilisateur_id'];
            $note = rand(3, 5);
            try {
                $stmt->execute([$evaluateurId, $evalueId, $tid, $note, $comments[array_rand($comments)]]);
                $avisCount++;
            } catch (Exception $e) {}
        }
    }
    echo "✓ {$avisCount} avis créés<br>";

    // RÉSUMÉ
    echo "<br><h2>📊 Résumé</h2>";
    echo "<ul>";
    echo "<li>3 employés</li>";
    echo "<li>" . count($vehicleIds) . " véhicules</li>";
    echo "<li>" . count($trajetIds) . " trajets (jusqu'au 28 fév 2026)</li>";
    echo "<li>{$count} participations</li>";
    echo "<li>{$avisCount} avis</li>";
    echo "</ul>";

    echo "<h3>🎯 Tests filtres :</h3>";
    echo "<ul>";
    echo "<li>Paris→Lyon : 3 trajets le 15/10/2025</li>";
    echo "<li>Marseille→Nice : 2 trajets le 18/10/2025</li>";
    echo "<li>Toulouse→Bordeaux : 2 trajets le 25/10/2025</li>";
    echo "</ul>";

    echo "<br><h2>🎉 Terminé !</h2>";

} catch (PDOException $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
