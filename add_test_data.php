<?php
/**
 * Script pour ajouter des données de test
 */

// Configuration Railway
$host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? null;
$dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? null;
$username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? null;
$password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? null;

if (!$host || !$dbname || !$username || !$password) {
    die('❌ Variables d\'environnement Railway non trouvées');
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>📊 Ajout de données de test EcoRide</h2>";

    // Vérifier si des utilisateurs existent déjà
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateur");
    $userCount = $stmt->fetchColumn();

    if ($userCount > 0) {
        echo "<p>✅ $userCount utilisateurs existent déjà dans la base</p>";
    } else {
        // Créer des utilisateurs de test
        $users = [
            [
                'pseudo' => 'jean.dupont',
                'email' => 'jean.dupont@example.com',
                'password' => password_hash('motdepasse123', PASSWORD_DEFAULT),
                'credit' => 25,
                'role' => 'utilisateur'
            ],
            [
                'pseudo' => 'marie.martin',
                'email' => 'marie.martin@example.com',
                'password' => password_hash('motdepasse123', PASSWORD_DEFAULT),
                'credit' => 30,
                'role' => 'utilisateur'
            ],
            [
                'pseudo' => 'demo',
                'email' => 'demo@ecoride.fr',
                'password' => password_hash('demo123', PASSWORD_DEFAULT),
                'credit' => 50,
                'role' => 'utilisateur'
            ]
        ];

        foreach ($users as $user) {
            $sql = "INSERT INTO utilisateur (pseudo, email, password, credit, role, statut)
                    VALUES (:pseudo, :email, :password, :credit, :role, 'actif')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($user);
            echo "<p>✅ Utilisateur {$user['pseudo']} créé</p>";
        }
    }

    // Ajouter des voitures de test
    $stmt = $pdo->query("SELECT COUNT(*) FROM voiture");
    $carCount = $stmt->fetchColumn();

    if ($carCount === 0) {
        echo "<h3>🚗 Ajout de véhicules de test</h3>";

        // Récupérer IDs utilisateurs
        $stmt = $pdo->query("SELECT utilisateur_id FROM utilisateur LIMIT 2");
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($userIds) >= 2) {
            $cars = [
                [
                    'utilisateur_id' => $userIds[0],
                    'modele' => 'Model 3',
                    'marque' => 'Tesla',
                    'immatriculation' => 'AB-123-CD',
                    'couleur' => 'Blanche',
                    'energie' => 'electrique',
                    'places' => 4
                ],
                [
                    'utilisateur_id' => $userIds[1],
                    'modele' => 'Clio',
                    'marque' => 'Renault',
                    'immatriculation' => 'EF-456-GH',
                    'couleur' => 'Rouge',
                    'energie' => 'essence',
                    'places' => 5
                ]
            ];

            foreach ($cars as $car) {
                $sql = "INSERT INTO voiture (utilisateur_id, modele, marque, immatriculation, couleur, energie, places, date_premiere_immatriculation)
                        VALUES (:utilisateur_id, :modele, :marque, :immatriculation, :couleur, :energie, :places, '2020-01-15')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($car);
                echo "<p>✅ Véhicule {$car['marque']} {$car['modele']} ajouté</p>";
            }
        }
    }

    // Ajouter des trajets de test
    $stmt = $pdo->query("SELECT COUNT(*) FROM covoiturage");
    $tripCount = $stmt->fetchColumn();

    if ($tripCount === 0) {
        echo "<h3>🗺️ Ajout de trajets de test</h3>";

        $stmt = $pdo->query("SELECT v.voiture_id, v.utilisateur_id FROM voiture v LIMIT 2");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($vehicles) >= 1) {
            $trips = [
                [
                    'conducteur_id' => $vehicles[0]['utilisateur_id'],
                    'voiture_id' => $vehicles[0]['voiture_id'],
                    'ville_depart' => 'Paris',
                    'ville_arrivee' => 'Lyon',
                    'date_depart' => date('Y-m-d H:i:s', strtotime('+1 day')),
                    'date_arrivee' => date('Y-m-d H:i:s', strtotime('+1 day +4 hours')),
                    'places_disponibles' => 3,
                    'prix_par_place' => 25.00
                ],
                [
                    'conducteur_id' => $vehicles[0]['utilisateur_id'],
                    'voiture_id' => $vehicles[0]['voiture_id'],
                    'ville_depart' => 'Marseille',
                    'ville_arrivee' => 'Nice',
                    'date_depart' => date('Y-m-d H:i:s', strtotime('+2 days')),
                    'date_arrivee' => date('Y-m-d H:i:s', strtotime('+2 days +2 hours')),
                    'places_disponibles' => 2,
                    'prix_par_place' => 15.00
                ]
            ];

            foreach ($trips as $trip) {
                $sql = "INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, date_arrivee, places_disponibles, prix_par_place, statut)
                        VALUES (:conducteur_id, :voiture_id, :ville_depart, :ville_arrivee, :date_depart, :date_arrivee, :places_disponibles, :prix_par_place, 'planifie')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($trip);
                echo "<p>✅ Trajet {$trip['ville_depart']} → {$trip['ville_arrivee']} ajouté</p>";
            }
        }
    }

    echo "<h3>🎯 Comptes de test disponibles :</h3>";
    echo "<ul>";
    echo "<li><strong>jean.dupont</strong> / motdepasse123</li>";
    echo "<li><strong>marie.martin</strong> / motdepasse123</li>";
    echo "<li><strong>demo</strong> / demo123</li>";
    echo "</ul>";

    echo "<p><strong>🎉 Données de test ajoutées avec succès !</strong></p>";
    echo '<p><a href="/" style="color: #2ECC71; text-decoration: none;">← Retour à l\'accueil</a></p>';

} catch (Exception $e) {
    echo "<p>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>