-- ========================================
-- VÉRIFIER ET CRÉER LA TABLE AVIS SI NÉCESSAIRE
-- ========================================

-- Créer la table avis si elle n'existe pas
CREATE TABLE IF NOT EXISTS avis (
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

SELECT 'Table avis vérifiée/créée avec succès!' as message;
