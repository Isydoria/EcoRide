-- ========================================
-- Base de données EcoRide - SCHEMA COMPLET
-- ========================================

DROP DATABASE IF EXISTS ecoride_db;
CREATE DATABASE ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoride_db;

-- Table configuration
CREATE TABLE configuration (
    id_configuration INT PRIMARY KEY AUTO_INCREMENT,
    cle VARCHAR(100) UNIQUE NOT NULL,
    valeur TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table utilisateur
CREATE TABLE utilisateur (
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
CREATE TABLE voiture (
    voiture_id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    modele VARCHAR(100) NOT NULL,
    marque VARCHAR(100) NOT NULL,
    immatriculation VARCHAR(20) UNIQUE NOT NULL,
    couleur VARCHAR(50),
    energie ENUM('essence', 'diesel', 'electrique', 'hybride', 'gpl') NOT NULL,
    places INT NOT NULL,
    date_premiere_immatriculation DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- Table parametre
CREATE TABLE parametre (
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
CREATE TABLE covoiturage (
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
CREATE TABLE participation (
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
CREATE TABLE avis (
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
CREATE TABLE transaction_credit (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    montant INT NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    description VARCHAR(255),
    reference_id INT,
    reference_type ENUM('participation', 'remboursement', 'bonus', 'commission'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
);

-- Configuration initiale
INSERT INTO configuration (cle, valeur) VALUES
('commission_credit', '2'),
('credit_inscription', '20'),
('email_contact', 'contact@ecoride.fr');

-- Fin du script
SELECT 'Base de données créée avec succès!' as Message;
