<?php
/**
 * admin/mongodb-stats.php
 * Interface de visualisation des données MongoDB (Base NoSQL)
 */

session_start();

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ../connexion.php');
    exit;
}

require_once '../config/init.php';

$error = null;
$mongo_available = false;
$activities = [];
$searches = [];
$stats = [
    'total_activities' => 0,
    'total_searches' => 0,
    'total_performance' => 0
];

// Vérifier si MongoDB est disponible
if (function_exists('mongodb')) {
    try {
        $mongo = mongodb();
        $mongo_available = true;
        
        // Récupérer les statistiques
        $stats = $mongo->getStats();
        
        // Récupérer les dernières activités
        $activities = $mongo->getRecentActivities(20);
        
        // Récupérer les dernières recherches
        $searches = $mongo->getRecentSearches(20);
        
    } catch (Exception $e) {
        $error = "Erreur MongoDB : " . $e->getMessage();
    }
} else {
    $error = "MongoDB n'est pas initialisé";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MongoDB Stats - EcoRide Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #2ECC71 0%, #27AE60 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #2ECC71;
        }
        .section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .section h2 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #dee2e6;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2ECC71;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Retour au dashboard</a>
        
        <div class="header">
            <h1>📊 Statistiques MongoDB</h1>
            <p>Base de données NoSQL - Logs et métriques applicatives</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($mongo_available): ?>
            
            <!-- Statistiques globales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>📝 Activités enregistrées</h3>
                    <div class="value"><?= number_format($stats['total_activities']) ?></div>
                </div>
                <div class="stat-card">
                    <h3>🔍 Recherches effectuées</h3>
                    <div class="value"><?= number_format($stats['total_searches']) ?></div>
                </div>
                <div class="stat-card">
                    <h3>⚡ Métriques performance</h3>
                    <div class="value"><?= number_format($stats['total_performance']) ?></div>
                </div>
            </div>

            <!-- Dernières activités -->
            <div class="section">
                <h2>📋 Dernières activités utilisateurs</h2>
                <?php if (count($activities) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Utilisateur ID</th>
                                <th>Action</th>
                                <th>Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($activities as $activity): ?>
                            <tr>
                                <td><?= htmlspecialchars($activity['_created'] ?? '') ?></td>
                                <td>#<?= htmlspecialchars($activity['user_id'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?= htmlspecialchars($activity['action'] ?? '') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($activity['data']['email'])): ?>
                                        <?= htmlspecialchars($activity['data']['email']) ?>
                                    <?php elseif (isset($activity['data']['ville_depart'])): ?>
                                        <?= htmlspecialchars($activity['data']['ville_depart']) ?> 
                                        → <?= htmlspecialchars($activity['data']['ville_arrivee']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucune activité enregistrée pour le moment.</p>
                <?php endif; ?>
            </div>

            <!-- Dernières recherches -->
            <div class="section">
                <h2>🔎 Dernières recherches de trajets</h2>
                <?php if (count($searches) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Utilisateur ID</th>
                                <th>Départ</th>
                                <th>Arrivée</th>
                                <th>Résultats</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($searches as $search): ?>
                            <tr>
                                <td><?= htmlspecialchars($search['_created'] ?? '') ?></td>
                                <td>#<?= htmlspecialchars($search['user_id'] ?? '0') ?></td>
                                <td><?= htmlspecialchars($search['depart'] ?? '') ?></td>
                                <td><?= htmlspecialchars($search['arrivee'] ?? '') ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($search['results_count'] ?? '0') ?> trajet(s)
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucune recherche enregistrée pour le moment.</p>
                <?php endif; ?>
            </div>

            <!-- Information technique -->
            <div class="section">
                <h2>⚙️ Information technique</h2>
                <p><strong>Implémentation :</strong> mongodb_fake.php (compatible PHP 8.3.14)</p>
                <p><strong>Stockage :</strong> Fichiers JSON dans mongodb_data/</p>
                <p><strong>Collections :</strong> activity_logs, search_history, performance_metrics</p>
                <p><strong>Test disponible :</strong> <a href="../test-mongodb-simple.php" target="_blank">/test-mongodb-simple.php</a></p>
            </div>

        <?php else: ?>
            <div class="section">
                <h2>⚠️ MongoDB non disponible</h2>
                <p>Le module MongoDB n'est pas chargé. Vérifiez que mongodb_fake.php est bien chargé dans config/init.php</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>