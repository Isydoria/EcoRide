<?php
/**
 * trajet-detail.php - Page de détail d'un covoiturage (US5)
 * Cette page affiche toutes les informations d'un trajet spécifique
 */

// Démarrer la session pour vérifier si l'utilisateur est connecté
session_start();

// Vérifier si un ID de trajet est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: trajets.php');
    exit();
}

$trajet_id = intval($_GET['id']); // Sécuriser l'ID

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;
$userPseudo = $_SESSION['user_pseudo'] ?? '';
$userCredits = $_SESSION['user_credits'] ?? 0;
$userRole = $_SESSION['user_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail du trajet - EcoRide</title>
    
    <!-- Police Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Fichiers CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/trajet-detail.css">
</head>
<body>
    <?php if (!$isLoggedIn): ?>
    <!-- Bannière flottante pour les non-connectés -->
    <div id="floatingBanner" class="floating-banner">
        <div class="banner-content">
            <span>💡 Connectez-vous pour réserver ce trajet et bénéficier de toutes les fonctionnalités</span>
            <button onclick="closeFloatingBanner()" class="banner-close">×</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-wrapper">
            <a href="index.php" class="logo">
                <span>🚗🌱 EcoRide</span>
            </a>
            
            <button class="mobile-menu-btn" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="nav-link">Accueil</a></li>
                <li><a href="trajets.php" class="nav-link">Trajets</a></li>
                <li><a href="comment-ca-marche.php" class="nav-link">Comment ça marche</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="user/dashboard.php" class="nav-link">Mon compte (<?php echo htmlspecialchars($userPseudo); ?>)</a></li>
                    <li><a href="logout.php" class="nav-link">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="connexion.php" class="nav-link">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="detail-container">
        <!-- Bouton retour -->
        <div class="container">
            <a href="trajets.php" class="btn-back">
                ← Retour aux résultats
            </a>
        </div>

        <!-- Zone de chargement -->
        <div id="loadingSection" class="loading-section">
            <div class="loading-spinner"></div>
            <p>Chargement des détails du trajet...</p>
        </div>

        <!-- Contenu du trajet (caché au début, affiché après chargement) -->
        <div id="trajetContent" class="trajet-content" style="display: none;">
            <div class="container">
                <div class="detail-grid">
                    <!-- Colonne principale - Informations du trajet -->
                    <div class="main-column">
                        <!-- En-tête du trajet -->
                        <div class="trajet-header-detail">
                            <div id="ecoIndicator" class="eco-indicator hidden"></div>
                            <h1 class="trajet-title">
                                <span id="villeDepart">...</span> → <span id="villeArrivee">...</span>
                            </h1>
                            <div class="trajet-datetime">
                                📅 <span id="dateDepart">...</span> à <span id="heureDepart">...</span>
                            </div>
                        </div>

                        <!-- Carte du trajet -->
                        <div class="trajet-card-detail">
                            <!-- Section Itinéraire -->
                            <section class="detail-section">
                                <h2 class="section-title">📍 Itinéraire</h2>
                                <div class="route-details">
                                    <div class="route-stop">
                                        <div class="route-icon">🚗</div>
                                        <div class="route-info">
                                            <strong>Départ</strong>
                                            <p id="adresseDepart">...</p>
                                            <small id="heureDepartDetail">...</small>
                                        </div>
                                    </div>
                                    <div class="route-line"></div>
                                    <div class="route-stop">
                                        <div class="route-icon">🏁</div>
                                        <div class="route-info">
                                            <strong>Arrivée</strong>
                                            <p id="adresseArrivee">...</p>
                                            <small id="heureArriveeDetail">...</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="duration-info">
                                    ⏱️ Durée estimée : <span id="dureeTrajet">...</span>
                                </div>
                            </section>

                            <!-- Section Véhicule -->
                            <section class="detail-section">
                                <h2 class="section-title">🚗 Véhicule</h2>
                                <div class="vehicle-info">
                                    <div class="vehicle-main">
                                        <span id="vehiculeMarque">...</span> <span id="vehiculeModele">...</span>
                                    </div>
                                    <div class="vehicle-details">
                                        <span class="vehicle-tag" id="vehiculeCouleur">...</span>
                                        <span class="vehicle-tag" id="vehiculeEnergie">...</span>
                                        <span class="vehicle-tag">🪑 <span id="vehiculePlaces">...</span> places</span>
                                    </div>
                                </div>
                            </section>

                            <!-- Section Préférences du conducteur -->
                            <section class="detail-section">
                                <h2 class="section-title">📋 Préférences du conducteur</h2>
                                <div id="preferencesContainer" class="preferences-grid">
                                    <!-- Les préférences seront ajoutées ici par JavaScript -->
                                </div>
                            </section>

                            <!-- Section Avis -->
                            <section class="detail-section">
                                <h2 class="section-title">⭐ Avis sur le conducteur</h2>
                                <div id="avisContainer" class="avis-container">
                                    <!-- Les avis seront ajoutés ici par JavaScript -->
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- Colonne latérale - Réservation et conducteur -->
                    <div class="sidebar-column">
                        <!-- Carte du conducteur -->
                        <div class="driver-card-detail">
                            <div class="driver-header">
                                <div class="driver-avatar-large" id="driverAvatar">?</div>
                                <div class="driver-info-detail">
                                    <h3 id="driverName">...</h3>
                                    <div class="driver-rating-detail" id="driverRating">
                                        <!-- Les étoiles seront ajoutées ici -->
                                    </div>
                                </div>
                            </div>
                            <div class="driver-stats">
                                <div class="stat-item">
                                    <span class="stat-value" id="totalTrajets">0</span>
                                    <span class="stat-label">Trajets</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value" id="memberSince">...</span>
                                    <span class="stat-label">Membre depuis</span>
                                </div>
                            </div>
                        </div>

                        <!-- Carte de réservation -->
                        <div class="booking-card">
                            <div class="price-section">
                                <div class="price-large">
                                    <span id="prixTrajet">...</span>€
                                    <small>/ place</small>
                                </div>
                                <div class="places-remaining">
                                    <span id="placesDisponibles">...</span> place(s) disponible(s)
                                </div>
                            </div>

                            <?php if ($isLoggedIn): ?>
                                <!-- Utilisateur connecté -->
                                <div class="user-credits">
                                    💰 Vos crédits : <strong><?php echo $userCredits; ?></strong>
                                </div>
                                
                                <button id="btnParticiper" class="btn btn-primary btn-large" onclick="participerTrajet()">
                                    Réserver ce trajet
                                </button>
                                
                                <p class="booking-info">
                                    ℹ️ La réservation débitera <span id="coutTotal">...</span> crédits de votre compte
                                </p>
                            <?php else: ?>
                                <!-- Utilisateur non connecté -->
                                <div class="login-required-box">
                                    <div class="login-icon">🔒</div>
                                    <h4>Connectez-vous pour réserver</h4>
                                    <p class="login-prompt">
                                        Pour réserver ce trajet et accéder à toutes les fonctionnalités, vous devez avoir un compte EcoRide.
                                    </p>
                                    <a href="connexion.php?redirect=trajet-detail.php?id=<?php echo $trajet_id; ?>" class="btn btn-primary btn-large">
                                        Se connecter
                                    </a>
                                    <div class="signup-prompt">
                                        <p>Pas encore de compte ?</p>
                                        <a href="inscription.php" class="btn btn-secondary">
                                            Créer un compte gratuitement
                                        </a>
                                        <p class="bonus-info">🎁 <strong>20 crédits offerts</strong> à l'inscription !</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message d'erreur si le trajet n'existe pas -->
        <div id="errorSection" class="error-section" style="display: none;">
            <div class="container">
                <div class="error-card">
                    <h2>❌ Trajet introuvable</h2>
                    <p>Le trajet que vous recherchez n'existe pas ou n'est plus disponible.</p>
                    <a href="trajets.php" class="btn btn-primary">Retour aux trajets</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de confirmation de réservation -->
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Confirmer la réservation</h3>
            <p>Êtes-vous sûr de vouloir réserver ce trajet ?</p>
            <p class="modal-info">
                <strong>Coût :</strong> <span id="modalCout">...</span> crédits<br>
                <strong>Trajet :</strong> <span id="modalTrajet">...</span><br>
                <strong>Date :</strong> <span id="modalDate">...</span>
            </p>
            <div class="modal-buttons">
                <button class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button class="btn btn-primary" onclick="confirmerReservation()">Confirmer</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div>
                <h4>🚗🌱 EcoRide</h4>
                <p>La plateforme de covoiturage écologique</p>
            </div>
            
            <div>
                <h4>Liens rapides</h4>
                <ul class="footer-links">
                    <li><a href="trajets.php">Trajets</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div>
                <h4>Informations</h4>
                <ul class="footer-links">
                    <li><a href="#">Mentions légales</a></li>
                    <li><a href="#">CGV</a></li>
                    <li><a href="#">Confidentialité</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2025 EcoRide - Tous droits réservés</p>
        </div>
    </footer>

    <!-- Données pour JavaScript -->
    <script>
        // Passer l'ID du trajet et les infos utilisateur au JavaScript
        const trajetId = <?php echo $trajet_id; ?>;
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const userCredits = <?php echo $userCredits; ?>;
        const userId = <?php echo $userId ? $userId : 'null'; ?>;
    </script>

    <!-- Scripts JavaScript -->
    <script src="js/main.js"></script>
    <script src="js/trajet-detail.js"></script>
</body>
</html>
