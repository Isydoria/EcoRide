-- ========================================
-- Données de test pour EcoRide
-- ========================================

USE ecoride;

-- Mot de passe pour tous : Test123!
-- Hash généré avec password_hash('Test123!', PASSWORD_DEFAULT)
SET @password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Administrateur
INSERT INTO utilisateur (pseudo, email, password, role, credit) VALUES
('admin', 'admin@ecoride.fr', @password, 'administrateur', 1000);

-- Employés
INSERT INTO utilisateur (pseudo, email, password, role, credit) VALUES
('employe_marie', 'marie.employe@ecoride.fr', @password, 'employe', 100),
('employe_thomas', 'thomas.employe@ecoride.fr', @password, 'employe', 100);

-- Conducteurs
INSERT INTO utilisateur (pseudo, email, password, telephone, adresse, date_naissance, role, credit) VALUES
('jean_eco', 'jean@example.com', @password, '0612345678', '15 rue de la République, Paris', '1985-03-15', 'utilisateur', 50),
('sophie_green', 'sophie@example.com', @password, '0623456789', '28 avenue Victor Hugo, Lyon', '1990-07-22', 'utilisateur', 75),
('pierre_volt', 'pierre@example.com', @password, '0634567890', '42 boulevard Gambetta, Bordeaux', '1988-11-08', 'utilisateur', 100);

-- Passagers
INSERT INTO utilisateur (pseudo, email, password, telephone, role, credit) VALUES
('alex_passager', 'alex@example.com', @password, '0656789012', 'utilisateur', 30),
('emma_voyage', 'emma@example.com', @password, '0667890123', 'utilisateur', 45),
('maxime_rider', 'maxime@example.com', @password, '0678901234', 'utilisateur', 20);

-- Voitures électriques (écologiques)
INSERT INTO voiture (utilisateur_id, modele, marque, immatriculation, couleur, energie, places, date_premiere_immatriculation) VALUES
(4, 'Model 3', 'Tesla', 'AA-123-BB', 'Blanc', 'electrique', 4, '2022-01-15'),
(5, 'Zoe', 'Renault', 'BB-234-CC', 'Bleu', 'electrique', 4, '2021-06-20'),
(6, 'ID.3', 'Volkswagen', 'CC-345-DD', 'Gris', 'electrique', 4, '2023-03-10');

-- Voiture hybride
INSERT INTO voiture (utilisateur_id, modele, marque, immatriculation, couleur, energie, places, date_premiere_immatriculation) VALUES
(4, 'Prius', 'Toyota', 'DD-456-EE', 'Argent', 'hybride', 4, '2020-09-05');

-- Voitures essence/diesel
INSERT INTO voiture (utilisateur_id, modele, marque, immatriculation, couleur, energie, places, date_premiere_immatriculation) VALUES
(5, 'Clio', 'Renault', 'EE-567-FF', 'Rouge', 'essence', 4, '2019-11-12');

-- Paramètres conducteurs
INSERT INTO parametre (utilisateur_id, fumeur, animaux, musique, discussion, preferences_custom) VALUES
(4, FALSE, TRUE, TRUE, TRUE, 'J\'aime discuter pendant les trajets'),
(5, FALSE, FALSE, TRUE, FALSE, 'Trajets calmes, musique douce'),
(6, FALSE, TRUE, FALSE, TRUE, 'Pas de musique mais discussion OK');

-- Covoiturages à venir (dates relatives à partir d'aujourd'hui)
INSERT INTO covoiturage (conducteur_id, voiture_id, ville_depart, adresse_depart, ville_arrivee, adresse_arrivee, date_depart, date_arrivee, places_disponibles, prix_par_place, statut) VALUES
-- Trajets écologiques
(4, 1, 'Paris', 'Gare du Nord', 'Lyon', 'Gare Part-Dieu', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL '2 2:30' DAY_MINUTE), 3, 25.00, 'planifie'),
(5, 2, 'Lyon', 'Bellecour', 'Marseille', 'Vieux-Port', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL '3 3:15' DAY_MINUTE), 2, 20.00, 'planifie'),
(6, 3, 'Bordeaux', 'Place Gambetta', 'Toulouse', 'Capitole', DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_ADD(NOW(), INTERVAL '4 2:30' DAY_MINUTE), 3, 15.00, 'planifie'),
-- Trajets hybrides
(4, 4, 'Paris', 'République', 'Orleans', 'Centre', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL '5 1:30' DAY_MINUTE), 3, 10.00, 'planifie'),
-- Trajets essence
(5, 5, 'Lyon', 'Perrache', 'Grenoble', 'Gare', DATE_ADD(NOW(), INTERVAL 6 DAY), DATE_ADD(NOW(), INTERVAL '6 1:15' DAY_MINUTE), 2, 8.00, 'planifie');

-- Participations (réservations)
INSERT INTO participation (covoiturage_id, passager_id, nombre_places, credit_utilise, statut) VALUES
(1, 7, 1, 27, 'reserve'),
(2, 8, 1, 22, 'reserve'),
(3, 9, 1, 17, 'reserve');

-- Avis validés
INSERT INTO avis (covoiturage_id, auteur_id, destinataire_id, commentaire, note, statut, valide_par, date_validation) VALUES
(1, 7, 4, 'Super conducteur, très ponctuel!', 5, 'valide', 2, NOW()),
(2, 8, 5, 'Trajet agréable, je recommande', 4, 'valide', 2, NOW());

-- Avis en attente
INSERT INTO avis (covoiturage_id, auteur_id, destinataire_id, commentaire, note, statut) VALUES
(3, 9, 6, 'Excellente expérience', 5, 'en_attente');

-- Transactions de crédit
INSERT INTO transaction_credit (utilisateur_id, montant, type, description, reference_type) VALUES
(1, 1000, 'credit', 'Crédit initial admin', 'bonus'),
(4, 20, 'credit', 'Crédit de bienvenue', 'bonus'),
(5, 20, 'credit', 'Crédit de bienvenue', 'bonus'),
(6, 20, 'credit', 'Crédit de bienvenue', 'bonus'),
(7, 20, 'credit', 'Crédit de bienvenue', 'bonus'),
(8, 20, 'credit', 'Crédit de bienvenue', 'bonus'),
(9, 20, 'credit', 'Crédit de bienvenue', 'bonus'),
(4, 30, 'credit', 'Achat de crédits', 'bonus'),
(5, 55, 'credit', 'Achat de crédits', 'bonus'),
(6, 80, 'credit', 'Achat de crédits', 'bonus');

-- Message de confirmation
SELECT 'Données de test importées!' as Message;
