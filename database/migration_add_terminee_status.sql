-- ========================================
-- MIGRATION : Ajouter le statut 'terminee' à participation
-- ========================================

-- PostgreSQL : Supprimer et recréer le constraint pour ajouter 'terminee'
ALTER TABLE participation
DROP CONSTRAINT IF EXISTS participation_statut_reservation_check;

ALTER TABLE participation
ADD CONSTRAINT participation_statut_reservation_check
CHECK (statut_reservation IN ('en_attente', 'confirmee', 'annulee', 'terminee'));

SELECT 'Migration terminée : statut terminee ajouté à participation' as message;
