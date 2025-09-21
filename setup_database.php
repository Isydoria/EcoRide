<?php
/**
 * Script temporaire pour initialiser la base de données Railway
 * À supprimer après utilisation
 */

// Configuration Railway (essai multiple méthodes)
$host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? null;
$dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? null;
$username = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? null;
$password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? null;

// Debug variables
echo "<h2>🔍 Debug Variables Railway</h2>";
echo "<p>Host: " . ($host ? "✅ Trouvé" : "❌ Non trouvé") . "</p>";
echo "<p>Database: " . ($dbname ? "✅ Trouvé" : "❌ Non trouvé") . "</p>";
echo "<p>User: " . ($username ? "✅ Trouvé" : "❌ Non trouvé") . "</p>";
echo "<p>Password: " . ($password ? "✅ Trouvé" : "❌ Non trouvé") . "</p>";

if (!$host || !$dbname || !$username || !$password) {
    die('❌ Variables d\'environnement Railway non trouvées');
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>🚀 Initialisation Base EcoRide</h2>";

    // Script de création des tables
    $sql = "
    -- Table configuration
    CREATE TABLE IF NOT EXISTS configuration (
        id_configuration INT PRIMARY KEY AUTO_INCREMENT,
        cle VARCHAR(100) UNIQUE NOT NULL,
        valeur TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Table utilisateur
    CREATE TABLE IF NOT EXISTS utilisateur (
        utilisateur_id INT PRIMARY KEY AUTO_INCREMENT,
        pseudo VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        telephone VARCHAR(20),
        adresse VARCHAR(255),
        date_naissance DATE,
        photo VARCHAR(255) DEFAULT 'default.jpg',
        credit INT DEFAULT 20,
        role ENUM('utilisateur', 'employe', 'administrateur') DEFAULT 'utilisateur',
        statut ENUM('actif', 'suspendu') DEFAULT 'actif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    -- Table voiture
    CREATE TABLE IF NOT EXISTS voiture (
        voiture_id INT PRIMARY KEY AUTO_INCREMENT,
        utilisateur_id INT NOT NULL,
        modele VARCHAR(100) NOT NULL,
        marque VARCHAR(100) NOT NULL,
        immatriculation VARCHAR(20) UNIQUE NOT NULL,
        couleur VARCHAR(50),
        energie ENUM('essence', 'diesel', 'electrique', 'hybride') NOT NULL,
        places INT NOT NULL,
        date_premiere_immatriculation DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
    );

    -- Table parametre
    CREATE TABLE IF NOT EXISTS parametre (
        parametre_id INT PRIMARY KEY AUTO_INCREMENT,
        utilisateur_id INT NOT NULL,
        fumeur BOOLEAN DEFAULT FALSE,
        animaux BOOLEAN DEFAULT FALSE,
        musique BOOLEAN DEFAULT TRUE,
        discussion BOOLEAN DEFAULT TRUE,
        preferences_custom TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
    );

    -- Table covoiturage
    CREATE TABLE IF NOT EXISTS covoiturage (
        covoiturage_id INT PRIMARY KEY AUTO_INCREMENT,
        conducteur_id INT NOT NULL,
        voiture_id INT NOT NULL,
        ville_depart VARCHAR(100) NOT NULL,
        adresse_depart VARCHAR(255),
        ville_arrivee VARCHAR(100) NOT NULL,
        adresse_arrivee VARCHAR(255),
        date_depart DATETIME NOT NULL,
        date_arrivee DATETIME NOT NULL,
        places_disponibles INT NOT NULL,
        prix_par_place DECIMAL(10, 2) NOT NULL,
        statut ENUM('planifie', 'en_cours', 'termine', 'annule') DEFAULT 'planifie',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conducteur_id) REFERENCES utilisateur(utilisateur_id),
        FOREIGN KEY (voiture_id) REFERENCES voiture(voiture_id)
    );

    -- Table participation
    CREATE TABLE IF NOT EXISTS participation (
        participation_id INT PRIMARY KEY AUTO_INCREMENT,
        covoiturage_id INT NOT NULL,
        passager_id INT NOT NULL,
        nombre_places INT DEFAULT 1,
        credit_utilise INT NOT NULL,
        statut ENUM('reserve', 'confirme', 'annule', 'termine') DEFAULT 'reserve',
        date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id),
        FOREIGN KEY (passager_id) REFERENCES utilisateur(utilisateur_id),
        UNIQUE KEY unique_participation (covoiturage_id, passager_id)
    );

    -- Table avis
    CREATE TABLE IF NOT EXISTS avis (
        avis_id INT PRIMARY KEY AUTO_INCREMENT,
        covoiturage_id INT NOT NULL,
        auteur_id INT NOT NULL,
        destinataire_id INT NOT NULL,
        commentaire TEXT,
        note INT CHECK (note >= 1 AND note <= 5),
        statut ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
        valide_par INT,
        date_validation DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id),
        FOREIGN KEY (auteur_id) REFERENCES utilisateur(utilisateur_id),
        FOREIGN KEY (destinataire_id) REFERENCES utilisateur(utilisateur_id)
    );

    -- Table transaction_credit
    CREATE TABLE IF NOT EXISTS transaction_credit (
        transaction_id INT PRIMARY KEY AUTO_INCREMENT,
        utilisateur_id INT NOT NULL,
        montant INT NOT NULL,
        type ENUM('credit', 'debit') NOT NULL,
        description VARCHAR(255),
        reference_id INT,
        reference_type ENUM('participation', 'remboursement', 'bonus', 'commission'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
    );";

    // Exécuter les requêtes
    $pdo->exec($sql);
    echo "<p>✅ Tables créées avec succès</p>";

    // Configuration initiale
    $config_sql = "
    INSERT IGNORE INTO configuration (cle, valeur) VALUES
    ('commission_credit', '2'),
    ('credit_inscription', '20'),
    ('email_contact', 'contact@ecoride.fr');";

    $pdo->exec($config_sql);
    echo "<p>✅ Configuration initiale ajoutée</p>";

    // Vérification
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h3>📋 Tables créées :</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    echo "<p><strong>🎉 Base de données initialisée avec succès !</strong></p>";
    echo "<p><em>Vous pouvez maintenant supprimer ce fichier.</em></p>";

} catch (Exception $e) {
    echo "<p>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>