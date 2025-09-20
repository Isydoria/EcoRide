<?php
/**
 * test-search-api.php
 * Fichier pour tester l'API de recherche des trajets
 */

// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test API Search Trajets</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1000px; 
            margin: 50px auto; 
            padding: 20px;
        }
        .test-section {
            background: #f5f5f5;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success { color: green; }
        .error { color: red; }
        pre { 
            background: #333; 
            color: #fff; 
            padding: 15px; 
            border-radius: 5px;
            overflow-x: auto;
        }
        button {
            background: #2ECC71;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #27AE60; }
    </style>
</head>
<body>
    <h1>🔧 Test de l'API de recherche des trajets</h1>

    <!-- Test 1: Connexion DB -->
    <div class="test-section">
        <h2>1. Test de connexion à la base de données</h2>
        <?php
        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=ecoride_db;charset=utf8mb4',
                'root',
                ''
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo '<p class="success">✅ Connexion à la base de données réussie</p>';
            
            // Afficher le nombre de trajets
            $count = $pdo->query("SELECT COUNT(*) FROM trajets")->fetchColumn();
            echo "<p>Nombre de trajets dans la base : <strong>$count</strong></p>";
            
        } catch(PDOException $e) {
            echo '<p class="error">❌ Erreur de connexion : ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <!-- Test 2: Trajets disponibles -->
    <div class="test-section">
        <h2>2. Trajets disponibles dans les prochains jours</h2>
        <?php
        if (isset($pdo)) {
            try {
                $sql = "
                    SELECT 
                        t.id_trajet,
                        t.ville_depart,
                        t.ville_arrivee,
                        t.date_depart,
                        t.heure_depart,
                        t.places_disponibles,
                        u.pseudo as conducteur
                    FROM 
                        trajets t
                        INNER JOIN utilisateurs u ON t.id_conducteur = u.id_utilisateur
                    WHERE 
                        t.date_depart >= CURDATE()
                        AND t.statut = 'planifie'
                    ORDER BY 
                        t.date_depart ASC
                    LIMIT 5
                ";
                
                $stmt = $pdo->query($sql);
                $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($trajets) > 0) {
                    echo '<table border="1" cellpadding="5" style="width:100%; border-collapse: collapse;">';
                    echo '<tr><th>ID</th><th>Départ</th><th>Arrivée</th><th>Date</th><th>Heure</th><th>Places</th><th>Conducteur</th></tr>';
                    
                    foreach ($trajets as $trajet) {
                        echo '<tr>';
                        echo '<td>' . $trajet['id_trajet'] . '</td>';
                        echo '<td>' . $trajet['ville_depart'] . '</td>';
                        echo '<td>' . $trajet['ville_arrivee'] . '</td>';
                        echo '<td>' . $trajet['date_depart'] . '</td>';
                        echo '<td>' . $trajet['heure_depart'] . '</td>';
                        echo '<td>' . $trajet['places_disponibles'] . '</td>';
                        echo '<td>' . $trajet['conducteur'] . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p class="error">Aucun trajet disponible dans la base</p>';
                }
                
            } catch(Exception $e) {
                echo '<p class="error">Erreur : ' . $e->getMessage() . '</p>';
            }
        }
        ?>
    </div>

    <!-- Test 3: Test de l'API -->
    <div class="test-section">
        <h2>3. Test de l'API search-trajets.php</h2>
        
        <form id="testForm">
            <label>Ville de départ : 
                <input type="text" id="depart" value="Paris" />
            </label><br><br>
            
            <label>Ville d'arrivée : 
                <input type="text" id="arrivee" value="Lyon" />
            </label><br><br>
            
            <label>Date : 
                <input type="date" id="date" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" />
            </label><br><br>
            
            <button type="button" onclick="testAPI()">Tester l'API</button>
            <button type="button" onclick="testDirectPHP()">Test PHP Direct</button>
        </form>
        
        <div id="result"></div>
    </div>

    <!-- Test 4: Test PHP direct -->
    <div class="test-section">
        <h2>4. Test de recherche PHP direct (sans AJAX)</h2>
        <?php
        if (isset($_GET['test']) && isset($pdo)) {
            $ville_depart = $_GET['depart'] ?? 'Paris';
            $ville_arrivee = $_GET['arrivee'] ?? 'Lyon';
            $date = $_GET['date'] ?? date('Y-m-d', strtotime('+2 days'));
            
            echo "<p>Recherche : $ville_depart → $ville_arrivee le $date</p>";
            
            try {
                $sql = "
                    SELECT 
                        t.*,
                        u.pseudo as conducteur_pseudo,
                        v.marque,
                        v.modele,
                        v.type_carburant
                    FROM 
                        trajets t
                        INNER JOIN utilisateurs u ON t.id_conducteur = u.id_utilisateur
                        INNER JOIN vehicules v ON t.id_vehicule = v.id_vehicule
                    WHERE 
                        LOWER(t.ville_depart) LIKE LOWER(:ville_depart)
                        AND LOWER(t.ville_arrivee) LIKE LOWER(:ville_arrivee)
                        AND DATE(t.date_depart) = :date_depart
                        AND t.places_disponibles > 0
                        AND t.statut = 'planifie'
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'ville_depart' => '%' . $ville_depart . '%',
                    'ville_arrivee' => '%' . $ville_arrivee . '%',
                    'date_depart' => $date
                ]);
                
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<pre>';
                echo json_encode(['success' => true, 'trajets' => $results], JSON_PRETTY_PRINT);
                echo '</pre>';
                
            } catch(Exception $e) {
                echo '<p class="error">Erreur SQL : ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p>Ajoutez ?test=1&depart=Paris&arrivee=Lyon&date=' . date('Y-m-d', strtotime('+2 days')) . ' à l\'URL pour tester</p>';
        }
        ?>
    </div>

    <script>
        function testAPI() {
            const result = document.getElementById('result');
            result.innerHTML = '<p>Test en cours...</p>';
            
            const formData = new FormData();
            formData.append('ville_depart', document.getElementById('depart').value);
            formData.append('ville_arrivee', document.getElementById('arrivee').value);
            formData.append('date_depart', document.getElementById('date').value);
            
            fetch('api/search-trajets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Status:', response.status);
                console.log('Headers:', response.headers);
                return response.text(); // D'abord récupérer comme texte
            })
            .then(text => {
                console.log('Réponse brute:', text);
                result.innerHTML = '<h3>Réponse brute de l\'API :</h3><pre>' + text + '</pre>';
                
                // Essayer de parser en JSON
                try {
                    const json = JSON.parse(text);
                    result.innerHTML += '<h3>Données JSON parsées :</h3><pre>' + JSON.stringify(json, null, 2) + '</pre>';
                } catch(e) {
                    result.innerHTML += '<p class="error">❌ Erreur de parsing JSON : ' + e.message + '</p>';
                }
            })
            .catch(error => {
                result.innerHTML = '<p class="error">❌ Erreur : ' + error + '</p>';
                console.error('Erreur:', error);
            });
        }
        
        function testDirectPHP() {
            const depart = document.getElementById('depart').value;
            const arrivee = document.getElementById('arrivee').value;
            const date = document.getElementById('date').value;
            
            window.location.href = '?test=1&depart=' + depart + '&arrivee=' + arrivee + '&date=' + date;
        }
    </script>
</body>
</html>