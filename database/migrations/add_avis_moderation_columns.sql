-- Migration: Ajout des colonnes de modération dans la table avis (PostgreSQL)
-- Date: 2025-10-20
-- Description: Ajoute les colonnes statut, valide_par et date_validation
--              pour permettre la modération des avis par les employés

-- Vérifier si les colonnes existent déjà pour éviter les erreurs
DO $$
BEGIN
    -- Ajouter la colonne statut si elle n'existe pas
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'avis' AND column_name = 'statut'
    ) THEN
        ALTER TABLE avis ADD COLUMN statut VARCHAR(20) DEFAULT 'en_attente';
        RAISE NOTICE 'Colonne statut ajoutée';
    ELSE
        RAISE NOTICE 'Colonne statut existe déjà';
    END IF;

    -- Ajouter la colonne valide_par si elle n'existe pas
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'avis' AND column_name = 'valide_par'
    ) THEN
        ALTER TABLE avis ADD COLUMN valide_par INT NULL;
        RAISE NOTICE 'Colonne valide_par ajoutée';
    ELSE
        RAISE NOTICE 'Colonne valide_par existe déjà';
    END IF;

    -- Ajouter la colonne date_validation si elle n'existe pas
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'avis' AND column_name = 'date_validation'
    ) THEN
        ALTER TABLE avis ADD COLUMN date_validation TIMESTAMP NULL;
        RAISE NOTICE 'Colonne date_validation ajoutée';
    ELSE
        RAISE NOTICE 'Colonne date_validation existe déjà';
    END IF;
END $$;

-- Ajouter une contrainte CHECK pour le statut
ALTER TABLE avis DROP CONSTRAINT IF EXISTS avis_statut_check;
ALTER TABLE avis ADD CONSTRAINT avis_statut_check
    CHECK (statut IN ('en_attente', 'valide', 'refuse', 'publie'));

-- Ajouter une clé étrangère vers utilisateur pour valide_par
ALTER TABLE avis DROP CONSTRAINT IF EXISTS avis_valide_par_fkey;
ALTER TABLE avis ADD CONSTRAINT avis_valide_par_fkey
    FOREIGN KEY (valide_par) REFERENCES utilisateur(utilisateur_id) ON DELETE SET NULL;

-- Créer un index sur la colonne statut pour améliorer les performances des requêtes
CREATE INDEX IF NOT EXISTS idx_avis_statut ON avis(statut);

-- Mettre à jour les avis existants pour les marquer comme validés
-- (car ils ont été publiés sans modération)
UPDATE avis SET statut = 'valide' WHERE statut IS NULL OR statut = 'en_attente';

-- Afficher un résumé
DO $$
DECLARE
    total_avis INT;
    avis_valides INT;
BEGIN
    SELECT COUNT(*) INTO total_avis FROM avis;
    SELECT COUNT(*) INTO avis_valides FROM avis WHERE statut = 'valide';

    RAISE NOTICE '=== Migration terminée ===';
    RAISE NOTICE 'Total avis: %', total_avis;
    RAISE NOTICE 'Avis marqués comme validés: %', avis_valides;
END $$;
