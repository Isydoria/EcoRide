<?php
/**
 * employee/dashboard.php - Interface employé pour modération des avis
 * US12 : Espace employé pour validation des avis et gestion incidents
 */

session_start();
require_once '../config/init.php';

// Vérifier si l'utilisateur est connecté et est employé
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'employe' && $_SESSION['user_role'] !== 'administrateur')) {
    header('Location: ../connexion.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_pseudo = $_SESSION['user_pseudo'] ?? 'Employé';

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    // Récupérer les avis en attente de validation - Compatible MySQL/PostgreSQL (après migration)
    if ($isPostgreSQL) {
        // PostgreSQL : evaluateur_id, evalue_id (après migration, colonnes statut ajoutées)
        $stmt = $pdo->prepare("
            SELECT a.*,
                   u1.pseudo as auteur_pseudo,
                   u1.email as auteur_email,
                   u2.pseudo as destinataire_pseudo,
                   u2.email as destinataire_email,
                   c.ville_depart,
                   c.ville_arrivee,
                   c.date_depart,
                   c.covoiturage_id as trajet_id
            FROM avis a
            JOIN utilisateur u1 ON a.evaluateur_id = u1.utilisateur_id
            JOIN utilisateur u2 ON a.evalue_id = u2.utilisateur_id
            LEFT JOIN covoiturage c ON a.covoiturage_id = c.covoiturage_id
            WHERE a.statut = 'en_attente'
            ORDER BY a.created_at DESC
        ");
    } else {
        // MySQL : auteur_id, destinataire_id
        $stmt = $pdo->prepare("
            SELECT a.*,
                   u1.pseudo as auteur_pseudo,
                   u1.email as auteur_email,
                   u2.pseudo as destinataire_pseudo,
                   u2.email as destinataire_email,
                   c.ville_depart,
                   c.ville_arrivee,
                   c.date_depart,
                   c.covoiturage_id as trajet_id
            FROM avis a
            JOIN utilisateur u1 ON a.auteur_id = u1.utilisateur_id
            JOIN utilisateur u2 ON a.destinataire_id = u2.utilisateur_id
            LEFT JOIN covoiturage c ON a.covoiturage_id = c.covoiturage_id
            WHERE a.statut = 'en_attente'
            ORDER BY a.created_at DESC
        ");
    }
    $stmt->execute();
    $pending_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les statistiques - Compatible MySQL/PostgreSQL (après migration, même structure)
    $stmt = $pdo->prepare("
        SELECT
            COUNT(CASE WHEN statut = 'en_attente' THEN 1 END) as avis_en_attente,
            COUNT(CASE WHEN statut = 'valide' OR statut = 'publie' THEN 1 END) as avis_valides,
            COUNT(CASE WHEN statut = 'refuse' THEN 1 END) as avis_refuses
        FROM avis
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erreur de base de données : " . $e->getMessage();
}

// Gestion des actions POST - Compatible MySQL/PostgreSQL (après migration)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['avis_id'])) {
    $action = $_POST['action'];
    $avis_id = intval($_POST['avis_id']);
    $nouveau_statut = ($action === 'approve') ? 'valide' : 'refuse';

    try {
        $stmt = $pdo->prepare("
            UPDATE avis
            SET statut = :statut,
                valide_par = :employe_id,
                date_validation = NOW()
            WHERE avis_id = :avis_id
        ");
        $stmt->execute([
            'statut' => $nouveau_statut,
            'employe_id' => $user_id,
            'avis_id' => $avis_id
        ]);

        $message = ($action === 'approve') ? 'Avis approuvé avec succès' : 'Avis refusé';
        header("Location: dashboard.php?success=" . urlencode($message));
        exit();
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

$success_message = $_GET['success'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Employé - EcoRide</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fa;
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            background: #2c3e50;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            text-decoration: none;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 20px;
            align-items: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-link.active {
            background: #3498db;
        }

        /* Container principal */
        .employee-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Header */
        .employee-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.2);
        }

        .employee-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .employee-header p {
            font-size: 18px;
            opacity: 0.95;
        }

        /* Grille de statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.12);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 16px;
            font-weight: 500;
        }

        /* Onglets */
        .tabs {
            display: flex;
            background: white;
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .tab {
            flex: 1;
            background: transparent;
            border: none;
            padding: 15px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: #2c3e50;
        }

        .tab:hover {
            background: #f8f9fa;
        }

        .tab.active {
            background: #3498db;
            color: white;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Cards d'avis */
        .review-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.06);
            border-left: 4px solid #3498db;
            transition: all 0.3s;
        }

        .review-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .review-header {
            margin-bottom: 20px;
        }

        .review-meta {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .review-participants {
            font-size: 16px;
            margin: 10px 0;
        }

        .review-participants strong {
            color: #2c3e50;
        }

        .review-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }

        .stars {
            color: #f39c12;
            font-size: 20px;
        }

        .review-content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }

        .review-content strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 10px;
        }

        .review-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-approve {
            background: #2ecc71;
            color: white;
        }

        .btn-approve:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }

        .btn-reject {
            background: #e74c3c;
            color: white;
        }

        .btn-reject:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        /* Messages d'alerte */
        .alert {
            padding: 18px 24px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 16px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* État vide */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #7f8c8d;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 72px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-direction: column;
            }

            .review-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-wrapper">
            <a href="../index.php" class="logo">
                <span>🚗🌱 EcoRide</span>
            </a>

            <ul class="nav-menu">
                <li><a href="../index.php" class="nav-link">Accueil</a></li>
                <li><a href="../trajets.php" class="nav-link">Trajets</a></li>
                <?php if ($_SESSION['user_role'] === 'administrateur'): ?>
                    <li><a href="../admin/dashboard.php" class="nav-link">🛠️ Admin</a></li>
                <?php endif; ?>
                <li><a href="dashboard.php" class="nav-link active">👷 Employé</a></li>
                <li><a href="../logout.php" class="nav-link">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="employee-container">
        <!-- Header -->
        <div class="employee-header">
            <h1>👷 Dashboard Employé</h1>
            <p>Bienvenue <?= htmlspecialchars($user_pseudo) ?> - Modération des avis et gestion des incidents</p>
        </div>

        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['avis_en_attente'] ?? 0 ?></div>
                <div class="stat-label">⏳ Avis en attente</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['avis_valides'] ?? 0 ?></div>
                <div class="stat-label">✅ Avis validés</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['avis_refuses'] ?? 0 ?></div>
                <div class="stat-label">❌ Avis refusés</div>
            </div>
        </div>

        <!-- Section principale -->
        <div class="review-section">
            <h2 style="margin-bottom: 25px; color: #2c3e50;">📝 Avis en attente de modération</h2>

            <?php if (empty($pending_reviews)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>Aucun avis en attente</h3>
                    <p>Tous les avis ont été traités. Excellent travail !</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-meta">
                                📍 Trajet : <?= htmlspecialchars($review['ville_depart'] ?? 'N/A') ?> → 
                                <?= htmlspecialchars($review['ville_arrivee'] ?? 'N/A') ?>
                                <?php if ($review['date_depart']): ?>
                                    | 📅 <?= date('d/m/Y', strtotime($review['date_depart'])) ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="review-participants">
                                <strong><?= htmlspecialchars($review['auteur_pseudo']) ?></strong> évalue
                                <strong><?= htmlspecialchars($review['destinataire_pseudo']) ?></strong>
                            </div>

                            <div class="review-rating">
                                <span class="stars">
                                    <?php
                                    $rating = intval($review['note']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo ($i <= $rating) ? '⭐' : '☆';
                                    }
                                    ?>
                                </span>
                                <span style="color: #7f8c8d;">(<?= $rating ?>/5)</span>
                            </div>
                        </div>

                        <?php if (!empty($review['commentaire'])): ?>
                            <div class="review-content">
                                <strong>Commentaire :</strong>
                                <?= nl2br(htmlspecialchars($review['commentaire'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="review-actions">
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="avis_id" value="<?= $review['avis_id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-approve" 
                                        onclick="return confirm('Approuver cet avis ?')">
                                    ✅ Approuver
                                </button>
                            </form>

                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="avis_id" value="<?= $review['avis_id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-reject" 
                                        onclick="return confirm('Refuser cet avis ?')">
                                    ❌ Refuser
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>