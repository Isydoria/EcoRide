<?php
/**
 * trajets.php - Page de recherche et affichage des covoiturages
 * Cette page permet aux visiteurs de rechercher des trajets disponibles
 */

// Démarrer la session pour vérifier si l'utilisateur est connecté
session_start();

// Vérifier si l'utilisateur est connecté (pour afficher le bon menu)
$isLoggedIn = isset($_SESSION['user_id']);
$userPseudo = $_SESSION['user_pseudo'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher un trajet - EcoRide</title>
    
    <!-- Police Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Fichiers CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/trajets.css">
</head>
<body>
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
                <li><a href="trajets.php" class="nav-link active">Trajets</a></li>
                <li><a href="comment-ca-marche.php" class="nav-link">Comment ça marche</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="user/dashboard.php" class="nav-link">Mon espace (<?php echo htmlspecialchars($userPseudo); ?>)</a></li>
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

    <!-- Contenu principal -->
    <main class="trajets-container">
        <!-- Section de recherche -->
        <section class="search-section">
            <div class="container">
                <h1>🔍 Trouvez votre covoiturage écologique</h1>
                <p class="subtitle">Recherchez parmi tous les trajets disponibles</p>
                
                <!-- Formulaire de recherche -->
                <form id="searchForm" class="search-form-trajets">
                    <div class="search-inputs">
                        <div class="form-group">
                            <label for="ville_depart" class="form-label">Ville de départ</label>
                            <input 
                                type="text" 
                                id="ville_depart" 
                                name="ville_depart" 
                                class="form-input" 
                                placeholder="Ex: Paris"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="ville_arrivee" class="form-label">Ville d'arrivée</label>
                            <input 
                                type="text" 
                                id="ville_arrivee" 
                                name="ville_arrivee" 
                                class="form-input" 
                                placeholder="Ex: Lyon"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="date_depart" class="form-label">Date de départ</label>
                            <input 
                                type="date" 
                                id="date_depart" 
                                name="date_depart" 
                                class="form-input"
                                min="<?php echo date('Y-m-d'); ?>"
                                required
                            >
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            🔍 Rechercher
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Section des filtres (US4) -->
        <section class="filters-section" id="filtersSection" style="display: none;">
            <div class="container">
                <h3>🎯 Affiner votre recherche</h3>
                <div class="filters-grid">
                    <!-- Filtre écologique -->
                    <div class="filter-item">
                        <label class="filter-label">
                            <input type="checkbox" id="filter-eco" name="ecologique">
                            <span>🌱 Trajets écologiques uniquement</span>
                        </label>
                    </div>
                    
                    <!-- Filtre prix max -->
                    <div class="filter-item">
                        <label class="filter-label">Prix maximum (€)</label>
                        <input 
                            type="number" 
                            id="filter-prix" 
                            name="prix_max" 
                            class="filter-input" 
                            placeholder="50"
                            min="0"
                            step="5"
                        >
                    </div>
                    
                    <!-- Filtre durée max -->
                    <div class="filter-item">
                        <label class="filter-label">Durée maximum (heures)</label>
                        <input 
                            type="number" 
                            id="filter-duree" 
                            name="duree_max" 
                            class="filter-input" 
                            placeholder="5"
                            min="0"
                            step="0.5"
                        >
                    </div>
                    
                    <!-- Filtre note minimum -->
                    <div class="filter-item">
                        <label class="filter-label">Note minimum du conducteur</label>
                        <select id="filter-note" name="note_min" class="filter-input">
                            <option value="">Toutes les notes</option>
                            <option value="1">⭐ 1+ étoile</option>
                            <option value="2">⭐⭐ 2+ étoiles</option>
                            <option value="3">⭐⭐⭐ 3+ étoiles</option>
                            <option value="4">⭐⭐⭐⭐ 4+ étoiles</option>
                            <option value="5">⭐⭐⭐⭐⭐ 5 étoiles</option>
                        </select>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="applyFilters()">
                        Appliquer les filtres
                    </button>
                </div>
            </div>
        </section>

        <!-- Section des résultats -->
        <section class="results-section">
            <div class="container">
                <!-- Message de statut -->
                <div id="statusMessage" class="status-message">
                    <p>👆 Entrez vos critères de recherche pour trouver un trajet</p>
                </div>
                
                <!-- Grille des résultats -->
                <div id="trajetsGrid" class="trajets-grid">
                    <!-- Les cartes de trajets seront ajoutées ici par JavaScript -->
                </div>
                
                <!-- Message si aucun résultat -->
                <div id="noResults" class="no-results" style="display: none;">
                    <h3>😕 Aucun trajet trouvé</h3>
                    <p>Essayez de modifier vos critères de recherche ou consultez d'autres dates.</p>
                    <div id="alternativeDates"></div>
                </div>
            </div>
        </section>
    </main>

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
                    <?php if (!$isLoggedIn): ?>
                        <li><a href="inscription.php">Inscription</a></li>
                    <?php endif; ?>
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

    <!-- Scripts JavaScript -->
    <script src="js/main.js"></script>
    <script src="js/trajets.js"></script>
</body>
</html>