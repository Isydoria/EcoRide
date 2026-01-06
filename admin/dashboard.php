<?php
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ../connexion.php');
    exit;
}

// Activer l'affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/init.php';

// Initialiser les variables
$error = null;
$stats = [];
$trip_stats = [];
$employees = [];
$recent_users = [];
$recent_trips = [];

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    // Statistiques générales
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateur");
    $stats['total_users'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM covoiturage");
    $stats['total_trips'] = $stmt->fetchColumn();

    // La colonne s'appelle 'credit' (singulier) dans PostgreSQL et MySQL
    $stmt = $pdo->query("SELECT SUM(credit) FROM utilisateur");
    $stats['total_credits'] = $stmt->fetchColumn() ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) FROM participation");
    $stats['total_bookings'] = $stmt->fetchColumn();

    // Trajets par statut - avec valeurs par défaut
    $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM covoiturage GROUP BY statut");
    $trip_stats_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Assurer que toutes les clés existent
    $trip_stats = [
        'planifie' => $trip_stats_raw['planifie'] ?? 0,
        'en_cours' => $trip_stats_raw['en_cours'] ?? 0,
        'termine' => $trip_stats_raw['termine'] ?? 0,
        'annule' => $trip_stats_raw['annule'] ?? 0
    ];

    // Liste des employés - Compatible MySQL/PostgreSQL
    $stmt = $pdo->query("
        SELECT utilisateur_id, pseudo, email, created_at, statut
        FROM utilisateur
        WHERE role = 'employe'
        ORDER BY created_at DESC
    ");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Derniers utilisateurs inscrits
    $stmt = $pdo->prepare("
        SELECT utilisateur_id, pseudo, email, created_at, statut, role
        FROM utilisateur
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Derniers trajets créés - Compatible MySQL/PostgreSQL
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.pseudo as conducteur, v.marque, v.modele
            FROM covoiturage c
            LEFT JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            ORDER BY c.covoiturage_id DESC
            LIMIT 10
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, u.pseudo as conducteur, v.marque, v.modele
            FROM covoiturage c
            LEFT JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            ORDER BY c.created_at DESC
            LIMIT 10
        ");
    }
    $stmt->execute();
    $recent_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - EcoRide</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f7fa;
        }
        
        .admin-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        
        .admin-header { 
            background: #2c3e50; 
            color: white; 
            padding: 20px 0; 
            margin-bottom: 20px;
        }
        
        .admin-nav { 
            display: flex; 
            gap: 20px; 
            align-items: center; 
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-nav a { 
            color: white; 
            text-decoration: none; 
            padding: 10px 15px; 
            border-radius: 5px; 
        }
        
        .admin-nav a:hover { 
            background: rgba(255,255,255,0.1); 
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin: 20px 0; 
        }
        
        .stat-card { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            text-align: center; 
        }
        
        .stat-number { 
            font-size: 28px; /* Taille fixe au lieu de 2em */
            font-weight: bold; 
            color: #2ECC71; 
            line-height: 1.2;
        }
        
        .stat-label { 
            color: #7f8c8d; 
            margin-top: 5px; 
        }
        
        .action-section {
            background: #e8f8f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2ecc71;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2ECC71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #27AE60;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .users-table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin: 20px 0;
        }
        
        .users-table th, .users-table td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ecf0f1; 
        }
        
        .users-table th { 
            background: #34495e; 
            color: white; 
        }
        
        .status-badge { 
            padding: 4px 8px; 
            border-radius: 4px; 
            color: white; 
            font-size: 0.8em; 
        }
        
        .status-actif { background: #2ECC71; }
        .status-suspendu { background: #e74c3c; }
        .status-planifie { background: #3498db; }
        
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .role-administrateur { background: #e74c3c; color: white; }
        .role-employe { background: #3498db; color: white; }
        .role-utilisateur { background: #95a5a6; color: white; }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 300px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        /* Liste employés dans action-section */
        .action-section ul {
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
        }

        .action-section ul li {
            padding: 10px;
            margin: 8px 0;
            background: white;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Wrapper pour scroll horizontal des tableaux */
        .table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* ========== RESPONSIVE MOBILE ========== */
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .admin-header {
                padding: 15px 0;
                margin-bottom: 15px;
            }

            .admin-nav {
                flex-direction: column;
                gap: 10px;
                padding: 0 10px;
                align-items: stretch;
            }

            .admin-nav h1 {
                font-size: 18px;
                margin: 0;
                text-align: center;
            }

            .admin-nav div {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                justify-content: center;
            }

            .admin-nav a {
                padding: 8px 10px;
                font-size: 12px;
                flex: 1 1 auto;
                text-align: center;
                white-space: nowrap;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 15px 10px;
            }

            .stat-number {
                font-size: 28px;
            }

            .stat-label {
                font-size: 13px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .chart-container {
                height: 250px;
                padding: 15px;
            }

            .chart-container h3 {
                font-size: 16px;
                margin-top: 0;
            }

            /* Tableau responsive avec wrapper */
            .table-wrapper {
                margin: 15px -10px;
            }

            .users-table {
                font-size: 11px;
                min-width: 600px;
            }

            .users-table th,
            .users-table td {
                padding: 8px 4px;
                white-space: nowrap;
            }

            .action-section {
                padding: 12px;
                margin: 15px 0;
            }

            .action-section h3 {
                font-size: 16px;
                margin-top: 0;
            }

            .action-section ul li {
                padding: 8px;
                font-size: 12px;
                flex-direction: column;
                align-items: flex-start;
            }

            .btn {
                font-size: 12px;
                padding: 6px 12px;
                white-space: nowrap;
            }

            .status-badge,
            .role-badge {
                font-size: 10px;
                padding: 3px 6px;
            }

            h2 {
                font-size: 20px;
                margin: 15px 0 10px 0;
            }
        }

        /* ========== TABLETTES ========== */
        @media (max-width: 1024px) and (min-width: 769px) {
            .admin-container {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .users-table {
                font-size: 13px;
            }

            .users-table th,
            .users-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-nav">
            <h1>🛠️ Administration EcoRide</h1>
            <div>
                <a href="dashboard.php">📊 Tableau de bord</a>
                <a href="mongodb-stats.php">🗄️ MongoDB Stats</a> <!-- 🆕 NOUVEAU -->
                <a href="../user/dashboard.php">👤 Mode utilisateur</a>
                <a href="../index.php">🏠 Accueil</a>
                <a href="../logout.php">🚪 Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Statistiques générales -->
        <h2>📊 Tableau de bord</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_users'] ?? 0 ?></div>
                <div class="stat-label">Utilisateurs inscrits</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_trips'] ?? 0 ?></div>
                <div class="stat-label">Trajets créés</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total_credits'] ?? 0) ?></div>
                <div class="stat-label">Crédits totaux</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_bookings'] ?? 0 ?></div>
                <div class="stat-label">Réservations totales</div>
            </div>
        </div>

        <!-- Section Gestion des employés -->
        <div class="action-section">
            <h3>👥 Gestion des employés</h3>
            <p style="color: #7f8c8d; margin: 10px 0;">
                Créez et gérez les comptes employés pour la modération.
            </p>
            <a href="create-employee.php" class="btn">
                ➕ Créer un nouvel employé
            </a>
            
            <?php if (count($employees) > 0): ?>
                <h4 style="margin-top: 20px;">Employés actuels :</h4>
                <ul>
                <?php foreach ($employees as $emp): ?>
                    <li>
                        <?= htmlspecialchars($emp['pseudo']) ?> - 
                        <?= htmlspecialchars($emp['email']) ?> - 
                        <span class="status-badge status-<?= $emp['statut'] ?>">
                            <?= ucfirst($emp['statut']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #7f8c8d; margin-top: 15px;">Aucun employé créé.</p>
            <?php endif; ?>
        </div>

        <!-- Graphiques côte à côte -->
        <div class="charts-grid">
            <div class="chart-container">
                <h3>📊 Répartition des trajets</h3>
                <div style="position: relative; height: 200px;">
                    <canvas id="tripsChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <h3>📈 Évolution des inscriptions</h3>
                <div style="position: relative; height: 200px;">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Utilisateurs récents -->
        <?php if (count($recent_users) > 0): ?>
        <h2>👥 Derniers utilisateurs inscrits</h2>
        <div class="table-wrapper">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Pseudo</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date inscription</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['pseudo']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= $user['role'] ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= $user['statut'] ?>">
                            <?= ucfirst($user['statut']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['utilisateur_id'] != $_SESSION['user_id']): ?>
                            <button class="btn <?= $user['statut'] === 'actif' ? 'btn-danger' : '' ?>" 
                                    onclick="toggleUserStatus(<?= $user['utilisateur_id'] ?>, '<?= $user['statut'] ?>')"
                                    style="padding: 5px 10px; font-size: 12px;">
                                <?= $user['statut'] === 'actif' ? 'Suspendre' : 'Activer' ?>
                            </button>
                        <?php else: ?>
                            <span style="color: #7f8c8d; font-size: 12px;">Vous</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>

        <!-- Derniers trajets -->
        <?php if (count($recent_trips) > 0): ?>
        <h2>🚗 Derniers trajets créés</h2>
        <div class="table-wrapper">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Trajet</th>
                    <th>Conducteur</th>
                    <th>Véhicule</th>
                    <th>Date départ</th>
                    <th>Prix</th>
                    <th>Places</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_trips as $trip): ?>
                <tr>
                    <td>
                        <strong>
                            <?= htmlspecialchars($trip['ville_depart'] ?? 'N/A') ?> → 
                            <?= htmlspecialchars($trip['ville_arrivee'] ?? 'N/A') ?>
                        </strong>
                    </td>
                    <td><?= htmlspecialchars($trip['conducteur'] ?? 'N/A') ?></td>
                    <td>
                        <?= htmlspecialchars(($trip['marque'] ?? '') . ' ' . ($trip['modele'] ?? '')) ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($trip['date_depart'])) ?></td>
                    <td><?= number_format($trip['prix_par_place'] ?? $trip['prix'] ?? 0, 2) ?> crédits</td>
                    <td><?= $trip['places_disponibles'] ?? 0 ?></td>
                    <td>
                        <span class="status-badge status-<?= $trip['statut'] ?? 'planifie' ?>">
                            <?= ucfirst($trip['statut'] ?? 'planifie') ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Token CSRF pour les requêtes
        const csrfToken = '<?php echo generateCSRFToken(); ?>';

        // Protection contre le rechargement infini
        if (window.performance && window.performance.navigation.type === 1) {
            console.log('Page rechargée');
        }

        // Fonction pour suspendre/activer
        function toggleUserStatus(userId, currentStatus) {
            const action = currentStatus === 'actif' ? 'suspend' : 'activate';
            const confirmMessage = currentStatus === 'actif'
                ? 'Voulez-vous vraiment suspendre cet utilisateur ?'
                : 'Voulez-vous vraiment réactiver cet utilisateur ?';

            if (confirm(confirmMessage)) {
                fetch('../api/toggle-user-status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, action: action, csrf_token: csrfToken})
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la modification du statut');
                });
            }
        }

        // Graphiques Chart.js avec gestion d'erreur
        document.addEventListener('DOMContentLoaded', function() {
            // Graphique 1 : Répartition des trajets
            const ctx1 = document.getElementById('tripsChart');
            if (ctx1) {
                try {
                    new Chart(ctx1, {
                        type: 'doughnut',
                        data: {
                            labels: ['Planifiés', 'En cours', 'Terminés', 'Annulés'],
                            datasets: [{
                                data: [
                                    <?= (int)$trip_stats['planifie'] ?>,
                                    <?= (int)$trip_stats['en_cours'] ?>,
                                    <?= (int)$trip_stats['termine'] ?>,
                                    <?= (int)$trip_stats['annule'] ?>
                                ],
                                backgroundColor: ['#3498db', '#f39c12', '#2ecc71', '#e74c3c']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                } catch(e) {
                    console.error('Erreur Chart.js (trajets):', e);
                    ctx1.parentElement.style.display = 'none';
                }
            }
            
            // Graphique 2 : Évolution des inscriptions
            const ctx2 = document.getElementById('usersChart');
            if (ctx2) {
                try {
                    new Chart(ctx2, {
                        type: 'line',
                        data: {
                            labels: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin'],
                            datasets: [{
                                label: 'Nouvelles inscriptions',
                                data: [2, 4, 6, 8, 7, <?= $stats['total_users'] ?? 9 ?>],
                                borderColor: '#2ecc71',
                                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                } catch(e) {
                    console.error('Erreur Chart.js (users):', e);
                    ctx2.parentElement.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>