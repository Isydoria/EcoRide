<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Configuration Railway
$host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? 'localhost';
$dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecoride_db';
$username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? 'root';
$password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Récupérer les véhicules de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un trajet - EcoRide</title>
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
            background: linear-gradient(135deg, #2ECC71, #27ae60);
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

        .no-vehicle-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }

        .add-vehicle-btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="create-trip-container">
            <h1>🚗 Créer un nouveau trajet</h1>

            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($vehicles)): ?>
                <div class="no-vehicle-notice">
                    <h3>⚠️ Aucun véhicule enregistré</h3>
                    <p>Vous devez d'abord enregistrer un véhicule pour créer des trajets.</p>
                    <a href="user/dashboard.php#vehicles" class="add-vehicle-btn">Ajouter un véhicule</a>
                </div>
            <?php else: ?>

                <form id="createTripForm" method="POST" action="api/create-trajet.php">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ville_depart">🏁 Ville de départ *</label>
                            <input type="text" id="ville_depart" name="ville_depart" required
                                   placeholder="Ex: Paris, Lyon, Marseille...">
                        </div>

                        <div class="form-group">
                            <label for="ville_arrivee">🏁 Ville d'arrivée *</label>
                            <input type="text" id="ville_arrivee" name="ville_arrivee" required
                                   placeholder="Ex: Nice, Bordeaux, Lille...">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_depart">📅 Date de départ *</label>
                            <input type="date" id="date_depart" name="date_depart" required
                                   min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group">
                            <label for="heure_depart">🕒 Heure de départ *</label>
                            <input type="time" id="heure_depart" name="heure_depart" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="voiture_id">🚙 Véhicule *</label>
                            <select id="voiture_id" name="voiture_id" required>
                                <option value="">Choisir un véhicule</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?= $vehicle['voiture_id'] ?>">
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
                                <option value="1">1 place</option>
                                <option value="2">2 places</option>
                                <option value="3">3 places</option>
                                <option value="4">4 places</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="prix_par_place">💰 Prix par place (€) *</label>
                        <input type="number" id="prix_par_place" name="prix_par_place"
                               min="1" max="100" step="0.50" required
                               placeholder="Ex: 15.50">
                    </div>

                    <div class="price-info">
                        <strong>💡 Information tarification :</strong><br>
                        EcoRide prélève une commission de 2 crédits par transaction pour maintenir la plateforme.
                        Le prix que vous fixez est entièrement versé à votre compte de crédits.
                    </div>

                    <div class="form-group">
                        <label for="commentaire">💬 Commentaire (optionnel)</label>
                        <textarea id="commentaire" name="commentaire" rows="3"
                                  placeholder="Informations supplémentaires pour les passagers..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        🚀 Publier mon trajet
                    </button>

                </form>

            <?php endif; ?>
        </div>
    </main>

    <script>
        document.getElementById('createTripForm')?.addEventListener('submit', function(e) {
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
            fetch('api/create-trajet.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    window.location.href = 'user/dashboard.php#mes-trajets';
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la création du trajet');
            });
        });
    </script>
</body>
</html>