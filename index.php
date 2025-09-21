<?php
// index.php - Page d'accueil
require_once 'config/init.php';
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
                    Bienvenue <?php echo htmlspecialchars($_SESSION['user_pseudo']); ?> !
                <?php else: ?>
                    Rejoignez la communauté du covoiturage 100% green
                <?php endif; ?>
            </p>
            
            <form class="search-form" action="trajets.php" method="GET">
                <div class="search-wrapper">
                    <div class="form-group">
                        <input type="text" id="departure" name="departure" placeholder="📍 Ville de départ" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <input type="text" id="arrival" name="arrival" placeholder="📍 Ville d'arrivée" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <input type="date" id="date" name="date" required class="form-input">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
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