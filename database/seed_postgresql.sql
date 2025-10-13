-- ========================================
-- DONNÉES DE TEST POUR ECORIDE (PostgreSQL)
-- ========================================

-- UTILISATEURS (avec des mots de passe hashés bcrypt)
-- Mot de passe pour tous : "password"
INSERT INTO utilisateur (pseudo, email, password, role, credits, telephone) VALUES
('admin', 'admin@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrateur', 100, '0601020304');

INSERT INTO utilisateur (pseudo, email, password, role, credits, telephone) VALUES
('employe', 'employe@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employe', 50, '0605060708');

-- Utilisateurs normaux (conducteurs)
INSERT INTO utilisateur (pseudo, email, password, telephone, credits, biographie) VALUES
('jean_dupont', 'jean.dupont@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0612345678', 50, 'Passionné de covoiturage écologique');

INSERT INTO utilisateur (pseudo, email, password, telephone, credits, biographie) VALUES
('marie_martin', 'marie.martin@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0623456789', 50, 'Adepte des trajets partagés');

INSERT INTO utilisateur (pseudo, email, password, telephone, credits, biographie) VALUES
('pierre_bernard', 'pierre.bernard@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0634567890', 50, 'Conducteur expérimenté');

INSERT INTO utilisateur (pseudo, email, password, telephone, credits, biographie) VALUES
('sophie_dubois', 'sophie.dubois@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0645678901', 50, 'Trajets réguliers Paris-Lyon');

INSERT INTO utilisateur (pseudo, email, password, telephone, credits, biographie) VALUES
('luc_petit', 'luc.petit@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0656789012', 50, 'Véhicule électrique');

INSERT INTO utilisateur (pseudo, email, password, telephone, credits, biographie) VALUES
('claire_rousseau', 'claire.rousseau@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0667890123', 50, 'Covoiturage convivial');

-- Passagers
INSERT INTO utilisateur (pseudo, email, password, telephone, credits, biographie) VALUES
('thomas_blanc', 'thomas.blanc@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0678901234', 50, 'Passager régulier');

INSERT INTO utilisateur (pseudo, email, password, telephone, credits, biographie) VALUES
('julie_moreau', 'julie.moreau@email.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0689012345', 50, 'Recherche trajets écologiques');

-- VOITURES
INSERT INTO voiture (utilisateur_id, modele, marque, immatriculation, couleur, places_disponibles, type_vehicule) VALUES
(3, 'Model 3', 'Tesla', 'AB-123-CD', 'Blanc', 4, 'electrique'),
(4, 'Prius', 'Toyota', 'EF-456-GH', 'Gris', 3, 'hybride'),
(5, 'Leaf', 'Nissan', 'IJ-789-KL', 'Bleu', 4, 'electrique'),
(6, 'Zoe', 'Renault', 'MN-012-OP', 'Rouge', 3, 'electrique'),
(7, 'Ioniq', 'Hyundai', 'QR-345-ST', 'Noir', 4, 'hybride'),
(8, 'e-Golf', 'Volkswagen', 'UV-678-WX', 'Vert', 3, 'electrique');

-- PARAMÈTRES UTILISATEURS
INSERT INTO parametre (utilisateur_id, fumeur, animaux, discussion, musique, preferences_supplementaires) VALUES
(3, FALSE, TRUE, TRUE, TRUE, 'J''aime les trajets calmes'),
(4, FALSE, FALSE, TRUE, TRUE, 'Musique douce bienvenue'),
(5, FALSE, TRUE, FALSE, TRUE, 'Préfère le silence'),
(6, FALSE, FALSE, TRUE, TRUE, 'Conversation agréable'),
(7, FALSE, TRUE, TRUE, FALSE, 'Pas de musique'),
(8, FALSE, FALSE, TRUE, TRUE, 'Trajets décontractés');

-- ========================================
-- COVOITURAGES - TRAJETS VARIÉS
-- ========================================

