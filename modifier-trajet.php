<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

require_once 'config/init.php';

// Vérifier que l'ID du trajet est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: user/dashboard.php?section=my-trips');
    exit;
}

$trip_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    // Récupérer les détails du trajet pour vérifier que l'utilisateur est bien le conducteur
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT c.*, v.marque, v.modele
            FROM covoiturage c
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            WHERE c.covoiturage_id = :trip_id AND c.conducteur_id = :user_id
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, v.marque, v.modele
            FROM covoiturage c
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            WHERE c.covoiturage_id = :trip_id AND c.conducteur_id = :user_id
        ");
    }
    $stmt->execute(['trip_id' => $trip_id, 'user_id' => $user_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        header('Location: user/dashboard.php?section=my-trips');
        exit;
    }

    // Vérifier que le trajet peut encore être modifié (statut "planifie")
    if ($trip['statut'] !== 'planifie') {
        $error = "Ce trajet ne peut plus être modifié car il a déjà commencé ou est terminé.";
    }

    // Récupérer les véhicules de l'utilisateur
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = ?");
    }
    $stmt->execute([$user_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
    error_log("Erreur modifier-trajet.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le trajet - EcoRide</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/trajets.css">
    <style>
        .create-trip-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2ECC71;
        }

        .price-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #2ECC71;
        }

        .submit-btn {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: #95a5a6;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: transform 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            background: #7f8c8d;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation incluse directement -->
    <nav class="navbar">
        <div class="nav-wrapper">
            <a href="index.php" class="logo">
                <span>🚗🌱 EcoRide</span>
            </a>

            <button class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="nav-link">Accueil</a></li>
                <li><a href="trajets.php" class="nav-link">Trajets</a></li>
                <li><a href="user/dashboard.php" class="nav-link">Mon espace</a></li>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employe'): ?>
                    <li><a href="employee/dashboard.php" class="nav-link" style="background: #3498db; color: white; padding: 8px 15px; border-radius: 20px;">👔 Dashboard Employé</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrateur'): ?>
                    <li><a href="admin/dashboard.php" class="nav-link" style="background: #e74c3c; color: white; padding: 8px 15px; border-radius: 20px;">🛠️ Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="nav-link">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <main class="main-content" style="padding-top: 80px;">
        <div class="create-trip-container">
            <h1>✏️ Modifier le trajet</h1>
            <p style="color: #7f8c8d; margin-bottom: 20px;">
                <?= htmlspecialchars($trip['ville_depart'] ?? '') ?> → <?= htmlspecialchars($trip['ville_arrivee'] ?? '') ?>
            </p>

            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <a href="user/dashboard.php?section=my-trips" class="cancel-btn">← Retour à mes trajets</a>
            <?php else: ?>

                <form id="editTripForm" method="POST" action="api/edit-trajet.php">
                    <input type="hidden" name="trip_id" value="<?= $trip_id ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ville_depart">🏁 Ville de départ *</label>
                            <input type="text" id="ville_depart" name="ville_depart" required
                                   value="<?= htmlspecialchars($trip['ville_depart'] ?? '') ?>"
                                   placeholder="Ex: Paris, Lyon, Marseille...">
                        </div>

                        <div class="form-group">
                            <label for="ville_arrivee">🏁 Ville d'arrivée *</label>
                            <input type="text" id="ville_arrivee" name="ville_arrivee" required
                                   value="<?= htmlspecialchars($trip['ville_arrivee'] ?? '') ?>"
                                   placeholder="Ex: Nice, Bordeaux, Lille...">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_depart">📅 Date de départ *</label>
                            <input type="date" id="date_depart" name="date_depart" required
                                   value="<?= date('Y-m-d', strtotime($trip['date_depart'])) ?>"
                                   min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group">
                            <label for="heure_depart">🕒 Heure de départ *</label>
                            <input type="time" id="heure_depart" name="heure_depart" required
                                   value="<?= date('H:i', strtotime($trip['date_depart'])) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_arrivee">📅 Date d'arrivée *</label>
                            <input type="date" id="date_arrivee" name="date_arrivee" required
                                   value="<?= date('Y-m-d', strtotime($trip['date_arrivee'])) ?>"
                                   min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group">
                            <label for="heure_arrivee">🕒 Heure d'arrivée prévue *</label>
                            <input type="time" id="heure_arrivee" name="heure_arrivee" required
                                   value="<?= date('H:i', strtotime($trip['date_arrivee'])) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="voiture_id">🚙 Véhicule *</label>
                            <select id="voiture_id" name="voiture_id" required>
                                <option value="">Choisir un véhicule</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <?php
                                        $vehicle_id = $vehicle['vehicule_id'] ?? $vehicle['voiture_id'];
                                        $trip_vehicle_id = $trip['voiture_id'];
                                    ?>
                                    <option value="<?= $vehicle_id ?>"
                                            <?= ($vehicle_id == $trip_vehicle_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vehicle['marque'] . ' ' . $vehicle['modele']) ?>
                                        (<?= htmlspecialchars($vehicle['immatriculation']) ?>)
                                        - <?= $vehicle['places'] ?> places
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="places_disponibles">👥 Places disponibles *</label>
                            <select id="places_disponibles" name="places_disponibles" required>
                                <option value="">Sélectionner...</option>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($i == $trip['places_disponibles']) ? 'selected' : '' ?>>
                                        <?= $i ?> place<?= $i > 1 ? 's' : '' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="prix_par_place">💰 Prix par place (crédits) *</label>
                        <input type="number" id="prix_par_place" name="prix_par_place"
                               min="1" max="100" step="0.50" required
                               value="<?= $trip['prix_par_place'] ?>"
                               placeholder="Ex: 15.50">
                    </div>

                    <div class="price-info">
                        <strong>💡 Information tarification :</strong><br>
                        EcoRide prélève une commission de 2 crédits par transaction pour maintenir la plateforme.
                        Le prix que vous fixez est entièrement versé à votre compte de crédits.
                    </div>

                    <button type="submit" class="submit-btn">
                        ✏️ Mettre à jour le trajet
                    </button>

                    <a href="user/dashboard.php?section=my-trips" class="cancel-btn">
                        ← Annuler et retourner
                    </a>

                </form>

            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer" style="background: #2c3e50; color: white; padding: 40px 0; margin-top: 50px;">
        <div style="max-width: 800px; margin: 0 auto; padding: 0 20px; text-align: center;">
            <div style="margin-bottom: 20px;">
                <h4 style="color: #2ECC71; margin-bottom: 15px;">🚗🌱 EcoRide</h4>
                <p style="color: #bdc3c7;">Modifiez vos trajets facilement</p>
            </div>

            <div style="border-top: 1px solid #34495e; padding-top: 20px;">
                <p style="color: #bdc3c7; margin: 0;">© 2025 EcoRide - Plateforme de covoiturage écologique</p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('editTripForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Validation côté client
            const depart = formData.get('ville_depart');
            const arrivee = formData.get('ville_arrivee');

            if (depart.toLowerCase() === arrivee.toLowerCase()) {
                alert('La ville de départ et d\'arrivée ne peuvent pas être identiques !');
                return;
            }

            // Soumission
            fetch('api/edit-trajet.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    window.location.href = 'user/dashboard.php?section=my-trips';
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la modification du trajet');
            });
        });
    </script>

    <script src="js/main.js"></script>
</body>
</html>