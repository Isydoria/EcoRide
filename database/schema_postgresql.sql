
-- ========================================
-- SCHÉMA POSTGRESQL POUR ECORIDE
-- ========================================

-- Suppression des tables existantes (ordre inversé pour les contraintes)
DROP TABLE IF EXISTS transaction CASCADE;
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
    valeur TEXT
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
    photo_profil VARCHAR(255),
    biographie TEXT,
    credits INT DEFAULT 0,
    role VARCHAR(50) DEFAULT 'utilisateur' CHECK (role IN ('utilisateur', 'employe', 'administrateur')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    places_disponibles INT NOT NULL,
    photo_vehicule VARCHAR(255),
    type_vehicule VARCHAR(50) CHECK (type_vehicule IN ('electrique', 'hybride', 'essence', 'diesel')),
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
    discussion BOOLEAN DEFAULT TRUE,
    musique BOOLEAN DEFAULT TRUE,
    preferences_supplementaires TEXT,
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
    ville_arrivee VARCHAR(100) NOT NULL,
    date_depart DATE NOT NULL,
    heure_depart TIME NOT NULL,
    places_disponibles INT NOT NULL,
    prix_par_place DECIMAL(10, 2) NOT NULL,
    statut VARCHAR(50) DEFAULT 'disponible' CHECK (statut IN ('disponible', 'complet', 'annule', 'termine')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conducteur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (voiture_id) REFERENCES voiture(voiture_id) ON DELETE CASCADE
);

-- ========================================
-- TABLE : participation
-- ========================================
CREATE TABLE participation (
    participation_id SERIAL PRIMARY KEY,
    covoiturage_id INT NOT NULL,
    passager_id INT NOT NULL,
    places_reservees INT NOT NULL,
    statut_reservation VARCHAR(50) DEFAULT 'en_attente' CHECK (statut_reservation IN ('en_attente', 'confirmee', 'annulee')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
    FOREIGN KEY (passager_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- ========================================
-- TABLE : avis
-- ========================================
CREATE TABLE avis (
    avis_id SERIAL PRIMARY KEY,
    evaluateur_id INT NOT NULL,
    evalue_id INT NOT NULL,
    covoiturage_id INT NOT NULL,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (evalue_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE
);

-- ========================================
-- TABLE : transaction
-- ========================================
CREATE TABLE transaction (
    transaction_id SERIAL PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    montant INT NOT NULL,
    type_transaction VARCHAR(50) CHECK (type_transaction IN ('achat', 'reservation', 'remboursement')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- ========================================
-- INSERTION DES DONNÉES DE CONFIGURATION
-- ========================================
INSERT INTO configuration (cle, valeur) VALUES
('credits_inscription', '50'),
('prix_credit', '1.00'),
('credits_covoiturage_complet', '10');

-- ========================================
-- MESSAGE DE SUCCÈS
-- ========================================
SELECT 'Base de données créée avec succès!' as message;

-- ========================================
-- FONCTION : Calculer les crédits gagnés
-- ========================================
CREATE OR REPLACE FUNCTION calculer_credits_gagnes(places INT)
RETURNS INT AS $$
BEGIN
    RETURN places * 5;
END;
$$ LANGUAGE plpgsql;

-- ========================================
-- INDEX POUR OPTIMISATION
-- ========================================
CREATE INDEX idx_covoiturage_depart ON covoiturage(ville_depart, date_depart);
CREATE INDEX idx_utilisateur_email ON utilisateur(email);