-- TRAJET PARIS → LYON (plusieurs dates et horaires)
INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, places_disponibles, prix_par_place, statut) VALUES
(3, 1, 'Paris', 'Lyon', '2025-10-20', '08:00:00', 3, 25.00, 'disponible'),
(6, 4, 'Paris', 'Lyon', '2025-10-20', '14:00:00', 2, 23.00, 'disponible'),
(3, 1, 'Paris', 'Lyon', '2025-10-25', '09:00:00', 4, 25.00, 'disponible'),
(6, 4, 'Paris', 'Lyon', '2025-11-01', '08:00:00', 3, 24.00, 'disponible'),
(3, 1, 'Paris', 'Lyon', '2025-11-05', '15:00:00', 2, 25.00, 'disponible'),
(6, 4, 'Paris', 'Lyon', '2025-11-15', '10:00:00', 3, 23.00, 'disponible'),
(3, 1, 'Paris', 'Lyon', '2025-12-01', '08:30:00', 4, 26.00, 'disponible'),
(6, 4, 'Paris', 'Lyon', '2025-12-15', '09:00:00', 2, 24.00, 'disponible'),
(3, 1, 'Paris', 'Lyon', '2026-01-10', '08:00:00', 3, 25.00, 'disponible'),
(6, 4, 'Paris', 'Lyon', '2026-01-20', '14:00:00', 3, 25.00, 'disponible'),
(3, 1, 'Paris', 'Lyon', '2026-02-01', '08:00:00', 4, 25.00, 'disponible'),
(6, 4, 'Paris', 'Lyon', '2026-02-14', '10:00:00', 2, 23.00, 'disponible'),
(3, 1, 'Paris', 'Lyon', '2026-02-28', '09:00:00', 3, 25.00, 'disponible');

-- TRAJET MARSEILLE → NICE (plusieurs dates)
INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, places_disponibles, prix_par_place, statut) VALUES
(4, 2, 'Marseille', 'Nice', '2025-10-22', '09:30:00', 2, 15.00, 'disponible'),
(7, 5, 'Marseille', 'Nice', '2025-10-22', '16:00:00', 3, 14.00, 'disponible'),
(4, 2, 'Marseille', 'Nice', '2025-11-05', '09:30:00', 2, 15.00, 'disponible'),
(7, 5, 'Marseille', 'Nice', '2025-11-12', '10:00:00', 4, 14.00, 'disponible'),
(4, 2, 'Marseille', 'Nice', '2025-12-10', '09:00:00', 2, 16.00, 'disponible'),
(7, 5, 'Marseille', 'Nice', '2026-01-15', '09:30:00', 3, 15.00, 'disponible'),
(4, 2, 'Marseille', 'Nice', '2026-02-05', '10:00:00', 2, 15.00, 'disponible'),
(7, 5, 'Marseille', 'Nice', '2026-02-25', '09:30:00', 3, 14.00, 'disponible');

-- TRAJET BORDEAUX → TOULOUSE (plusieurs dates)
INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, places_disponibles, prix_par_place, statut) VALUES
(5, 3, 'Bordeaux', 'Toulouse', '2025-10-24', '14:00:00', 4, 20.00, 'disponible'),
(8, 6, 'Bordeaux', 'Toulouse', '2025-10-24', '18:00:00', 2, 18.00, 'disponible'),
(5, 3, 'Bordeaux', 'Toulouse', '2025-11-10', '14:00:00', 3, 20.00, 'disponible'),
(8, 6, 'Bordeaux', 'Toulouse', '2025-11-20', '15:00:00', 3, 19.00, 'disponible'),
(5, 3, 'Bordeaux', 'Toulouse', '2025-12-05', '14:00:00', 4, 20.00, 'disponible'),
(8, 6, 'Bordeaux', 'Toulouse', '2026-01-08', '13:00:00', 2, 18.00, 'disponible'),
(5, 3, 'Bordeaux', 'Toulouse', '2026-02-10', '14:00:00', 3, 20.00, 'disponible'),
(8, 6, 'Bordeaux', 'Toulouse', '2026-02-20', '15:00:00', 3, 19.00, 'disponible');

-- TRAJET LILLE → PARIS (plusieurs dates)
INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, places_disponibles, prix_par_place, statut) VALUES
(3, 1, 'Lille', 'Paris', '2025-10-23', '07:00:00', 3, 12.00, 'disponible'),
(6, 4, 'Lille', 'Paris', '2025-10-23', '08:30:00', 2, 11.00, 'disponible'),
(3, 1, 'Lille', 'Paris', '2025-11-08', '07:00:00', 4, 12.00, 'disponible'),
(6, 4, 'Lille', 'Paris', '2025-12-12', '08:00:00', 3, 12.00, 'disponible'),
(3, 1, 'Lille', 'Paris', '2026-01-18', '07:00:00', 3, 13.00, 'disponible'),
(6, 4, 'Lille', 'Paris', '2026-02-08', '08:30:00', 2, 12.00, 'disponible'),
(3, 1, 'Lille', 'Paris', '2026-02-22', '07:00:00', 4, 12.00, 'disponible');

