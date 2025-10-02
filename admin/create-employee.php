<?php
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ../connexion.php');
    exit;
}

require_once '../config/init.php';

$success = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = cleanInput($_POST['pseudo'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($pseudo) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } elseif (!isValidEmail($email)) {
        $error = "Email invalide";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        try {
            $pdo = db();
            
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Cet email est déjà utilisé";
            } else {
                // Créer l'employé
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateur (pseudo, email, password, role, credit, statut) 
                    VALUES (?, ?, ?, 'employe', 100, 'actif')
                ");
                $stmt->execute([$pseudo, $email, $hash]);
                
                $success = "Employé créé avec succès !";
                
                // Redirection après 2 secondes
                header("refresh:2;url=dashboard.php");
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la création : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un employé - Administration EcoRide</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            margin-bottom: 40px;
        }
        
        .admin-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2ecc71;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #2ecc71;
            color: white;
        }
        
        .btn-primary:hover {
            background: #27ae60;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
        
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .help-text {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .strength-weak { background: #e74c3c; width: 33%; }
        .strength-medium { background: #f39c12; width: 66%; }
        .strength-strong { background: #2ecc71; width: 100%; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-nav">
            <h1>🛠️ Créer un employé</h1>
            <div>
                <a href="dashboard.php">← Retour au tableau de bord</a>
                <a href="../deconnexion.php">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <div class="form-container">
            <h2>Nouveau compte employé</h2>
            <p class="help-text">Les employés peuvent modérer les avis et gérer les incidents.</p>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="pseudo">Pseudo de l'employé</label>
                    <input type="text" 
                           id="pseudo" 
                           name="pseudo" 
                           placeholder="Ex: marie_employe" 
                           value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email professionnel</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="employe@ecoride.fr"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                    <span class="help-text">Utilisez une adresse email @ecoride.fr de préférence</span>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Minimum 6 caractères"
                           onkeyup="checkPasswordStrength()"
                           required>
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           placeholder="Retapez le mot de passe"
                           required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        Créer l'employé
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        Annuler
                    </a>
                </div>
            </form>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                <h3>Informations importantes :</h3>
                <ul class="help-text">
                    <li>L'employé recevra 100 crédits à la création du compte</li>
                    <li>Le compte sera immédiatement actif</li>
                    <li>L'employé pourra modérer les avis et gérer les incidents</li>
                    <li>Communiquez les identifiants de manière sécurisée à l'employé</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('password-strength');
            
            if (password.length < 6) {
                strengthBar.className = 'password-strength strength-weak';
            } else if (password.length < 10 || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                strengthBar.className = 'password-strength strength-medium';
            } else {
                strengthBar.className = 'password-strength strength-strong';
            }
        }
        
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas !');
                return false;
            }
            
            if (password.length < 6) {
                alert('Le mot de passe doit contenir au moins 6 caractères !');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>