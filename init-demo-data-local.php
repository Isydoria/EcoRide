<?php
/**
 * Script d'initialisation des données de démonstration - VERSION MYSQL LOCAL
 * Compatible avec WampServer/XAMPP
 * Mêmes données que init-demo-data.php (PostgreSQL Render)
 */

require_once 'config/init.php';

try {
    $pdo = db();

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Init MySQL Local</title>";
    echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}h1,h2{color:#2ECC71;}ul{background:white;padding:20px;border-radius:8px;}</style>";
    echo "</head><body>";
    echo "<h1>🚀 Initialisation MySQL Local - EcoRide</h1>";
    echo "✅ Connexion réussie<br><br>";

    // 1. ADMINISTRATEUR
    echo "<h2>👨‍💼 Création de l'administrateur</h2>";
    $stmt = $pdo->prepare("INSERT INTO utilisateur (pseudo, email, password, role, credit, statut) VALUES (?, ?, ?, 'administrateur', 100, 'actif') ON DUPLICATE KEY UPDATE pseudo=pseudo");
    $stmt->execute(['Admin EcoRide', 'admin@ecoride.fr', password_hash('Ec0R1de!', PASSWORD_DEFAULT)]);
    echo "✓ Admin EcoRide (admin@ecoride.fr)<br>";

    // 2. EMPLOYÉS
    echo "<br><h2>👥 Création des employés</h2>";
    $employees = [
        ['Sophie Martin', 'sophie.martin@ecoride.fr', 'Sophie2025!'],
        ['Lucas Dubois', 'lucas.dubois@ecoride.fr', 'Lucas2025!'],
        ['Emma Bernard', 'emma.bernard@ecoride.fr', 'Emma2025!']
    ];

    $stmt = $pdo->prepare("INSERT INTO utilisateur (pseudo, email, password, role, credit, statut) VALUES (?, ?, ?, 'employe', 50, 'actif') ON DUPLICATE KEY UPDATE pseudo=pseudo");
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

    $stmt = $pdo->prepare("INSERT INTO utilisateur (pseudo, email, password, role, credit, statut) VALUES (?, ?, ?, 'utilisateur', ?, 'actif') ON DUPLICATE KEY UPDATE pseudo=pseudo");
    $users = [];
    foreach ($users_data as $user) {
        $stmt->execute([$user[0], $user[1], password_hash($user[2], PASSWORD_DEFAULT), $user[3]]);
        echo "✓ {$user[0]} ({$user[3]} crédits)<br>";
    }

    // Récupérer tous les utilisateurs non-admin pour les trajets
    $users = $pdo->query("SELECT utilisateur_id, pseudo FROM utilisateur WHERE role != 'administrateur' ORDER BY utilisateur_id")->fetchAll();

    if (count($users) == 0) {
        echo "⚠️ Aucun utilisateur trouvé. Créez d'abord des utilisateurs.<br>";
        exit;
    }

    // 4. VÉHICULES - 1 par utilisateur minimum avec accent écologique
    echo "<br><h2>🚗 Création des véhicules</h2>";

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

    $stmt = $pdo->prepare("INSERT INTO voiture (utilisateur_id, marque, modele, immatriculation, places, energie, couleur) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE marque=marque");

    $vehicleIds = [];
    $vehicleCount = 0;

    // Assigner au moins 1 voiture par utilisateur
    foreach ($users as $i => $user) {
        if ($i < count($vehicles)) {
            $v = $vehicles[$i];
            try {
                $stmt->execute([$user['utilisateur_id'], $v[0], $v[1], $v[2], $v[3], $v[4], $v[5]]);
                $vehicleId = $pdo->lastInsertId();
                if ($vehicleId) {
                    $vehicleIds[$user['utilisateur_id']] = $vehicleId;
                }
                echo "✓ {$user['pseudo']}: {$v[0]} {$v[1]} {$v[4]} ({$v[2]})<br>";
                $vehicleCount++;
            } catch (Exception $e) {
                echo "⚠️ {$v[0]} {$v[1]} déjà existant<br>";
            }
        }
    }

    // Si pas assez de véhicules insérés, récupérer les existants
    if (count($vehicleIds) == 0) {
        $existing = $pdo->query("SELECT voiture_id, utilisateur_id FROM voiture LIMIT 20")->fetchAll();
        foreach ($existing as $veh) {
            $vehicleIds[$veh['utilisateur_id']] = $veh['voiture_id'];
        }
    }

    // 5. TRAJETS
    echo "<br><h2>🛣️ Création des trajets</h2>";

    $trajets = [
        // JANVIER 2026 - PARIS-LYON (même date 15/01 pour filtres)
        ['Paris', 'Lyon', '2026-01-15 08:00:00', '2026-01-15 12:30:00', 15, 3],
        ['Paris', 'Lyon', '2026-01-15 14:00:00', '2026-01-15 18:30:00', 20, 2],
        ['Paris', 'Lyon', '2026-01-15 19:00:00', '2026-01-15 23:30:00', 18, 4],
        ['Paris', 'Lyon', '2026-01-20 09:00:00', '2026-01-20 13:30:00', 15, 3],

        // MARSEILLE-NICE (même date 18/01)
        ['Marseille', 'Nice', '2026-01-18 10:00:00', '2026-01-18 12:30:00', 25, 2],
        ['Marseille', 'Nice', '2026-01-18 15:00:00', '2026-01-18 17:30:00', 30, 3],
        ['Marseille', 'Nice', '2026-01-22 11:00:00', '2026-01-22 13:30:00', 25, 2],

        // TOULOUSE-BORDEAUX (même date 25/01)
        ['Toulouse', 'Bordeaux', '2026-01-25 08:30:00', '2026-01-25 10:45:00', 20, 3],
        ['Toulouse', 'Bordeaux', '2026-01-25 16:00:00', '2026-01-25 18:15:00', 22, 2],

        // AUTRES TRAJETS JANVIER
        ['Lyon', 'Marseille', '2026-01-10 07:00:00', '2026-01-10 10:00:00', 18, 3],
        ['Bordeaux', 'Paris', '2026-01-12 06:00:00', '2026-01-12 11:30:00', 25, 2],
        ['Nice', 'Lyon', '2026-01-14 13:00:00', '2026-01-14 16:30:00', 30, 3],
        ['Strasbourg', 'Paris', '2026-01-16 08:00:00', '2026-01-16 12:00:00', 20, 4],
        ['Lille', 'Bruxelles', '2026-01-19 10:00:00', '2026-01-19 11:30:00', 15, 2],

        // SUITE JANVIER
        ['Paris', 'Strasbourg', '2026-01-23 07:30:00', '2026-01-23 11:30:00', 22, 3],
        ['Lyon', 'Grenoble', '2026-01-26 09:00:00', '2026-01-26 10:30:00', 12, 4],
        ['Marseille', 'Montpellier', '2026-01-28 14:00:00', '2026-01-28 16:00:00', 15, 2],
        ['Nantes', 'Rennes', '2026-01-29 08:00:00', '2026-01-29 09:30:00', 10, 3],
        ['Paris', 'Lille', '2026-01-31 10:00:00', '2026-01-31 11:30:00', 18, 2],
        ['Lyon', 'Paris', '2026-01-05 07:00:00', '2026-01-05 11:30:00', 20, 3],
        ['Nice', 'Marseille', '2026-01-08 15:00:00', '2026-01-08 17:30:00', 25, 2],
        ['Bordeaux', 'Toulouse', '2026-01-06 09:00:00', '2026-01-06 11:15:00', 18, 4],
        ['Strasbourg', 'Lyon', '2026-01-27 13:00:00', '2026-01-27 17:00:00', 25, 3],

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

    $stmt = $pdo->prepare("INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, date_arrivee, prix_par_place, places_disponibles, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'planifie') ON DUPLICATE KEY UPDATE ville_depart=ville_depart");

    $trajetIds = [];
    $trajetsByUser = [];

    foreach ($trajets as $i => $t) {
        $userId = $users[$i % count($users)]['utilisateur_id'];
        $vehicleId = $vehicleIds[$userId] ?? array_values($vehicleIds)[0];

        try {
            $stmt->execute([$userId, $vehicleId, $t[0], $t[1], $t[2], $t[3], $t[4], $t[5]]);
            $trajetId = $pdo->lastInsertId();
            if ($trajetId) {
                $trajetIds[] = $trajetId;
                $trajetsByUser[$userId][] = $trajetId;
            }
            echo "✓ {$t[0]} → {$t[1]} ({$t[2]})<br>";
        } catch (Exception $e) {
            echo "⚠️ Trajet {$t[0]} → {$t[1]} déjà existant<br>";
        }
    }

    // 6. PARTICIPATIONS
    echo "<br><h2>🎟️ Création des participations</h2>";
    $participationCount = 0;

    if (count($trajetIds) > 0) {
        $stmt = $pdo->prepare("INSERT INTO participation (covoiturage_id, passager_id, nombre_places, credit_utilise, statut) VALUES (?, ?, 1, ?, 'confirme') ON DUPLICATE KEY UPDATE statut=statut");

        foreach ($trajetIds as $i => $trajetId) {
            // Trouver le conducteur du trajet
            $conducteurResult = $pdo->query("SELECT conducteur_id FROM covoiturage WHERE covoiturage_id = $trajetId")->fetch();
            if (!$conducteurResult) continue;

            $conducteurId = $conducteurResult['conducteur_id'];

            // 2-3 passagers par trajet
            $nbPassagers = rand(1, 3);
            $addedPassengers = 0;

            foreach ($users as $user) {
                if ($addedPassengers >= $nbPassagers) break;
                if ($user['utilisateur_id'] == $conducteurId) continue; // Pas le conducteur

                try {
                    // Récupérer le prix du trajet
                    $prixResult = $pdo->query("SELECT prix_par_place FROM covoiturage WHERE covoiturage_id = $trajetId")->fetch();
                    $prix = $prixResult ? $prixResult['prix_par_place'] : 10;

                    $stmt->execute([$trajetId, $user['utilisateur_id'], $prix]);
                    $participationCount++;
                    $addedPassengers++;
                } catch (Exception $e) {
                    // Participation déjà existante, continuer
                }
            }
        }
    }
    echo "✓ {$participationCount} participations créées<br>";

    // 7. AVIS
    echo "<br><h2>⭐ Création des avis</h2>";
    $avisCount = 0;

    // Avis pour quelques trajets terminés
    $participations = $pdo->query("SELECT p.participation_id, p.covoiturage_id, p.passager_id, c.conducteur_id
                                   FROM participation p
                                   JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
                                   LIMIT 10")->fetchAll();

    $stmt = $pdo->prepare("INSERT INTO avis (covoiturage_id, auteur_id, destinataire_id, commentaire, note, statut) VALUES (?, ?, ?, ?, ?, 'valide') ON DUPLICATE KEY UPDATE note=note");

    $commentaires = [
        "Excellent conducteur, très ponctuel et agréable !",
        "Trajet très agréable, bonne ambiance dans la voiture.",
        "Conducteur sympathique, conduite sécuritaire.",
        "Parfait, je recommande vivement !",
        "Bon trajet, rien à redire.",
        "Super expérience, à refaire !",
        "Passager ponctuel et respectueux.",
        "Très bonne communication, trajet fluide."
    ];

    foreach ($participations as $i => $p) {
        try {
            $note = rand(4, 5); // Notes positives
            $commentaire = $commentaires[array_rand($commentaires)];

            // Passager note le conducteur
            $stmt->execute([$p['covoiturage_id'], $p['passager_id'], $p['conducteur_id'], $commentaire, $note]);
            $avisCount++;
        } catch (Exception $e) {
            // Avis déjà existant
        }
    }

    echo "✓ {$avisCount} avis créés<br>";

    // RÉSUMÉ
    echo "<br><h2>✅ Résumé de l'initialisation</h2>";
    echo "<ul>";
    echo "<li><strong>1 Administrateur</strong> : admin@ecoride.fr</li>";
    echo "<li><strong>3 Employés</strong> : Sophie, Lucas, Emma</li>";
    echo "<li><strong>5 Utilisateurs</strong> : Jean, Marie, Paul, Alice, Thomas</li>";
    echo "<li><strong>11 Véhicules écologiques</strong> : 4 électriques, 3 hybrides, 2 GPL, 1 essence, 1 diesel</li>";
    echo "<li><strong>33 Trajets</strong> de janvier à février 2026</li>";
    echo "<li><strong>{$participationCount} Participations</strong></li>";
    echo "<li><strong>{$avisCount} Avis</strong></li>";
    echo "</ul>";

    echo "<h3>🔑 Comptes de test :</h3>";
    echo "<ul>";
    echo "<li><strong>Admin :</strong> admin@ecoride.fr / Ec0R1de!</li>";
    echo "<li><strong>Employés :</strong> sophie.martin@ecoride.fr / Sophie2025! (etc.)</li>";
    echo "<li><strong>Utilisateurs :</strong> jean.dupont@ecoride.fr / Jean2025! (etc.)</li>";
    echo "</ul>";

    echo "<h3>🎯 Tests filtres :</h3>";
    echo "<ul>";
    echo "<li>Paris→Lyon : 3 trajets le 15/01/2026</li>";
    echo "<li>Marseille→Nice : 2 trajets le 18/01/2026</li>";
    echo "<li>Toulouse→Bordeaux : 2 trajets le 25/01/2026</li>";
    echo "</ul>";

    echo "<br><h2>🎉 Terminé !</h2>";

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}

echo "</body></html>";
?>