-- TRAJET NANTES → RENNES (plusieurs dates)
INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, places_disponibles, prix_par_place, statut) VALUES
(4, 2, 'Nantes', 'Rennes', '2025-10-26', '11:00:00', 2, 10.00, 'disponible'),
(7, 5, 'Nantes', 'Rennes', '2025-10-26', '16:00:00', 3, 9.00, 'disponible'),
(4, 2, 'Nantes', 'Rennes', '2025-11-18', '11:00:00', 2, 10.00, 'disponible'),
(7, 5, 'Nantes', 'Rennes', '2025-12-20', '12:00:00', 4, 10.00, 'disponible'),
(4, 2, 'Nantes', 'Rennes', '2026-01-25', '11:00:00', 2, 11.00, 'disponible'),
(7, 5, 'Nantes', 'Rennes', '2026-02-18', '12:00:00', 3, 10.00, 'disponible');

-- TRAJET STRASBOURG → NANCY (plusieurs dates)
INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, places_disponibles, prix_par_place, statut) VALUES
(5, 3, 'Strasbourg', 'Nancy', '2025-10-28', '13:00:00', 3, 18.00, 'disponible'),
(8, 6, 'Strasbourg', 'Nancy', '2025-11-22', '14:00:00', 2, 17.00, 'disponible'),
(5, 3, 'Strasbourg', 'Nancy', '2025-12-15', '13:00:00', 4, 18.00, 'disponible'),
(8, 6, 'Strasbourg', 'Nancy', '2026-01-30', '14:00:00', 3, 18.00, 'disponible'),
(5, 3, 'Strasbourg', 'Nancy', '2026-02-12', '13:00:00', 3, 18.00, 'disponible');

-- TRAJETS COMPLÉTÉS (passés)
INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, ville_arrivee, date_depart, heure_depart, places_disponibles, prix_par_place, statut) VALUES
(3, 1, 'Paris', 'Lyon', '2025-09-15', '08:00:00', 0, 25.00, 'termine'),
(4, 2, 'Marseille', 'Nice', '2025-09-20', '09:30:00', 0, 15.00, 'termine');

-- ========================================
-- PARTICIPATIONS / RÉSERVATIONS
-- ========================================
INSERT INTO participation (covoiturage_id, passager_id, places_reservees, statut_reservation) VALUES
(1, 9, 1, 'confirmee'),
(1, 10, 1, 'confirmee'),
(4, 9, 2, 'confirmee'),
(10, 10, 1, 'en_attente'),
(16, 9, 1, 'confirmee'),
(55, 9, 2, 'confirmee'),
(55, 10, 1, 'confirmee'),
(56, 9, 1, 'confirmee');

-- ========================================
-- AVIS
-- ========================================
INSERT INTO avis (evaluateur_id, evalue_id, covoiturage_id, note, commentaire) VALUES
(9, 3, 55, 5, 'Excellent conducteur, trajet agréable et ponctuel!'),
(10, 3, 55, 5, 'Véhicule très confortable, je recommande'),
(9, 4, 56, 4, 'Bonne compagnie, trajet sympathique'),
(3, 9, 1, 5, 'Passager agréable et ponctuel');

-- ========================================
-- TRANSACTIONS
-- ========================================
INSERT INTO transaction (utilisateur_id, montant, type_transaction) VALUES
(3, 50, 'achat'),
(4, 50, 'achat'),
(5, 50, 'achat'),
(6, 50, 'achat'),
(7, 50, 'achat'),
(8, 50, 'achat'),
(9, 50, 'achat'),
(10, 50, 'achat'),
(9, -25, 'reservation'),
(10, -15, 'reservation');

-- MESSAGE DE SUCCÈS
SELECT 'Données de test insérées avec succès! Total: ' || 
       (SELECT COUNT(*) FROM covoiturage) || ' covoiturages créés' as message;
