<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EcoRide</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .welcome {
            background: linear-gradient(135deg, #2ECC71, #27AE60);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            color: #2ECC71;
            font-weight: bold;
        }
        .logout-btn {
            background: #E74C3C;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="welcome">
            <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_pseudo']); ?> ! 👋</h1>
            <p>Vous êtes connecté en tant que : <?php echo htmlspecialchars($_SESSION['user_role']); ?></p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $_SESSION['user_credits']; ?></div>
                <div>Crédits disponibles</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">0</div>
                <div>Trajets effectués</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">0</div>
                <div>CO2 économisé</div>
            </div>
        </div>
        
        <h2>Informations de session</h2>
        <pre style="background: #f0f0f0; padding: 15px; border-radius: 5px;">
<?php print_r($_SESSION); ?>
        </pre>
        
        <br>
        <a href="../logout.php" class="logout-btn">Se déconnecter</a>
    </div>
</body>
</html>