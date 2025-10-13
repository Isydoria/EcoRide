<?php
/**
 * Script d'initialisation PostgreSQL v2 - Plus robuste
 * Exécute les fichiers SQL via connexion PDO directe
 */

// Désactiver session_start() qui cause des problèmes
define('NO_SESSION', true);

ob_start();
?>
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Init DB Render v2</title>
    <style>
        body { font-family: monospace; max-width: 1000px; margin: 20px auto; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #569cd6; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 4px; overflow-x: auto; }
        h1 { color: #4ec9b0; }
        h2 { color: #569cd6; margin-top: 30px; }
    </style>
</head>
<body>
<h1>🚀 Init PostgreSQL Render v2</h1>
<?php

// Vérifier environnement Render
if (!getenv('RENDER')) {
    echo "<p class='error'>❌ Doit être exécuté sur Render uniquement</p></body></html>";
    exit;
}

echo "<p class='success'>✅ Environnement Render détecté</p>";

// Connexion directe PostgreSQL
try {
    $dbUrl = getenv('DATABASE_URL');
    if (!$dbUrl) {
        throw new Exception('DATABASE_URL non défini');
    }

    echo "<p class='info'>📡 Connexion à PostgreSQL...</p>";

    $pdo = new PDO($dbUrl, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<p class='success'>✅ Connecté</p>";

    // Étape 1 : Exécuter le schéma complet
    echo "<h2>📋 Étape 1 : Création du schéma</h2>";

    $schemaFile = __DIR__ . '/database/schema_postgresql.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema_postgresql.sql introuvable");
    }

    $schema = file_get_contents($schemaFile);

    // Nettoyer BOM UTF-8
    $schema = str_replace("\xEF\xBB\xBF", '', $schema);

    try {
        // Exécuter tout le fichier d'un coup
        $pdo->exec($schema);
        echo "<p class='success'>✅ Schéma créé</p>";
    } catch (PDOException $e) {
        $error = $e->getMessage();
        if (strpos($error, 'already exists') !== false) {
            echo "<p class='warning'>⚠️ Tables déjà existantes</p>";
        } else {
            echo "<p class='error'>❌ Erreur schéma: " . htmlspecialchars($error) . "</p>";

            // Essayer de créer les tables une par une
            echo "<p class='info'>Tentative création table par table...</p>";

            $tables = [
                "CREATE TABLE IF NOT EXISTS utilisateur (
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
                )",
                "CREATE TABLE IF NOT EXISTS vehicule (
                    vehicule_id SERIAL PRIMARY KEY,
                    marque VARCHAR(50) NOT NULL,
                    modele VARCHAR(50) NOT NULL,
                    couleur VARCHAR(30),
                    immatriculation VARCHAR(20) UNIQUE NOT NULL,
                    places INTEGER NOT NULL CHECK (places > 0 AND places <= 8),
                    type_carburant VARCHAR(30),
                    id_conducteur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS covoiturage (
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
                )",
                "CREATE TABLE IF NOT EXISTS participation (
                    participation_id SERIAL PRIMARY KEY,
                    id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
                    id_passager INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
                    nombre_places INTEGER DEFAULT 1,
                    statut VARCHAR(20) DEFAULT 'en_attente',
                    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS preference (
                    preference_id SERIAL PRIMARY KEY,
                    id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
                    accepte_fumeur BOOLEAN DEFAULT FALSE,
                    accepte_animaux BOOLEAN DEFAULT FALSE,
                    accepte_musique BOOLEAN DEFAULT TRUE,
                    accepte_discussion BOOLEAN DEFAULT TRUE,
                    preferences_autres TEXT
                )",
                "CREATE TABLE IF NOT EXISTS avis (
                    avis_id SERIAL PRIMARY KEY,
                    id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
                    id_auteur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
                    id_utilisateur_note INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
                    note INTEGER CHECK (note >= 1 AND note <= 5),
                    commentaire TEXT,
                    date_avis TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS message (
                    message_id SERIAL PRIMARY KEY,
                    id_expediteur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
                    id_destinataire INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
                    id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
                    contenu TEXT NOT NULL,
                    lu BOOLEAN DEFAULT FALSE,
                    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS paiement (
                    paiement_id SERIAL PRIMARY KEY,
                    id_utilisateur INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
                    id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE SET NULL,
                    montant DECIMAL(10, 2) NOT NULL,
                    type_transaction VARCHAR(20),
                    statut VARCHAR(20) DEFAULT 'en_attente',
                    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )"
            ];

            foreach ($tables as $createTable) {
                try {
                    $pdo->exec($createTable);
                    echo "<p class='success'>✅ Table créée</p>";
                } catch (PDOException $ex) {
                    if (strpos($ex->getMessage(), 'already exists') === false) {
                        echo "<p class='error'>❌ " . htmlspecialchars($ex->getMessage()) . "</p>";
                    }
                }
            }
        }
    }

    // Étape 2 : Insérer les données
    echo "<h2>🌱 Étape 2 : Insertion des données</h2>";

    // Insérer admin directement
    try {
        $passwordHash = password_hash('Ec0R1de!', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO utilisateur (pseudo, email, password, role, credits, is_conducteur)
            VALUES ('Admin', 'admin@ecoride.fr', :password, 'administrateur', 100, true)
            ON CONFLICT (email) DO NOTHING
        ");
        $stmt->execute(['password' => $passwordHash]);
        echo "<p class='success'>✅ Admin créé</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Admin: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Insérer utilisateurs demo
    try {
        $users = [
            ['Demo User', 'demo@ecoride.fr', 'demo123', 'utilisateur', 50],
            ['Jean Dupont', 'jean@example.com', 'Test123!', 'utilisateur', 50],
            ['Marie Martin', 'marie@example.com', 'Test123!', 'utilisateur', 30]
        ];

        foreach ($users as $user) {
            $stmt = $pdo->prepare("
                INSERT INTO utilisateur (pseudo, email, password, role, credits, is_conducteur)
                VALUES (:pseudo, :email, :password, :role, :credits, true)
                ON CONFLICT (email) DO NOTHING
            ");
            $stmt->execute([
                'pseudo' => $user[0],
                'email' => $user[1],
                'password' => password_hash($user[2], PASSWORD_DEFAULT),
                'role' => $user[3],
                'credits' => $user[4]
            ]);
        }
        echo "<p class='success'>✅ Utilisateurs démo créés</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Users: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Étape 3 : Vérification
    echo "<h2>🔍 Étape 3 : Vérification</h2>";

    echo "<pre>";
    $tables = ['utilisateur', 'vehicule', 'covoiturage', 'participation', 'avis', 'message', 'paiement', 'preference'];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            printf("%-20s: %d lignes\n", ucfirst($table), $count);
        } catch (PDOException $e) {
            printf("%-20s: ❌ Erreur\n", ucfirst($table));
        }
    }
    echo "</pre>";

    // Liste des utilisateurs
    echo "<h2>👥 Comptes disponibles</h2>";
    try {
        $users = $pdo->query("SELECT pseudo, email, role, credits FROM utilisateur ORDER BY role, pseudo")->fetchAll();

        echo "<pre>";
        printf("%-20s %-30s %-15s %s\n", "Pseudo", "Email", "Rôle", "Crédits");
        echo str_repeat("-", 80) . "\n";
        foreach ($users as $user) {
            printf("%-20s %-30s %-15s %d\n",
                $user['pseudo'],
                $user['email'],
                $user['role'],
                $user['credits']
            );
        }
        echo "</pre>";

        echo "<p class='info'><strong>Mots de passe:</strong></p>";
        echo "<pre>";
        echo "admin@ecoride.fr  : Ec0R1de!\n";
        echo "demo@ecoride.fr   : demo123\n";
        echo "autres            : Test123!\n";
        echo "</pre>";

    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "<h2>✅ Initialisation terminée</h2>";
    echo "<p class='success'>➡️ <a href='/connexion.php' style='color:#4ec9b0'>Se connecter maintenant</a></p>";
    echo "<p class='warning'>⚠️ SUPPRIMER ce fichier après utilisation !</p>";

} catch (Exception $e) {
    echo "<h2 class='error'>❌ Erreur fatale</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>
</body>
</html>
