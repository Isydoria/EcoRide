<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion.php');
    exit;
}

// Configuration Railway
$host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? 'localhost';
$dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecoride_db';
$username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? 'root';
$password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Récupérer les infos utilisateur actualisées
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE utilisateur_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les véhicules de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id' => $user_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les trajets créés par l'utilisateur (conducteur)
    $stmt = $pdo->prepare("
        SELECT c.*, v.marque, v.modele
        FROM covoiturage c
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        WHERE c.conducteur_id = :user_id
        ORDER BY c.date_depart DESC
        LIMIT 10
    ");
    $stmt->execute(['user_id' => $user_id]);
    $my_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les participations (passager)
    $stmt = $pdo->prepare("
        SELECT p.*, c.ville_depart, c.ville_arrivee, c.date_depart, u.pseudo as conducteur
        FROM participation p
        JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
        JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
        WHERE p.passager_id = :user_id
        ORDER BY c.date_depart DESC
        LIMIT 10
    ");
    $stmt->execute(['user_id' => $user_id]);
    $my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques
    $stats = [
        'credits' => $user_data['credit'],
        'trips_created' => count($my_trips),
        'trips_taken' => count($my_bookings),
        'vehicles' => count($vehicles)
    ];

} catch (Exception $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
}

// Gestion section active
$active_section = $_GET['section'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace - EcoRide</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            margin: 0;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #2ECC71, #27AE60);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .dashboard-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .dashboard-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .dashboard-nav a:hover {
            background: rgba(255,255,255,0.1);
        }

        .create-trip-btn {
            background: #f39c12 !important;
            font-weight: 600;
            padding: 12px 25px !important;
            border-radius: 25px !important;
            font-size: 16px;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }

        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            height: fit-content;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .sidebar h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 15px;
            color: #7f8c8d;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #2ECC71;
            color: white;
        }

        .main-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card.credits {
            background: linear-gradient(135deg, #2ECC71, #27AE60);
        }

        .stat-card.trips {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .stat-card.vehicles {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            opacity: 0.9;
            font-size: 0.9em;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .trips-list {
            display: grid;
            gap: 15px;
        }

        .trip-card {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            transition: shadow 0.3s;
        }

        .trip-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .trip-route {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .trip-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .add-vehicle-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2ECC71;
            color: white;
        }

        .btn-primary:hover {
            background: #27AE60;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .vehicle-card {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .vehicle-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            align-items: center;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div>
                <h1>👋 Bonjour <?= htmlspecialchars($_SESSION['user_pseudo']) ?></h1>
                <p>Gérez vos trajets et votre profil EcoRide</p>
            </div>
            <div class="dashboard-nav">
                <a href="../index.php">← Accueil</a>
                <a href="../creer-trajet.php" class="create-trip-btn">🚗 Créer un trajet</a>
                <?php if ($_SESSION['user_role'] === 'administrateur'): ?>
                    <a href="../admin/dashboard.php" style="background: #e74c3c; padding: 10px 15px; border-radius: 5px;">🛠️ Admin</a>
                <?php endif; ?>
                <a href="../logout.php">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h3>Navigation</h3>
            <ul class="sidebar-menu">
                <li><a href="?section=overview" class="<?= $active_section === 'overview' ? 'active' : '' ?>">📊 Vue d'ensemble</a></li>
                <li><a href="?section=my-trips" class="<?= $active_section === 'my-trips' ? 'active' : '' ?>">🚗 Mes trajets</a></li>
                <li><a href="?section=my-bookings" class="<?= $active_section === 'my-bookings' ? 'active' : '' ?>">🎫 Mes réservations</a></li>
                <li><a href="?section=vehicles" class="<?= $active_section === 'vehicles' ? 'active' : '' ?>">🚙 Mes véhicules</a></li>
                <li><a href="?section=profile" class="<?= $active_section === 'profile' ? 'active' : '' ?>">👤 Mon profil</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Vue d'ensemble -->
            <div class="section <?= $active_section === 'overview' ? 'active' : '' ?>">
                <h2>📊 Vue d'ensemble</h2>

                <div class="stats-grid">
                    <div class="stat-card credits">
                        <div class="stat-number"><?= $stats['credits'] ?></div>
                        <div class="stat-label">Crédits disponibles</div>
                    </div>
                    <div class="stat-card trips">
                        <div class="stat-number"><?= $stats['trips_created'] ?></div>
                        <div class="stat-label">Trajets créés</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['trips_taken'] ?></div>
                        <div class="stat-label">Trajets effectués</div>
                    </div>
                    <div class="stat-card vehicles">
                        <div class="stat-number"><?= $stats['vehicles'] ?></div>
                        <div class="stat-label">Véhicules enregistrés</div>
                    </div>
                </div>

                <div style="text-align: center; margin: 40px 0;">
                    <a href="../creer-trajet.php" class="btn btn-primary" style="font-size: 18px; padding: 15px 30px; border-radius: 25px;">
                        🚗 Créer un nouveau trajet
                    </a>
                </div>
            </div>

            <!-- Mes trajets -->
            <div class="section <?= $active_section === 'my-trips' ? 'active' : '' ?>">
                <h2>🚗 Mes trajets (en tant que conducteur)</h2>

                <?php if (empty($my_trips)): ?>
                    <div class="empty-state">
                        <h3>Aucun trajet créé</h3>
                        <p>Vous n'avez pas encore publié de trajets.</p>
                        <a href="../creer-trajet.php" class="btn btn-primary">Créer mon premier trajet</a>
                    </div>
                <?php else: ?>
                    <div class="trips-list">
                        <?php foreach ($my_trips as $trip): ?>
                            <div class="trip-card">
                                <div class="trip-route">
                                    <?= htmlspecialchars($trip['ville_depart']) ?> → <?= htmlspecialchars($trip['ville_arrivee']) ?>
                                </div>
                                <div class="trip-details">
                                    <div>📅 <?= date('d/m/Y à H:i', strtotime($trip['date_depart'])) ?></div>
                                    <div>💰 <?= number_format($trip['prix_par_place'], 2) ?>€/place</div>
                                    <div>👥 <?= $trip['places_disponibles'] ?> places</div>
                                    <div>🚗 <?= htmlspecialchars($trip['marque'] . ' ' . $trip['modele']) ?></div>
                                    <div>📊 <?= ucfirst($trip['statut']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mes réservations -->
            <div class="section <?= $active_section === 'my-bookings' ? 'active' : '' ?>">
                <h2>🎫 Mes réservations (en tant que passager)</h2>

                <?php if (empty($my_bookings)): ?>
                    <div class="empty-state">
                        <h3>Aucune réservation</h3>
                        <p>Vous n'avez pas encore réservé de trajets.</p>
                        <a href="../trajets.php" class="btn btn-primary">Rechercher des trajets</a>
                    </div>
                <?php else: ?>
                    <div class="trips-list">
                        <?php foreach ($my_bookings as $booking): ?>
                            <div class="trip-card">
                                <div class="trip-route">
                                    <?= htmlspecialchars($booking['ville_depart']) ?> → <?= htmlspecialchars($booking['ville_arrivee']) ?>
                                </div>
                                <div class="trip-details">
                                    <div>📅 <?= date('d/m/Y à H:i', strtotime($booking['date_depart'])) ?></div>
                                    <div>👨‍✈️ Conducteur: <?= htmlspecialchars($booking['conducteur']) ?></div>
                                    <div>🎫 <?= $booking['nombre_places'] ?> place(s)</div>
                                    <div>💳 <?= $booking['credit_utilise'] ?> crédits</div>
                                    <div>📊 <?= ucfirst($booking['statut']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mes véhicules -->
            <div class="section <?= $active_section === 'vehicles' ? 'active' : '' ?>">
                <h2>🚙 Mes véhicules</h2>

                <?php if (!empty($vehicles)): ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="vehicle-card">
                            <div class="vehicle-info">
                                <div>
                                    <strong><?= htmlspecialchars($vehicle['marque'] . ' ' . $vehicle['modele']) ?></strong><br>
                                    <small><?= htmlspecialchars($vehicle['immatriculation']) ?></small>
                                </div>
                                <div><?= htmlspecialchars($vehicle['couleur']) ?></div>
                                <div><?= $vehicle['places'] ?> places</div>
                                <div><?= ucfirst($vehicle['energie']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="add-vehicle-form">
                    <h3>Ajouter un véhicule</h3>
                    <form id="addVehicleForm" method="POST" action="../api/add-vehicle.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Marque *</label>
                                <input type="text" name="marque" required placeholder="Ex: Renault">
                            </div>
                            <div class="form-group">
                                <label>Modèle *</label>
                                <input type="text" name="modele" required placeholder="Ex: Clio">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Immatriculation *</label>
                                <input type="text" name="immatriculation" required placeholder="Ex: AB-123-CD">
                            </div>
                            <div class="form-group">
                                <label>Couleur</label>
                                <input type="text" name="couleur" placeholder="Ex: Rouge">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre de places *</label>
                                <select name="places" required>
                                    <option value="">Choisir...</option>
                                    <option value="2">2 places</option>
                                    <option value="4">4 places</option>
                                    <option value="5">5 places</option>
                                    <option value="7">7 places</option>
                                    <option value="9">9 places</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Type d'énergie *</label>
                                <select name="energie" required>
                                    <option value="">Choisir...</option>
                                    <option value="electrique">🔋 Électrique</option>
                                    <option value="hybride">⚡ Hybride</option>
                                    <option value="essence">⛽ Essence</option>
                                    <option value="diesel">🛢️ Diesel</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter ce véhicule</button>
                    </form>
                </div>
            </div>

            <!-- Mon profil -->
            <div class="section <?= $active_section === 'profile' ? 'active' : '' ?>">
                <h2>👤 Mon profil</h2>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pseudo</label>
                            <input type="text" value="<?= htmlspecialchars($user_data['pseudo']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($user_data['email']) ?>" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Crédits</label>
                            <input type="text" value="<?= $user_data['credit'] ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Membre depuis</label>
                            <input type="text" value="<?= date('d/m/Y', strtotime($user_data['created_at'])) ?>" readonly>
                        </div>
                    </div>
                    <p><em>💡 La modification du profil sera disponible dans une prochaine version.</em></p>
                </div>
            </div>
        </div>
    </div>

    </div>

    <!-- Footer -->
    <footer style="background: #2c3e50; color: white; padding: 40px 0; margin-top: 50px;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 30px;">
                <div>
                    <h4 style="color: #2ECC71; margin-bottom: 15px;">🚗🌱 EcoRide</h4>
                    <p style="color: #bdc3c7; line-height: 1.6;">La plateforme de covoiturage écologique qui révolutionne vos déplacements tout en préservant l'environnement.</p>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Navigation</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><a href="../index.php" style="color: #bdc3c7; text-decoration: none;">Accueil</a></li>
                        <li style="margin-bottom: 8px;"><a href="../trajets.php" style="color: #bdc3c7; text-decoration: none;">Rechercher des trajets</a></li>
                        <li style="margin-bottom: 8px;"><a href="../creer-trajet.php" style="color: #bdc3c7; text-decoration: none;">Créer un trajet</a></li>
                        <li style="margin-bottom: 8px;"><a href="../contact.php" style="color: #bdc3c7; text-decoration: none;">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Mon compte</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><a href="?section=overview" style="color: #bdc3c7; text-decoration: none;">Vue d'ensemble</a></li>
                        <li style="margin-bottom: 8px;"><a href="?section=my-trips" style="color: #bdc3c7; text-decoration: none;">Mes trajets</a></li>
                        <li style="margin-bottom: 8px;"><a href="?section=vehicles" style="color: #bdc3c7; text-decoration: none;">Mes véhicules</a></li>
                        <li style="margin-bottom: 8px;"><a href="?section=profile" style="color: #bdc3c7; text-decoration: none;">Mon profil</a></li>
                    </ul>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Support</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><a href="../comment-ca-marche.php" style="color: #bdc3c7; text-decoration: none;">Comment ça marche</a></li>
                        <li style="margin-bottom: 8px;"><a href="mailto:contact@ecoride.fr" style="color: #bdc3c7; text-decoration: none;">contact@ecoride.fr</a></li>
                        <li style="margin-bottom: 8px;"><span style="color: #bdc3c7;">Mentions légales</span></li>
                        <li style="margin-bottom: 8px;"><span style="color: #bdc3c7;">Politique de confidentialité</span></li>
                    </ul>
                </div>
            </div>

            <div style="border-top: 1px solid #34495e; padding-top: 20px; text-align: center;">
                <p style="color: #bdc3c7; margin: 0;">
                    © 2025 EcoRide - Plateforme de covoiturage écologique |
                    Développé pour l'évaluation RNCP Développeur Web et Web Mobile
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Gestion du formulaire d'ajout de véhicule
        document.getElementById('addVehicleForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('../api/add-vehicle.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout du véhicule');
            });
        });
    </script>
</body>
</html>