<?php
/**
 * FICHIER: connexion.php
 * Page de connexion
 */
require_once 'config/init.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    redirect('/user/dashboard.php');
}

// Générer le token CSRF
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EcoRide</title>
    
    <!-- Police Inter de Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Liens vers les fichiers CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-page">
    <!-- Navigation simplifiée -->
    <nav class="navbar-simple">
        <div class="nav-wrapper-simple">
            <a href="index.php" class="logo">
                <span>🚗🌱 EcoRide</span>
            </a>
            <a href="index.php" class="back-link">← Retour à l'accueil</a>
        </div>
    </nav>

    <!-- Container de connexion -->
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title">Connexion</h1>
                <p class="auth-subtitle">Connectez-vous pour accéder à votre espace</p>
            </div>

            <!-- Messages d'erreur/succès (cachés par défaut) -->
            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>

            <!-- Formulaire de connexion -->
            <form class="auth-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="votre@email.com"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <div class="forgot-password">
                    <a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn btn-primary">
                    Se connecter
                </button>
            </form>

            <div class="auth-links">
                <p>Vous n'avez pas encore de compte ?</p>
                <a href="inscription.php">Créer un compte gratuitement</a>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="js/main.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>