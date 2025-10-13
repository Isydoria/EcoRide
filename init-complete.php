<?php
/**
 * Script d'initialisation COMPLET avec toutes les tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Init Complète PostgreSQL</title>
    <style>
        body { font-family: monospace; background: #111; color: #0f0; padding: 20px; line-height: 1.6; }
        .ok { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        pre { background: #000; padding: 10px; border: 1px solid #333; margin: 10px 0; }
        h1 { color: #0ff; }
        h2 { color: #0ff; margin-top: 30px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        a { color: #0f0; }
        table { border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px 12px; text-align: left; border: 1px solid #333; }
        th { background: #222; color: #0ff; }
    </style>
</head>
<body>
<h1>🚀 Initialisation Complète PostgreSQL - EcoRide</h1>

<?php

if (!getenv('RENDER')) {
    die("<p class='error'>❌ Ce script doit être exécuté sur Render uniquement</p></body></html>");
}

echo "<p class='ok'>✅ Environnement Render détecté</p>";

// Parser DATABASE_URL
$dbUrl = getenv('DATABASE_URL');
if (!$dbUrl) {
    die("<p class='error'>❌ DATABASE_URL non défini</p></body></html>");
}

$parts = parse_url($dbUrl);
$host = $parts['host'];
$port = $parts['port'] ?? 5432;
$dbname = ltrim($parts['path'], '/');
$user = $parts['user'];
$pass = $parts['pass'];

echo "<pre>";
echo "PostgreSQL: $host:$port\n";
echo "Database: $dbname\n";
echo "User: $user\n";
echo "</pre>";

try {
    // Connexion PDO avec DSN explicite
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    echo "<p class='ok'>✅ Connexion PostgreSQL établie</p>";

    // Étape 1 : Nettoyer toutes les tables
    echo "<h2>🧹 Étape 1 : Nettoyage base de données</h2>";

    $db->exec("DROP TABLE IF EXISTS paiement CASCADE");
    $db->exec("DROP TABLE IF EXISTS message CASCADE");
    $db->exec("DROP TABLE IF EXISTS avis CASCADE");
    $db->exec("DROP TABLE IF EXISTS preference CASCADE");
    $db->exec("DROP TABLE IF EXISTS participation CASCADE");
    $db->exec("DROP TABLE IF EXISTS covoiturage CASCADE");
    $db->exec("DROP TABLE IF EXISTS vehicule CASCADE");
    $db->exec("DROP TABLE IF EXISTS utilisateur CASCADE");

    echo "<p class='ok'>✓ Tables supprimées (si elles existaient)</p>";

    // Étape 2 : Créer toutes les tables
    echo "<h2>📋 Étape 2 : Création des tables</h2>";

    // Table utilisateur
    $db->exec("
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
    echo "<p class='ok'>✓ utilisateur</p>";

    // Table vehicule
    $db->exec("
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
    echo "<p class='ok'>✓ vehicule</p>";

    // Table covoiturage
    $db->exec("
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
    echo "<p class='ok'>✓ covoiturage</p>";

    // Table participation
    $db->exec("
        CREATE TABLE participation (
            participation_id SERIAL PRIMARY KEY,
            id_trajet INTEGER REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
            id_passager INTEGER REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
            nombre_places INTEGER DEFAULT 1,
            statut VARCHAR(20) DEFAULT 'en_attente',
            date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='ok'>✓ participation</p>";

    // Table preference
    $db->exec("
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
    echo "<p class='ok'>✓ preference</p>";

    // Table avis
    $db->exec("
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
    echo "<p class='ok'>✓ avis</p>";

    // Table message
    $db->exec("
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
    echo "<p class='ok'>✓ message</p>";

    // Table paiement
    $db->exec("
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
    echo "<p class='ok'>✓ paiement</p>";

    echo "<p class='ok'>✅ Toutes les 8 tables créées avec succès</p>";

    // Étape 3 : Insérer les utilisateurs
    echo "<h2>👥 Étape 3 : Création des utilisateurs</h2>";

    $stmt = $db->prepare("
        INSERT INTO utilisateur (pseudo, email, password, role, credits, is_conducteur)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // Admin
    $stmt->execute(['Admin', 'admin@ecoride.fr', password_hash('Ec0R1de!', PASSWORD_DEFAULT), 'administrateur', 100, true]);
    echo "<p class='ok'>✓ Admin (admin@ecoride.fr / Ec0R1de!)</p>";

    // Employé
    $stmt->execute(['Employee', 'employee@ecoride.fr', password_hash('Emp123!', PASSWORD_DEFAULT), 'employe', 75, true]);
    echo "<p class='ok'>✓ Employé (employee@ecoride.fr / Emp123!)</p>";

    // Utilisateurs
    $stmt->execute(['Demo User', 'demo@ecoride.fr', password_hash('demo123', PASSWORD_DEFAULT), 'utilisateur', 50, true]);
    echo "<p class='ok'>✓ Demo (demo@ecoride.fr / demo123)</p>";

    $stmt->execute(['Jean Dupont', 'jean@example.com', password_hash('Test123!', PASSWORD_DEFAULT), 'utilisateur', 50, true]);
    echo "<p class='ok'>✓ Jean Dupont</p>";

    $stmt->execute(['Marie Martin', 'marie@example.com', password_hash('Test123!', PASSWORD_DEFAULT), 'utilisateur', 30, true]);
    echo "<p class='ok'>✓ Marie Martin</p>";

    $stmt->execute(['Pierre Durand', 'pierre@example.com', password_hash('Test123!', PASSWORD_DEFAULT), 'utilisateur', 40, true]);
    echo "<p class='ok'>✓ Pierre Durand</p>";

    echo "<p class='ok'>✅ 6 utilisateurs créés</p>";

    // Étape 4 : Insérer des véhicules de démonstration
    echo "<h2>🚗 Étape 4 : Création de véhicules</h2>";

    $adminId = $db->query("SELECT utilisateur_id FROM utilisateur WHERE email='admin@ecoride.fr'")->fetchColumn();
    $jeanId = $db->query("SELECT utilisateur_id FROM utilisateur WHERE email='jean@example.com'")->fetchColumn();
    $marieId = $db->query("SELECT utilisateur_id FROM utilisateur WHERE email='marie@example.com'")->fetchColumn();

    $stmtVehicule = $db->prepare("
        INSERT INTO vehicule (marque, modele, couleur, immatriculation, places, type_carburant, id_conducteur)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtVehicule->execute(['Tesla', 'Model 3', 'Blanc', 'EL-123-CT', 4, 'electrique', $adminId]);
    $stmtVehicule->execute(['Renault', 'Zoe', 'Bleu', 'EL-456-ZE', 4, 'electrique', $jeanId]);
    $stmtVehicule->execute(['Toyota', 'Prius', 'Gris', 'HY-789-PR', 5, 'hybride', $marieId]);

    echo "<p class='ok'>✓ 3 véhicules écologiques créés</p>";

    // Étape 5 : Vérification complète
    echo "<h2>🔍 Étape 5 : Vérification de la base de données</h2>";

    echo "<table>";
    echo "<tr><th>Table</th><th>Nombre de lignes</th></tr>";

    $tables = ['utilisateur', 'vehicule', 'covoiturage', 'participation', 'preference', 'avis', 'message', 'paiement'];
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $color = $count > 0 ? 'ok' : 'warning';
        echo "<tr><td>$table</td><td class='$color'>$count</td></tr>";
    }
    echo "</table>";

    // Liste des utilisateurs
    echo "<h2>👤 Liste des comptes disponibles</h2>";

    $users = $db->query("SELECT pseudo, email, role, credits FROM utilisateur ORDER BY role DESC, pseudo")->fetchAll();

    echo "<table>";
    echo "<tr><th>Pseudo</th><th>Email</th><th>Rôle</th><th>Crédits</th></tr>";
    foreach ($users as $u) {
        $roleColor = $u['role'] === 'administrateur' ? 'warning' : ($u['role'] === 'employe' ? 'ok' : '');
        echo "<tr>";
        echo "<td>{$u['pseudo']}</td>";
        echo "<td>{$u['email']}</td>";
        echo "<td class='$roleColor'>{$u['role']}</td>";
        echo "<td>{$u['credits']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Mots de passe
    echo "<h2>🔑 Mots de passe des comptes</h2>";
    echo "<pre>";
    echo "ADMINISTRATEUR:\n";
    echo "  admin@ecoride.fr     → Ec0R1de!\n\n";
    echo "EMPLOYÉ:\n";
    echo "  employee@ecoride.fr  → Emp123!\n\n";
    echo "UTILISATEURS:\n";
    echo "  demo@ecoride.fr      → demo123\n";
    echo "  jean@example.com     → Test123!\n";
    echo "  marie@example.com    → Test123!\n";
    echo "  pierre@example.com   → Test123!\n";
    echo "</pre>";

    // Message final
    echo "<h2 class='ok'>✅ Initialisation terminée avec succès !</h2>";
    echo "<p>La base de données PostgreSQL est prête avec :</p>";
    echo "<ul>";
    echo "<li>✓ 8 tables créées</li>";
    echo "<li>✓ 6 utilisateurs (1 admin, 1 employé, 4 users)</li>";
    echo "<li>✓ 3 véhicules écologiques</li>";
    echo "</ul>";

    echo "<p><strong>Prochaines étapes :</strong></p>";
    echo "<ol>";
    echo "<li><a href='/connexion.php'>Se connecter en tant qu'admin</a></li>";
    echo "<li><a href='/admin/dashboard.php'>Accéder au dashboard admin</a></li>";
    echo "<li class='warning'>⚠️ SUPPRIMER ce fichier init-complete.php pour la sécurité !</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h2 class='error'>❌ Erreur PDO</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Erreur</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

?>
</body>
</html>
