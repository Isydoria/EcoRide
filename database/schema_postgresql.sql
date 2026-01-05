
-- ========================================
-- SCHÉMA POSTGRESQL POUR ECORIDE
-- Compatible 100% avec le schéma MySQL
-- ========================================

-- Suppression des tables existantes (ordre inversé pour les contraintes)
DROP TABLE IF EXISTS transaction_credit CASCADE;
DROP TABLE IF EXISTS avis CASCADE;
DROP TABLE IF EXISTS participation CASCADE;
DROP TABLE IF EXISTS covoiturage CASCADE;
DROP TABLE IF EXISTS parametre CASCADE;
DROP TABLE IF EXISTS voiture CASCADE;
DROP TABLE IF EXISTS utilisateur CASCADE;
DROP TABLE IF EXISTS configuration CASCADE;

-- ========================================
-- TABLE : configuration
-- ========================================
CREATE TABLE configuration (
    id_configuration SERIAL PRIMARY KEY,
    cle VARCHAR(100) NOT NULL UNIQUE,
    valeur TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- TABLE : utilisateur
-- ========================================
CREATE TABLE utilisateur (
    utilisateur_id SERIAL PRIMARY KEY,
    pseudo VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse VARCHAR(255),
    date_naissance DATE,
    photo VARCHAR(255) DEFAULT 'default.jpg',
    credit INT DEFAULT 20,
    role VARCHAR(20) DEFAULT 'utilisateur' CHECK (role IN ('utilisateur', 'employe', 'administrateur')),
    statut VARCHAR(20) DEFAULT 'actif' CHECK (statut IN ('actif', 'suspendu')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- TABLE : voiture
-- ========================================
CREATE TABLE voiture (
    voiture_id SERIAL PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    modele VARCHAR(100) NOT NULL,
    marque VARCHAR(100) NOT NULL,
    immatriculation VARCHAR(20) NOT NULL,
    couleur VARCHAR(50),
    energie VARCHAR(20) NOT NULL CHECK (energie IN ('essence', 'diesel', 'electrique', 'hybride', 'gpl')),
    places INT NOT NULL,
    date_premiere_immatriculation DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- ========================================
-- TABLE : parametre
-- ========================================
CREATE TABLE parametre (
    parametre_id SERIAL PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    fumeur BOOLEAN DEFAULT FALSE,
    animaux BOOLEAN DEFAULT FALSE,
    musique BOOLEAN DEFAULT TRUE,
    discussion BOOLEAN DEFAULT TRUE,
    preferences_custom TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- ========================================
-- TABLE : covoiturage
-- ========================================
CREATE TABLE covoiturage (
    covoiturage_id SERIAL PRIMARY KEY,
    conducteur_id INT NOT NULL,
    voiture_id INT NOT NULL,
    ville_depart VARCHAR(100) NOT NULL,
    adresse_depart VARCHAR(255),
    ville_arrivee VARCHAR(100) NOT NULL,
    adresse_arrivee VARCHAR(255),
    date_depart TIMESTAMP NOT NULL,
    date_arrivee TIMESTAMP NOT NULL,
    places_disponibles INT NOT NULL,
    prix_par_place DECIMAL(10, 2) NOT NULL,
    statut VARCHAR(20) DEFAULT 'planifie' CHECK (statut IN ('planifie', 'en_cours', 'termine', 'annule')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conducteur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE RESTRICT,
    FOREIGN KEY (voiture_id) REFERENCES voiture(voiture_id) ON DELETE RESTRICT
);

-- ========================================
-- TABLE : participation
-- ========================================
CREATE TABLE participation (
    participation_id SERIAL PRIMARY KEY,
    covoiturage_id INT NOT NULL,
    passager_id INT NOT NULL,
    nombre_places INT DEFAULT 1,
    credit_utilise INT NOT NULL,
    statut VARCHAR(20) DEFAULT 'reserve' CHECK (statut IN ('reserve', 'confirme', 'annule', 'termine')),
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
    FOREIGN KEY (passager_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    UNIQUE (covoiturage_id, passager_id)
);

-- ========================================
-- TABLE : avis
-- ========================================
CREATE TABLE avis (
    avis_id SERIAL PRIMARY KEY,
    covoiturage_id INT NOT NULL,
    auteur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    commentaire TEXT,
    note INT CHECK (note >= 1 AND note <= 5),
    statut VARCHAR(20) DEFAULT 'en_attente' CHECK (statut IN ('en_attente', 'valide', 'refuse')),
    valide_par INT,
    date_validation TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- ========================================
-- TABLE : transaction_credit
-- ========================================
CREATE TABLE transaction_credit (
    transaction_id SERIAL PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    montant INT NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('credit', 'debit')),
    description VARCHAR(255),
    reference_id INT,
    reference_type VARCHAR(50) CHECK (reference_type IN ('participation', 'remboursement', 'bonus', 'commission')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- ========================================
-- INSERTION DES DONNÉES DE CONFIGURATION
-- ========================================
INSERT INTO configuration (cle, valeur) VALUES
('commission_credit', '2'),
('credit_inscription', '20'),
('email_contact', 'contact@ecoride.fr');

-- ========================================
-- INDEX POUR OPTIMISATION
-- ========================================
CREATE INDEX idx_covoiturage_depart ON covoiturage(ville_depart, date_depart);
CREATE INDEX idx_covoiturage_statut ON covoiturage(statut);
CREATE INDEX idx_utilisateur_email ON utilisateur(email);
CREATE INDEX idx_avis_statut ON avis(statut);
CREATE INDEX idx_participation_statut ON participation(statut);

-- ========================================
-- MESSAGE DE SUCCÈS
-- ========================================
SELECT 'Base de données PostgreSQL créée avec succès - Compatible avec MySQL!' as message;
