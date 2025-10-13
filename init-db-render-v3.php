<?php
/**
 * Script d'initialisation PostgreSQL v3
 * Utilise database_render.php directement
 */

// Désactiver toute sortie avant les headers
define('NO_SESSION', true);
ob_start();

?>
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Init DB Render v3</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #0d1117; color: #c9d1d9; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #58a6ff; font-size: 24px; margin: 20px 0; }
        h2 { color: #79c0ff; font-size: 18px; margin: 20px 0 10px 0; border-bottom: 1px solid #30363d; padding-bottom: 5px; }
        .success { color: #3fb950; }
        .error { color: #f85149; }
        .warning { color: #d29922; }
        .info { color: #58a6ff; }
        pre { background: #161b22; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #30363d; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #30363d; }
        th { color: #79c0ff; background: #161b22; }
        .btn { display: inline-block; padding: 10px 20px; background: #238636; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .btn:hover { background: #2ea043; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚀 Initialisation Base PostgreSQL - Render.com v3</h1>
<?php

// Vérifier environnement
if (!getenv('RENDER')) {
    echo "<p class='error'>❌ Ce script doit être exécuté sur Render uniquement</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='success'>✅ Environnement Render détecté</p>";

try {
    // Charger la config database_render.php directement
    echo "<p class='info'>📡 Chargement configuration PostgreSQL...</p>";

    require_once __DIR__ . '/config/database_render.php';

    // Obtenir la connexion
    $pdo = db();

    echo "<p class='success'>✅ Connexion PostgreSQL établie</p>";

    // Afficher les extensions chargées
    echo "<p class='info'>Extensions PHP chargées:</p><pre>";
    $extensions = get_loaded_extensions();
    foreach (['pdo', 'pdo_pgsql', 'pgsql'] as $ext) {
        $loaded = in_array($ext, $extensions) ? '✅' : '❌';
        echo "$loaded $ext\n";
    }
    echo "</pre>";

    // Étape 1 : Drop et recréer les tables
    echo "<h2>📋 Étape 1 : Nettoyage et création tables</h2>";

    $tables = ['paiement', 'message', 'avis', 'preference', 'participation', 'covoiturage', 'vehicule', 'utilisateur'];

    echo "<p class='warning'>⚠️ Suppression des tables existantes...</p>";
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table CASCADE");
            echo "<span class='success'>✓</span> $table ";
        } catch (PDOException $e) {
            echo "<span class='error'>✗</span> $table ";
        }
    }
    echo "<br><br>";

    echo "<p class='info'>📝 Création des tables...</p>";

    // Créer utilisateur
    $pdo->exec("
        CREATE TABLE utilisateur (
            utilisateur_id SERIAL PRIMARY KEY,
            pseudo VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            telephone VARCHAR(20),
            role VARCHAR(20) DEFAULT 'utilisateur',
            credits INTEGER DEFAULT 20,
            is_conducteur BOOLEAN DEFAULT FALSE,
            date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    echo "<span class='success'>✓</span> utilisateur<br>";

    // Créer vehicule
    $pdo->exec("
        CREATE TABLE vehicule (
            vehicule_id SERIAL PRIMARY KEY,
            marque VARCHAR(50) NOT NULL,
            modele VARCHAR(50) NOT NULL,
            couleur VARCHAR(30),
            immatriculation VARCHAR(20) UNIQUE NOT NULL,
            places INTEGER NOT NULL CHECK (places > 0 AND places <= 8),
            type_carburant VARCHAR(30),
            id_conducteur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<span class='success'>✓</span> vehicule<br>";

    // Créer covoiturage
    $pdo->exec("
        CREATE TABLE covoiturage (
            covoiturage_id SERIAL PRIMARY KEY,
            ville_depart VARCHAR(100) NOT NULL,
            ville_arrivee VARCHAR(100) NOT NULL,
            adresse_depart VARCHAR(255),
            adresse_arrivee VARCHAR(255),
            date_depart TIMESTAMP NOT NULL,
            date_arrivee TIMESTAMP,
            places_disponibles INTEGER NOT NULL,
            prix DECIMAL(10, 2) NOT NULL,
            statut VARCHAR(20) DEFAULT 'planifie',
            id_conducteur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            id_vehicule INTEGER REFERENCES vehicule(vehicule_id) ON DELETE SET NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<span class='success'>✓</span> covoiturage<br>";

    // Créer participation
    $pdo->exec("
        CREATE TABLE participation (
            participation_id SERIAL PRIMARY KEY,
            id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
            id_passager INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            nombre_places INTEGER DEFAULT 1,
            statut VARCHAR(20) DEFAULT 'en_attente',
            date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<span class='success'>✓</span> participation<br>";

    // Créer preference
    $pdo->exec("
        CREATE TABLE preference (
            preference_id SERIAL PRIMARY KEY,
            id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
            accepte_fumeur BOOLEAN DEFAULT FALSE,
            accepte_animaux BOOLEAN DEFAULT FALSE,
            accepte_musique BOOLEAN DEFAULT TRUE,
            accepte_discussion BOOLEAN DEFAULT TRUE,
            preferences_autres TEXT
        )
    ");
    echo "<span class='success'>✓</span> preference<br>";

    // Créer avis
    $pdo->exec("
        CREATE TABLE avis (
            avis_id SERIAL PRIMARY KEY,
            id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
            id_auteur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            id_utilisateur_note INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            note INTEGER CHECK (note >= 1 AND note <= 5),
            commentaire TEXT,
            date_avis TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<span class='success'>✓</span> avis<br>";

    // Créer message
    $pdo->exec("
        CREATE TABLE message (
            message_id SERIAL PRIMARY KEY,
            id_expediteur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            id_destinataire INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
            contenu TEXT NOT NULL,
            lu BOOLEAN DEFAULT FALSE,
            date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<span class='success'>✓</span> message<br>";

    // Créer paiement
    $pdo->exec("
        CREATE TABLE paiement (
            paiement_id SERIAL PRIMARY KEY,
            id_utilisateur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE SET NULL,
            montant DECIMAL(10, 2) NOT NULL,
            type_transaction VARCHAR(20),
            statut VARCHAR(20) DEFAULT 'en_attente',
            date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<span class='success'>✓</span> paiement<br>";

    echo "<p class='success'>✅ Toutes les tables créées</p>";

    // Étape 2 : Insérer les données
    echo "<h2>🌱 Étape 2 : Insertion des données</h2>";

    // Admin
    $stmt = $pdo->prepare("
        INSERT INTO utilisateur (pseudo, email, password, role, credits, is_conducteur)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['Admin', 'admin@ecoride.fr', password_hash('Ec0R1de!', PASSWORD_DEFAULT), 'administrateur', 100, true]);
    echo "<span class='success'>✓</span> Admin créé<br>";

    // Utilisateurs demo
    $users = [
        ['Demo User', 'demo@ecoride.fr', 'demo123', 'utilisateur', 50],
        ['Jean Dupont', 'jean@example.com', 'Test123!', 'utilisateur', 50],
        ['Marie Martin', 'marie@example.com', 'Test123!', 'utilisateur', 30],
        ['Pierre Durand', 'pierre@example.com', 'Test123!', 'utilisateur', 40]
    ];

    foreach ($users as $user) {
        $stmt->execute([
            $user[0],
            $user[1],
            password_hash($user[2], PASSWORD_DEFAULT),
            $user[3],
            $user[4],
            true
        ]);
        echo "<span class='success'>✓</span> {$user[0]}<br>";
    }

    echo "<p class='success'>✅ " . (count($users) + 1) . " utilisateurs créés</p>";

    // Étape 3 : Vérification
    echo "<h2>🔍 Étape 3 : Vérification</h2>";

    echo "<table>";
    echo "<tr><th>Table</th><th>Lignes</th></tr>";
    $tables = ['utilisateur', 'vehicule', 'covoiturage', 'participation', 'avis', 'message', 'paiement', 'preference'];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $color = $count > 0 ? 'success' : 'info';
        echo "<tr><td>$table</td><td class='$color'>$count</td></tr>";
    }
    echo "</table>";

    // Étape 4 : Liste des comptes
    echo "<h2>👥 Étape 4 : Comptes disponibles</h2>";

    $users = $pdo->query("SELECT pseudo, email, role, credits FROM utilisateur ORDER BY role DESC, pseudo")->fetchAll();

    echo "<table>";
    echo "<tr><th>Pseudo</th><th>Email</th><th>Rôle</th><th>Crédits</th></tr>";
    foreach ($users as $user) {
        $roleColor = $user['role'] === 'administrateur' ? 'warning' : 'info';
        echo "<tr>";
        echo "<td>{$user['pseudo']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td class='$roleColor'>{$user['role']}</td>";
        echo "<td>{$user['credits']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h2>🔑 Mots de passe</h2>";
    echo "<pre>";
    echo "admin@ecoride.fr  → Ec0R1de!\n";
    echo "demo@ecoride.fr   → demo123\n";
    echo "jean@example.com  → Test123!\n";
    echo "marie@example.com → Test123!\n";
    echo "pierre@example.com→ Test123!\n";
    echo "</pre>";

    echo "<h2 class='success'>✅ Initialisation terminée avec succès !</h2>";
    echo "<p><a href='/connexion.php' class='btn'>➡️ Se connecter maintenant</a></p>";
    echo "<p class='warning'>⚠️ IMPORTANT : Supprimer ce fichier pour la sécurité !</p>";

} catch (Exception $e) {
    echo "<h2 class='error'>❌ Erreur fatale</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>
</div>
</body>
</html>
