<?php
// index.php - Page d'accueil
require_once 'config/init.php';

// Récupérer les trajets disponibles récents
$popular_trips = [];
$error_message = '';

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    // Récupérer les trajets futurs disponibles
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.pseudo as conducteur_pseudo,
                   c.prix as prix_par_place,
                   0 as note_moyenne,
                   COALESCE(v.marque, 'Véhicule') as marque,
                   COALESCE(v.modele, 'non renseigné') as modele,
                   COALESCE(v.couleur, '') as couleur,
                   COALESCE(v.type_carburant, 'essence') as type_carburant,
                   0 as reservations_count,
                   c.places_disponibles as places_libres
            FROM covoiturage c
            JOIN utilisateur u ON c.id_conducteur = u.utilisateur_id
            LEFT JOIN vehicule v ON c.id_vehicule = v.vehicule_id
            WHERE c.statut IN ('planifie', 'en_cours')
            AND c.date_depart >= CURRENT_DATE
            AND c.places_disponibles > 0
            ORDER BY c.date_depart ASC
            LIMIT 6
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, u.pseudo as conducteur_pseudo,
                   0 as note_moyenne,
                   COALESCE(v.marque, 'Véhicule') as marque,
                   COALESCE(v.modele, 'non renseigné') as modele,
                   COALESCE(v.couleur, '') as couleur,
                   COALESCE(v.energie, 'essence') as type_carburant,
                   0 as reservations_count,
                   c.places_disponibles as places_libres
            FROM covoiturage c
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            WHERE c.statut IN ('planifie', 'en_cours')
            AND c.date_depart >= CURDATE()
            AND c.places_disponibles > 0
            ORDER BY c.date_depart ASC
            LIMIT 6
        ");
    }

    $stmt->execute();
    $popular_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des trajets : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Plateforme de covoiturage écologique</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/home.css">
</head>
<body>
    <!-- Navigation -->
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
                <li><a href="index.php" class="nav-link active">Accueil</a></li>
                <li><a href="trajets.php" class="nav-link">Trajets</a></li>
                <li><a href="comment-ca-marche.php" class="nav-link">Comment ça marche</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="user/dashboard.php" class="nav-link">Mon espace</a></li>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrateur'): ?>
                        <li><a href="admin/dashboard.php" class="nav-link" style="background: #e74c3c; color: white; padding: 8px 15px; border-radius: 20px;">🛠️ Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="nav-link">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="connexion.php" class="nav-link">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Voyagez <span class="text-green">écologique</span>, économisez <span class="text-green">ensemble</span></h1>
            <p class="hero-description">
                <?php if (isLoggedIn()): ?>
                    Bienvenue <?php echo htmlspecialchars($_SESSION['user_pseudo'] ?? 'Utilisateur'); ?> !
                <?php else: ?>
                    Rejoignez la communauté du covoiturage 100% green
                <?php endif; ?>
            </p>

            <div class="cta-buttons">
                <a href="trajets.php" class="btn btn-primary">🔍 Rechercher un trajet</a>
                <?php if (isLoggedIn()): ?>
                    <a href="creer-trajet.php" class="btn btn-secondary">➕ Créer un trajet</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Section Trajets Disponibles -->
    <section class="popular-trips">
        <div class="container">
            <h2 class="section-title">🚗 Trajets disponibles</h2>
            <p class="section-subtitle">Découvrez les prochains trajets proposés par la communauté</p>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php elseif (empty($popular_trips)): ?>
                <div class="empty-state">
                    <h3>Aucun trajet disponible pour le moment</h3>
                    <p>Revenez bientôt pour découvrir de nouveaux trajets !</p>
                    <a href="creer-trajet.php" class="btn btn-primary">Créer le premier trajet</a>
                </div>
            <?php else: ?>
                <div class="trips-grid">
                    <?php foreach ($popular_trips as $trip): ?>
                        <div class="trip-card">
                            <div class="trip-header">
                                <div class="trip-route">
                                    <span class="route-text">
                                        <?= htmlspecialchars($trip['ville_depart']) ?> → <?= htmlspecialchars($trip['ville_arrivee']) ?>
                                    </span>
                                    <span class="status-badge">
                                        <?= $trip['statut'] === 'planifie' ? '📅 Programmé' : '🚀 En cours' ?>
                                    </span>
                                </div>
                            </div>

                            <div class="trip-info">
                                <div class="trip-datetime">
                                    <span class="date">📅 <?= date('d/m/Y', strtotime($trip['date_depart'])) ?></span>
                                    <span class="time">🕒 <?= date('H:i', strtotime($trip['date_depart'])) ?></span>
                                </div>

                                <div class="trip-details">
                                    <div class="detail-item">
                                        <span class="icon">👨‍✈️</span>
                                        <span><?= htmlspecialchars($trip['conducteur_pseudo']) ?></span>
                                        <?php if ($trip['note_moyenne'] > 0): ?>
                                            <span class="rating">⭐ <?= number_format($trip['note_moyenne'], 1) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="detail-item">
                                        <span class="icon">🚗</span>
                                        <span><?= htmlspecialchars($trip['marque'] . ' ' . $trip['modele']) ?></span>
                                        <?php if ($trip['type_carburant'] === 'electrique'): ?>
                                            <span class="eco-badge">⚡</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="detail-item">
                                        <span class="icon">👥</span>
                                        <span><?= $trip['places_libres'] ?> place(s) libre(s)</span>
                                    </div>

                                    <div class="detail-item">
                                        <span class="icon">💰</span>
                                        <span><?= number_format($trip['prix_par_place'], 2) ?> crédits</span>
                                    </div>
                                </div>
                            </div>

                            <div class="trip-actions">
                                <a href="trajet-detail.php?id=<?= $trip['covoiturage_id'] ?>" class="btn btn-outline">
                                    👁️ Voir détails
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <a href="trajet-detail.php?id=<?= $trip['covoiturage_id'] ?>#reserver" class="btn btn-primary">
                                        🎫 Réserver
                                    </a>
                                <?php else: ?>
                                    <a href="connexion.php" class="btn btn-primary">
                                        🔐 Se connecter
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="view-all-section">
                    <a href="trajets.php" class="btn btn-secondary btn-large">
                        🔍 Voir tous les trajets disponibles
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Section Caractéristiques -->
    <section class="about">
        <div class="container">
            <h2 class="section-title">Pourquoi EcoRide ?</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔋</div>
                    <h3 class="feature-title">100% Électrique</h3>
                    <p class="feature-description">Priorité aux véhicules électriques pour réduire votre empreinte carbone</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3 class="feature-title">Économique</h3>
                    <p class="feature-description">Partagez les frais et économisez sur tous vos trajets</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3 class="feature-title">Écologique</h3>
                    <p class="feature-description">Contribuez à la protection de l'environnement</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div>
                <h4>🚗🌱 EcoRide</h4>
                <p>La plateforme de covoiturage écologique</p>
            </div>
            
            <div>
                <h4>Liens</h4>
                <ul class="footer-links">
                    <li><a href="trajets.php">Trajets</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div>
                <h4>À propos</h4>
                <ul class="footer-links">
                    <li><a href="mentions-legales.php">Mentions légales</a></li>
                    <li><a href="cgv.php">CGV</a></li>
                    <li><a href="confidentialite.php">Confidentialité</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2025 EcoRide</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script src="js/home.js"></script>
</body>
</html>