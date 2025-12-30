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

    // 1. ADMINISTRATEUR
    echo "<h2>👨‍💼 Création de l'administrateur</h2>";
    $stmt = $db->prepare("INSERT INTO utilisateur (pseudo, email, password, role, credits) VALUES (?, ?, ?, 'administrateur', 100) ON CONFLICT (email) DO NOTHING RETURNING utilisateur_id");
    $stmt->execute(['Admin EcoRide', 'admin@ecoride.fr', password_hash('Ec0R1de!', PASSWORD_DEFAULT)]);
    echo "✓ Admin EcoRide (admin@ecoride.fr)<br>";

    // 2. EMPLOYÉS
    echo "<br><h2>👥 Création des employés</h2>";
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

    // 3. UTILISATEURS
    echo "<br><h2>🚗 Création des utilisateurs</h2>";
    $users_data = [
        ['Jean Dupont', 'jean.dupont@ecoride.fr', 'Jean2025!', 100],
        ['Marie Martin', 'marie.martin@ecoride.fr', 'Marie2025!', 75],
        ['Paul Durand', 'paul.durand@ecoride.fr', 'Paul2025!', 60],
        ['Alice Bernard', 'alice.bernard@ecoride.fr', 'Alice2025!', 80],
        ['Thomas Petit', 'thomas.petit@ecoride.fr', 'Thomas2025!', 90]
    ];

    $stmt = $db->prepare("INSERT INTO utilisateur (pseudo, email, password, role, credits) VALUES (?, ?, ?, 'utilisateur', ?) ON CONFLICT (email) DO NOTHING RETURNING utilisateur_id");

    foreach ($users_data as $user) {
        $stmt->execute([$user[0], $user[1], password_hash($user[2], PASSWORD_DEFAULT), $user[3]]);
        echo "✓ {$user[0]} ({$user[3]} crédits)<br>";
    }

    // 4. VÉHICULES - 1 par utilisateur minimum
    echo "<br><h2>🚗 Création des véhicules</h2>";
    $users = $db->query("SELECT utilisateur_id, pseudo FROM utilisateur WHERE role != 'administrateur' ORDER BY utilisateur_id")->fetchAll();

    $vehicles = [
        ['Renault', 'Zoe', 'AB-123-CD', 4, 'electrique', 'Blanche'],
        ['Tesla', 'Model 3', 'EF-456-GH', 5, 'electrique', 'Noire'],
        ['Nissan', 'Leaf', 'IJ-789-KL', 5, 'electrique', 'Bleue'],
        ['BMW', 'i3', 'MN-012-OP', 4, 'electrique', 'Grise'],
        ['Toyota', 'Prius', 'QR-345-ST', 5, 'hybride', 'Bleue'],
        ['Toyota', 'Yaris Hybrid', 'UV-678-WX', 4, 'hybride', 'Verte'],
        ['Honda', 'Jazz Hybrid', 'YZ-901-AB', 4, 'hybride', 'Blanche'],
        ['Dacia', 'Sandero GPL', 'CD-234-EF', 5, 'gpl', 'Rouge'],
        ['Renault', 'Clio GPL', 'GH-567-IJ', 4, 'gpl', 'Grise'],
        ['Peugeot', '208', 'KL-890-MN', 4, 'essence', 'Blanche'],
        ['VW', 'Golf', 'OP-123-QR', 5, 'diesel', 'Noire']
    ];

    $stmt = $db->prepare("INSERT INTO voiture (utilisateur_id, marque, modele, immatriculation, places_disponibles, type_vehicule, couleur) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING voiture_id");

    $vehicleIds = [];
    $vehicleCount = 0;

    // Assigner au moins 1 voiture par utilisateur
    foreach ($users as $i => $user) {
        if ($i < count($vehicles)) {
            $v = $vehicles[$i];
            $stmt->execute([$user['utilisateur_id'], $v[0], $v[1], $v[2], $v[3], strtolower($v[4]), $v[5]]);
            $vehicleIds[$user['utilisateur_id']] = $stmt->fetch()['voiture_id'];
            echo "✓ {$user['pseudo']}: {$v[0]} {$v[1]} {$v[4]} ({$v[2]})<br>";
            $vehicleCount++;
        }
    }

    // 5. TRAJETS
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
    $trajetsByUser = [];

    foreach ($trajets as $i => $t) {
        $userId = $users[$i % count($users)]['utilisateur_id'];
        $vehicleId = $vehicleIds[$userId] ?? array_values($vehicleIds)[0];

        // Extraire date et heure
        $dateTime = new DateTime($t[2]);
        $date = $dateTime->format('Y-m-d');
        $heure = $dateTime->format('H:i:s');

        $stmt->execute([$userId, $vehicleId, $t[0], $t[1], $date, $heure, $t[4], $t[5]]);
        $trajetId = $stmt->fetch()['covoiturage_id'];
        $trajetIds[] = $trajetId;

        // Suivre les trajets par utilisateur
        if (!isset($trajetsByUser[$userId])) {
            $trajetsByUser[$userId] = 0;
        }
        $trajetsByUser[$userId]++;

        $pseudo = $users[$i % count($users)]['pseudo'];
        echo "✓ {$pseudo}: {$t[0]} → {$t[1]} ({$date} {$heure})<br>";
    }

    // Afficher statistiques par utilisateur
    echo "<br><strong>Trajets par utilisateur:</strong><br>";
    foreach ($users as $user) {
        $count = $trajetsByUser[$user['utilisateur_id']] ?? 0;
        echo "• {$user['pseudo']}: $count trajet(s)<br>";
    }

    // 6. PARTICIPATIONS
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

    // 7. AVIS
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
    echo "<li>1 administrateur (admin@ecoride.fr / Ec0R1de!)</li>";
    echo "<li>3 employés avec véhicules et trajets</li>";
    echo "<li>5 utilisateurs avec véhicules et trajets</li>";
    echo "<li><strong>" . $vehicleCount . " véhicules</strong> (1 par utilisateur)</li>";
    echo "<li><strong>" . count($trajetIds) . " trajets</strong> (répartis équitablement jusqu'au 28 fév 2026)</li>";
    echo "<li><strong>{$count} participations</strong> (réservations croisées)</li>";
    echo "<li><strong>{$avisCount} avis</strong> (évaluations mutuelles)</li>";
    echo "</ul>";

    echo "<h3>🔐 Connexion :</h3>";
    echo "<ul>";
    echo "<li><strong>Admin :</strong> admin@ecoride.fr / Ec0R1de!</li>";
    echo "<li><strong>Employés :</strong> sophie.martin@ecoride.fr / Sophie2025! (etc.)</li>";
    echo "<li><strong>Utilisateurs :</strong> jean.dupont@ecoride.fr / Jean2025! (etc.)</li>";
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
