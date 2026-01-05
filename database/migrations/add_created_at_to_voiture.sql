-- Migration: Ajouter la colonne created_at à la table voiture
-- Date: 2026-01-05
-- Raison: Synchroniser le schéma PostgreSQL avec MySQL
-- La table voiture sur PostgreSQL n'a pas la colonne created_at qui existe dans le schéma de référence

-- Ajouter la colonne created_at avec une valeur par défaut
ALTER TABLE voiture
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Mettre à jour les lignes existantes avec la date actuelle
-- (pour les véhicules déjà créés sans cette colonne)
UPDATE voiture
SET created_at = CURRENT_TIMESTAMP
WHERE created_at IS NULL;

-- Rendre la colonne NOT NULL après avoir rempli les valeurs existantes
ALTER TABLE voiture
ALTER COLUMN created_at SET NOT NULL;
