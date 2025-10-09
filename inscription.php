<?php
/**
 * FICHIER: inscription.php
 * Page d'inscription
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
    <title>Inscription - EcoRide</title>
    
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

    <!-- Container d'inscription -->
    <div class="auth-container">
        <div class="auth-card signup">
            <div class="auth-header">
                <h1 class="auth-title">Créer un compte</h1>
                <p class="auth-subtitle">Rejoignez la communauté EcoRide</p>
            </div>

            <!-- Bonus d'inscription -->
            <div class="bonus-info">
                🎁 <span>20 crédits offerts</span> à l'inscription !
            </div>

            <!-- Messages d'erreur/succès (cachés par défaut) -->
            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>

            <!-- Formulaire d'inscription -->
            <form class="auth-form" id="signupForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="pseudo" class="form-label">
                        Pseudo <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="pseudo" 
                        name="pseudo" 
                        class="form-input" 
                        placeholder="Votre pseudo"
                        required
                        minlength="3"
                        maxlength="20"
                        title="Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores"
                    >
                    <small class="form-hint">3-20 caractères : lettres, chiffres, - ou _</small>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        Adresse email <span class="required">*</span>
                    </label>
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
                    <label for="password" class="form-label">
                        Mot de passe <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                        minlength="8"
                    >
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                    <p class="password-hint">
                        Minimum 8 caractères, incluez des chiffres et des majuscules pour plus de sécurité
                    </p>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">
                        Confirmer le mot de passe <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="checkbox-group">
                    <input 
                        type="checkbox" 
                        id="terms" 
                        name="terms" 
                        required
                    >
                    <label for="terms">
                        J'accepte les <a href="cgv.php" target="_blank">conditions d'utilisation</a> et la 
                        <a href="confidentialite.php" target="_blank">politique de confidentialité</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    Créer mon compte
                </button>
            </form>

            <div class="auth-links">
                <p>Vous avez déjà un compte ?</p>
                <a href="connexion.php">Se connecter</a>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="js/main.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>