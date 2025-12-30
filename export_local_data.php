<?php
// export_local_data.php
// Exporter les données de la base MySQL locale vers PostgreSQL Render

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export/Import Base de Données</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
        }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .status {
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            line-height: 1.6;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin: 10px 5px;
        }
        .btn:hover { transform: translateY(-2px); }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover { background: #f8f9fa; }
        pre {
            background: #2d3748;
            color: #68d391;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 400px;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Migration Base de Données</h1>
        <p class="subtitle">MySQL Local → PostgreSQL Render</p>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['export'])) {
        echo '<div class="status info"><strong>📊 ÉTAPE 1 : Export des données locales MySQL</strong></div>';

        try {
            // Connexion MySQL local
            $pdo_mysql = new PDO('mysql:host=localhost;dbname=ecoride_db;charset=utf8mb4', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            echo '<div class="status success">✅ Connexion MySQL locale réussie</div>';

            // Exporter les données
            $export = [
                'utilisateurs' => [],
                'voitures' => [],
                'parametres' => [],
                'covoiturages' => [],
                'participations' => [],
                'avis' => [],
                'transactions' => [],
                'configuration' => []
            ];

            // Utilisateurs
            $stmt = $pdo_mysql->query("SELECT * FROM utilisateur");
            $export['utilisateurs'] = $stmt->fetchAll();

            // Voitures
            $stmt = $pdo_mysql->query("SELECT * FROM voiture");
            $export['voitures'] = $stmt->fetchAll();

            // Paramètres
            $stmt = $pdo_mysql->query("SELECT * FROM parametre");
            $export['parametres'] = $stmt->fetchAll();

            // Covoiturages
            $stmt = $pdo_mysql->query("SELECT * FROM covoiturage");
            $export['covoiturages'] = $stmt->fetchAll();

            // Participations
            $stmt = $pdo_mysql->query("SELECT * FROM participation");
            $export['participations'] = $stmt->fetchAll();

            // Avis
            $stmt = $pdo_mysql->query("SELECT * FROM avis");
            $export['avis'] = $stmt->fetchAll();

            // Transactions
            $stmt = $pdo_mysql->query("SELECT * FROM transaction");
            $export['transactions'] = $stmt->fetchAll();

            // Configuration
            $stmt = $pdo_mysql->query("SELECT * FROM configuration");
            $export['configuration'] = $stmt->fetchAll();

            // Sauvegarder dans un fichier
            file_put_contents(__DIR__ . '/export_data.json', json_encode($export, JSON_PRETTY_PRINT));

            echo '<div class="status success">';
            echo '<strong>✅ Export réussi !</strong><br><br>';
            echo '<strong>Données exportées :</strong><br>';
            echo '• Utilisateurs : ' . count($export['utilisateurs']) . '<br>';
            echo '• Voitures : ' . count($export['voitures']) . '<br>';
            echo '• Paramètres : ' . count($export['parametres']) . '<br>';
            echo '• Covoiturages : ' . count($export['covoiturages']) . '<br>';
            echo '• Participations : ' . count($export['participations']) . '<br>';
            echo '• Avis : ' . count($export['avis']) . '<br>';
            echo '• Transactions : ' . count($export['transactions']) . '<br>';
            echo '• Configuration : ' . count($export['configuration']) . '<br>';
            echo '</div>';

            // Afficher les utilisateurs
            if (count($export['utilisateurs']) > 0) {
                echo '<div class="section">';
                echo '<h3>👥 Utilisateurs exportés</h3>';
                echo '<table>';
                echo '<thead><tr><th>ID</th><th>Pseudo</th><th>Email</th><th>Rôle</th><th>Crédits</th></tr></thead>';
                echo '<tbody>';
                foreach ($export['utilisateurs'] as $user) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($user['utilisateur_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['pseudo']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                    echo '<td><strong>' . htmlspecialchars($user['role']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($user['credits']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            }

            echo '<form method="POST" style="margin-top: 30px;">';
            echo '<button type="submit" name="import" class="btn">➡️ ÉTAPE 2 : Importer dans PostgreSQL Render</button>';
            echo '</form>';

        } catch (PDOException $e) {
            echo '<div class="status error">';
            echo '<strong>❌ Erreur MySQL :</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }

    } elseif (isset($_POST['import'])) {
        echo '<div class="status info"><strong>📥 ÉTAPE 2 : Import dans PostgreSQL Render</strong></div>';

        // Charger le fichier d'export
        if (!file_exists(__DIR__ . '/export_data.json')) {
            echo '<div class="status error">❌ Fichier export_data.json introuvable. Veuillez d\'abord exporter.</div>';
            exit;
        }

        $export = json_decode(file_get_contents(__DIR__ . '/export_data.json'), true);

        // Charger DATABASE_URL
        if (file_exists(__DIR__ . '/.env')) {
            $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
                putenv(trim($key) . '=' . trim($value));
            }
        }

        $database_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

        if (!$database_url) {
            echo '<div class="status error">❌ DATABASE_URL introuvable dans .env</div>';
            exit;
        }

        try {
            // Connexion PostgreSQL Render
            $db = parse_url($database_url);
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
                $db['host'],
                $db['port'] ?? 5432,
                ltrim($db['path'], '/')
            );

            $pdo_pg = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            echo '<div class="status success">✅ Connexion PostgreSQL Render réussie</div>';

            // Mot de passe unique pour tous les utilisateurs
            $password_unique = password_hash('EcoRide2025!', PASSWORD_DEFAULT);

            echo '<div class="status warning">';
            echo '<strong>🔐 Mot de passe unifié pour tous les utilisateurs :</strong><br>';
            echo 'Mot de passe : <code><strong>EcoRide2025!</strong></code><br>';
            echo 'Ce mot de passe fonctionne pour tous les comptes (admin, employés, utilisateurs)';
            echo '</div>';

            $stats = [
                'utilisateurs' => 0,
                'voitures' => 0,
                'parametres' => 0,
                'covoiturages' => 0,
                'participations' => 0,
                'avis' => 0,
                'transactions' => 0
            ];

            // Import des utilisateurs avec mot de passe unique
            echo '<div class="status info">👥 Import des utilisateurs...</div>';
            foreach ($export['utilisateurs'] as $user) {
                $stmt = $pdo_pg->prepare("
                    INSERT INTO utilisateur (pseudo, email, password, telephone, photo_profil, biographie, credits, role, created_at)
                    VALUES (:pseudo, :email, :password, :telephone, :photo_profil, :biographie, :credits, :role, :created_at)
                ");
                $stmt->execute([
                    'pseudo' => $user['pseudo'],
                    'email' => $user['email'],
                    'password' => $password_unique, // Mot de passe unique !
                    'telephone' => $user['telephone'],
                    'photo_profil' => $user['photo_profil'],
                    'biographie' => $user['biographie'],
                    'credits' => $user['credits'],
                    'role' => $user['role'],
                    'created_at' => $user['created_at']
                ]);
                $stats['utilisateurs']++;
            }

            // Import des voitures
            echo '<div class="status info">🚗 Import des voitures...</div>';
            foreach ($export['voitures'] as $voiture) {
                $stmt = $pdo_pg->prepare("
                    INSERT INTO voiture (utilisateur_id, modele, marque, immatriculation, couleur, places_disponibles, photo_vehicule, type_vehicule)
                    VALUES (:utilisateur_id, :modele, :marque, :immatriculation, :couleur, :places_disponibles, :photo_vehicule, :type_vehicule)
                ");
                $stmt->execute([
                    'utilisateur_id' => $voiture['utilisateur_id'],
                    'modele' => $voiture['modele'],
                    'marque' => $voiture['marque'],
                    'immatriculation' => $voiture['immatriculation'],
                    'couleur' => $voiture['couleur'],
                    'places_disponibles' => $voiture['places_disponibles'],
                    'photo_vehicule' => $voiture['photo_vehicule'],
                    'type_vehicule' => $voiture['type_vehicule']
                ]);
                $stats['voitures']++;
            }

            // Import des paramètres
            echo '<div class="status info">⚙️ Import des paramètres...</div>';
            foreach ($export['parametres'] as $param) {
                $stmt = $pdo_pg->prepare("
                    INSERT INTO parametre (utilisateur_id, fumeur, animaux, discussion, musique, preferences_supplementaires)
                    VALUES (:utilisateur_id, :fumeur, :animaux, :discussion, :musique, :preferences_supplementaires)
                ");
                $stmt->execute([
                    'utilisateur_id' => $param['utilisateur_id'],
                    'fumeur' => $param['fumeur'] ? 'true' : 'false',
                    'animaux' => $param['animaux'] ? 'true' : 'false',
                    'discussion' => $param['discussion'] ? 'true' : 'false',
                    'musique' => $param['musique'] ? 'true' : 'false',
                    'preferences_supplementaires' => $param['preferences_supplementaires']
                ]);
                $stats['parametres']++;
            }

            // Import des covoiturages avec dates mises à jour
            echo '<div class="status info">🚗 Import des covoiturages (dates jusqu\'en février 2025)...</div>';

            $dates_futures = [
                '2025-01-10', '2025-01-15', '2025-01-20', '2025-01-25', '2025-01-30',
                '2025-02-05', '2025-02-10', '2025-02-15', '2025-02-20', '2025-02-25'
            ];

            foreach ($export['covoiturages'] as $index => $covoit) {
                // Assigner une date future
                $nouvelle_date = $dates_futures[$index % count($dates_futures)];

                $stmt = $pdo_pg->prepare("
                    INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, places_disponibles, prix_par_place, statut, created_at)
                    VALUES (:conducteur_id, :voiture_id, :ville_depart, :ville_arrivee, :date_depart, :heure_depart, :places_disponibles, :prix_par_place, :statut, :created_at)
                ");
                $stmt->execute([
                    'conducteur_id' => $covoit['conducteur_id'],
                    'voiture_id' => $covoit['voiture_id'],
                    'ville_depart' => $covoit['ville_depart'],
                    'ville_arrivee' => $covoit['ville_arrivee'],
                    'date_depart' => $nouvelle_date,
                    'heure_depart' => $covoit['heure_depart'],
                    'places_disponibles' => $covoit['places_disponibles'],
                    'prix_par_place' => $covoit['prix_par_place'],
                    'statut' => $covoit['statut'],
                    'created_at' => $covoit['created_at']
                ]);
                $stats['covoiturages']++;
            }

            // Import des participations
            echo '<div class="status info">✋ Import des participations...</div>';
            foreach ($export['participations'] as $part) {
                $stmt = $pdo_pg->prepare("
                    INSERT INTO participation (covoiturage_id, passager_id, places_reservees, statut_reservation, created_at)
                    VALUES (:covoiturage_id, :passager_id, :places_reservees, :statut_reservation, :created_at)
                ");
                $stmt->execute([
                    'covoiturage_id' => $part['covoiturage_id'],
                    'passager_id' => $part['passager_id'],
                    'places_reservees' => $part['places_reservees'],
                    'statut_reservation' => $part['statut_reservation'],
                    'created_at' => $part['created_at']
                ]);
                $stats['participations']++;
            }

            // Import des avis
            echo '<div class="status info">⭐ Import des avis...</div>';
            foreach ($export['avis'] as $avis) {
                $stmt = $pdo_pg->prepare("
                    INSERT INTO avis (evaluateur_id, evalue_id, covoiturage_id, note, commentaire, statut, valide_par, date_validation, created_at)
                    VALUES (:evaluateur_id, :evalue_id, :covoiturage_id, :note, :commentaire, :statut, :valide_par, :date_validation, :created_at)
                ");
                $stmt->execute([
                    'evaluateur_id' => $avis['evaluateur_id'],
                    'evalue_id' => $avis['evalue_id'],
                    'covoiturage_id' => $avis['covoiturage_id'],
                    'note' => $avis['note'],
                    'commentaire' => $avis['commentaire'],
                    'statut' => $avis['statut'],
                    'valide_par' => $avis['valide_par'],
                    'date_validation' => $avis['date_validation'],
                    'created_at' => $avis['created_at']
                ]);
                $stats['avis']++;
            }

            // Import des transactions
            echo '<div class="status info">💰 Import des transactions...</div>';
            foreach ($export['transactions'] as $trans) {
                $stmt = $pdo_pg->prepare("
                    INSERT INTO transaction (utilisateur_id, montant, type_transaction, created_at)
                    VALUES (:utilisateur_id, :montant, :type_transaction, :created_at)
                ");
                $stmt->execute([
                    'utilisateur_id' => $trans['utilisateur_id'],
                    'montant' => $trans['montant'],
                    'type_transaction' => $trans['type_transaction'],
                    'created_at' => $trans['created_at']
                ]);
                $stats['transactions']++;
            }

            echo '<div class="status success">';
            echo '<strong>✅ MIGRATION TERMINÉE AVEC SUCCÈS !</strong><br><br>';
            echo '<strong>Données importées :</strong><br>';
            echo '• Utilisateurs : ' . $stats['utilisateurs'] . '<br>';
            echo '• Voitures : ' . $stats['voitures'] . '<br>';
            echo '• Paramètres : ' . $stats['parametres'] . '<br>';
            echo '• Covoiturages : ' . $stats['covoiturages'] . ' (dates mises à jour jusqu\'en février)<br>';
            echo '• Participations : ' . $stats['participations'] . '<br>';
            echo '• Avis : ' . $stats['avis'] . '<br>';
            echo '• Transactions : ' . $stats['transactions'] . '<br>';
            echo '</div>';

            echo '<div class="status warning">';
            echo '<strong>📝 Informations importantes :</strong><br>';
            echo '• Tous les utilisateurs ont le même mot de passe : <code><strong>EcoRide2025!</strong></code><br>';
            echo '• Les dates des trajets ont été mises à jour (janvier-février 2025)<br>';
            echo '• Votre application Render est maintenant prête à être utilisée !<br>';
            echo '• URL : <a href="https://ecoride-om7c.onrender.com" target="_blank">https://ecoride-om7c.onrender.com</a>';
            echo '</div>';

        } catch (PDOException $e) {
            echo '<div class="status error">';
            echo '<strong>❌ Erreur PostgreSQL :</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    }

} else {
    // Formulaire initial
    ?>
    <div class="status info">
        <strong>ℹ️ Ce script va :</strong><br>
        1. Exporter toutes les données de votre base MySQL locale<br>
        2. Mettre le même mot de passe pour tous les utilisateurs<br>
        3. Mettre à jour les dates des trajets (janvier-février 2025)<br>
        4. Importer tout dans PostgreSQL Render
    </div>

    <form method="POST">
        <button type="submit" name="export" class="btn">
            🚀 ÉTAPE 1 : Exporter les données locales
        </button>
    </form>
    <?php
}
?>
    </div>
</body>
</html>
