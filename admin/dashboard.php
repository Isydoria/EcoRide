<?php
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ../connexion.php');
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

    // Statistiques générales
    $stats = [];

    // Nombre total d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateur");
    $stats['total_users'] = $stmt->fetchColumn();

    // Nombre de trajets (covoiturage)
    $stmt = $pdo->query("SELECT COUNT(*) FROM covoiturage");
    $stats['total_trips'] = $stmt->fetchColumn();

    // Total des crédits de la plateforme
    $stmt = $pdo->query("SELECT SUM(credit) FROM utilisateur");
    $stats['total_credits'] = $stmt->fetchColumn() ?? 0;

    // Trajets par statut
    $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM covoiturage GROUP BY statut");
    $trip_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Derniers utilisateurs inscrits
    $stmt = $pdo->prepare("SELECT pseudo, email, created_at, statut FROM utilisateur ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - EcoRide</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #2ECC71; }
        .stat-label { color: #7f8c8d; margin-top: 5px; }
        .users-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .users-table th, .users-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        .users-table th { background: #34495e; color: white; }
        .status-badge { padding: 4px 8px; border-radius: 4px; color: white; font-size: 0.8em; }
        .status-actif { background: #2ECC71; }
        .status-suspendu { background: #e74c3c; }
        .admin-header { background: #2c3e50; color: white; padding: 20px 0; margin: -20px -20px 20px; }
        .admin-nav { display: flex; gap: 20px; align-items: center; }
        .admin-nav a { color: white; text-decoration: none; padding: 10px 15px; border-radius: 5px; }
        .admin-nav a:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-container">
            <div class="admin-nav">
                <h1>🛠️ Administration EcoRide</h1>
                <a href="../user/dashboard.php">← Retour utilisateur</a>
                <a href="../deconnexion.php">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>

            <h2>📊 Statistiques générales</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_users'] ?></div>
                    <div class="stat-label">Utilisateurs inscrits</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_trips'] ?></div>
                    <div class="stat-label">Trajets créés</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_credits']) ?></div>
                    <div class="stat-label">Crédits totaux</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $trip_stats['planifie'] ?? 0 ?></div>
                    <div class="stat-label">Trajets planifiés</div>
                </div>
            </div>

            <h2>👥 Derniers utilisateurs inscrits</h2>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Pseudo</th>
                        <th>Email</th>
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
                        <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $user['statut'] ?>">
                                <?= ucfirst($user['statut']) ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="toggleUserStatus('<?= $user['pseudo'] ?>')">
                                <?= $user['statut'] === 'actif' ? 'Suspendre' : 'Activer' ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>

    <!-- Footer Admin -->
    <footer style="background: #34495e; color: white; padding: 30px 0; margin-top: 50px;">
        <div class="admin-container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-bottom: 20px;">
                <div>
                    <h4 style="color: #e74c3c; margin-bottom: 15px;">🛠️ Administration EcoRide</h4>
                    <p style="color: #bdc3c7;">Interface d'administration pour la gestion de la plateforme de covoiturage.</p>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Actions rapides</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><a href="dashboard.php" style="color: #bdc3c7; text-decoration: none;">📊 Statistiques</a></li>
                        <li style="margin-bottom: 8px;"><a href="../user/dashboard.php" style="color: #bdc3c7; text-decoration: none;">👤 Mode utilisateur</a></li>
                        <li style="margin-bottom: 8px;"><a href="../index.php" style="color: #bdc3c7; text-decoration: none;">🏠 Accueil site</a></li>
                    </ul>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Support technique</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><span style="color: #bdc3c7;">📧 admin@ecoride.fr</span></li>
                        <li style="margin-bottom: 8px;"><a href="https://github.com/Isydoria/EcoRide" style="color: #bdc3c7; text-decoration: none;" target="_blank">🔗 GitHub</a></li>
                        <li style="margin-bottom: 8px;"><span style="color: #bdc3c7;">🚀 Railway</span></li>
                    </ul>
                </div>
            </div>

            <div style="border-top: 1px solid #2c3e50; padding-top: 20px; text-align: center;">
                <p style="color: #bdc3c7; margin: 0;">
                    © 2025 EcoRide - Interface d'administration |
                    Connecté en tant que : <strong><?= htmlspecialchars($_SESSION['user_pseudo']) ?></strong>
                </p>
            </div>
        </div>
    </footer>

    <script>
        function toggleUserStatus(pseudo) {
            if (confirm('Voulez-vous vraiment modifier le statut de cet utilisateur ?')) {
                // Ici on pourrait ajouter l'appel AJAX pour modifier le statut
                alert('Fonctionnalité en cours de développement');
            }
        }
    </script>
</body>
</html>