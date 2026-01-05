<?php
/**
 * Migration: Harmonisation complète du schéma PostgreSQL avec MySQL
 * À exécuter une seule fois sur Render après déploiement
 */

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("❌ DATABASE_URL non définie - Ce script est pour Render uniquement");
}

try {
    $parts = parse_url($dbUrl);
    $dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) . ";dbname=" . ltrim($parts['path'], '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parts['user'], $parts['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migration Schéma</title>";
    echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}
          h1{color:#2c3e50;}h2{color:#3498db;margin-top:30px;}
          p{background:white;padding:10px;border-left:4px solid #3498db;}
          .success{border-left-color:#27ae60;}.error{border-left-color:#e74c3c;}</style></head><body>";
    echo "<h1>🔧 Migration: Harmonisation Schéma PostgreSQL ↔ MySQL</h1>";

    $pdo->beginTransaction();

    // ==================================================
    // ÉTAPE 1: MODIFIER LA TABLE VOITURE
    // ==================================================
    echo "<h2>📦 Étape 1: Modification table voiture</h2>";

    // Renommer places_disponibles en places
    try {
        $pdo->exec("ALTER TABLE voiture RENAME COLUMN places_disponibles TO places");
        echo "<p class='success'>✅ Colonne 'places_disponibles' renommée en 'places'</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne 'places' déjà existante ou erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Renommer type_vehicule en energie
    try {
        $pdo->exec("ALTER TABLE voiture RENAME COLUMN type_vehicule TO energie");
        echo "<p class='success'>✅ Colonne 'type_vehicule' renommée en 'energie'</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne 'energie' déjà existante ou erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Ajouter colonnes manquantes à voiture
    try {
        $pdo->exec("ALTER TABLE voiture ADD COLUMN IF NOT EXISTS adresse VARCHAR(255)");
        $pdo->exec("ALTER TABLE voiture ADD COLUMN IF NOT EXISTS date_naissance DATE");
        echo "<p class='success'>✅ Colonnes optionnelles ajoutées à voiture</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonnes déjà existantes: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // ==================================================
    // ÉTAPE 2: MODIFIER LA TABLE COVOITURAGE
    // ==================================================
    echo "<h2>🚗 Étape 2: Modification table covoiturage</h2>";

    // Vérifier si date_depart est de type DATE (ancien schéma)
    $result = $pdo->query("
        SELECT data_type
        FROM information_schema.columns
        WHERE table_name = 'covoiturage' AND column_name = 'date_depart'
    ")->fetch();

    if ($result && $result['data_type'] === 'date') {
        echo "<p>🔄 Conversion de DATE+TIME en TIMESTAMP...</p>";

        // Créer colonne temporaire TIMESTAMP
        $pdo->exec("ALTER TABLE covoiturage ADD COLUMN date_depart_new TIMESTAMP");
        $pdo->exec("ALTER TABLE covoiturage ADD COLUMN date_arrivee_new TIMESTAMP");

        // Copier les données en combinant date + heure
        $pdo->exec("
            UPDATE covoiturage
            SET date_depart_new = (date_depart + heure_depart::time)::timestamp
        ");

        // Pour date_arrivee, on estime +2h si elle n'existe pas
        $pdo->exec("
            UPDATE covoiturage
            SET date_arrivee_new = (date_depart + heure_depart::time + INTERVAL '2 hours')::timestamp
        ");

        // Supprimer anciennes colonnes
        $pdo->exec("ALTER TABLE covoiturage DROP COLUMN IF EXISTS date_depart");
        $pdo->exec("ALTER TABLE covoiturage DROP COLUMN IF EXISTS date_arrivee");
        $pdo->exec("ALTER TABLE covoiturage DROP COLUMN IF EXISTS heure_depart");
        $pdo->exec("ALTER TABLE covoiturage DROP COLUMN IF EXISTS heure_arrivee");

        // Renommer nouvelles colonnes
        $pdo->exec("ALTER TABLE covoiturage RENAME COLUMN date_depart_new TO date_depart");
        $pdo->exec("ALTER TABLE covoiturage RENAME COLUMN date_arrivee_new TO date_arrivee");

        echo "<p class='success'>✅ Colonnes date convertiesde DATE+TIME vers TIMESTAMP</p>";
    } else {
        echo "<p>✅ Colonnes date déjà au format TIMESTAMP</p>";
    }

    // Ajouter colonnes manquantes
    try {
        $pdo->exec("ALTER TABLE covoiturage ADD COLUMN IF NOT EXISTS adresse_depart VARCHAR(255)");
        $pdo->exec("ALTER TABLE covoiturage ADD COLUMN IF NOT EXISTS adresse_arrivee VARCHAR(255)");
        echo "<p class='success'>✅ Colonnes adresse ajoutées</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonnes adresse déjà existantes</p>";
    }

    // Supprimer l'ancienne contrainte de statut d'abord
    try {
        $pdo->exec("ALTER TABLE covoiturage DROP CONSTRAINT IF EXISTS covoiturage_statut_check");
        echo "<p class='success'>✅ Ancienne contrainte statut supprimée</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Suppression contrainte: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Mettre à jour les statuts existants AVANT d'ajouter la nouvelle contrainte
    $updated = $pdo->exec("
        UPDATE covoiturage
        SET statut = CASE
            WHEN statut = 'disponible' THEN 'planifie'
            WHEN statut = 'complet' THEN 'planifie'
            ELSE statut
        END
    ");
    echo "<p class='success'>✅ {$updated} trajets mis à jour (disponible/complet → planifie)</p>";

    // Ajouter la nouvelle contrainte de statut APRÈS la mise à jour
    try {
        $pdo->exec("
            ALTER TABLE covoiturage
            ADD CONSTRAINT covoiturage_statut_check
            CHECK (statut IN ('planifie', 'en_cours', 'termine', 'annule'))
        ");
        echo "<p class='success'>✅ Nouvelle contrainte statut ajoutée (planifie, en_cours, termine, annule)</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Ajout contrainte: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // ==================================================
    // ÉTAPE 3: MODIFIER LA TABLE UTILISATEUR
    // ==================================================
    echo "<h2>👤 Étape 3: Modification table utilisateur</h2>";

    // Renommer photo_profil en photo si nécessaire
    try {
        $pdo->exec("ALTER TABLE utilisateur RENAME COLUMN photo_profil TO photo");
        echo "<p class='success'>✅ Colonne 'photo_profil' renommée en 'photo'</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne 'photo' déjà existante</p>";
    }

    // Renommer credits en credit (singulier)
    try {
        $pdo->exec("ALTER TABLE utilisateur RENAME COLUMN credits TO credit");
        echo "<p class='success'>✅ Colonne 'credits' renommée en 'credit'</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne 'credit' déjà existante</p>";
    }

    // Renommer date_inscription en created_at (pour uniformité)
    try {
        $pdo->exec("ALTER TABLE utilisateur ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✅ Colonne 'updated_at' ajoutée</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne updated_at: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // ==================================================
    // ÉTAPE 4: MODIFIER LA TABLE PARTICIPATION
    // ==================================================
    echo "<h2>🎫 Étape 4: Modification table participation</h2>";

    // Renommer statut_reservation en statut
    try {
        $pdo->exec("ALTER TABLE participation RENAME COLUMN statut_reservation TO statut");
        echo "<p class='success'>✅ Colonne 'statut_reservation' renommée en 'statut'</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne 'statut' déjà existante</p>";
    }

    // Renommer places_reservees en nombre_places
    try {
        $pdo->exec("ALTER TABLE participation RENAME COLUMN places_reservees TO nombre_places");
        echo "<p class='success'>✅ Colonne 'places_reservees' renommée en 'nombre_places'</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne 'nombre_places' déjà existante</p>";
    }

    // Ajouter credit_utilise si manquant
    try {
        $pdo->exec("ALTER TABLE participation ADD COLUMN IF NOT EXISTS credit_utilise INT DEFAULT 0");
        echo "<p class='success'>✅ Colonne 'credit_utilise' ajoutée</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne credit_utilise: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Supprimer les anciennes contraintes de statut pour participation
    try {
        $pdo->exec("ALTER TABLE participation DROP CONSTRAINT IF EXISTS participation_statut_check");
        $pdo->exec("ALTER TABLE participation DROP CONSTRAINT IF EXISTS participation_statut_reservation_check");
        echo "<p class='success'>✅ Anciennes contraintes statut participation supprimées</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Suppression contraintes participation: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Mettre à jour les statuts existants de participation AVANT d'ajouter la nouvelle contrainte
    $updatedPart = $pdo->exec("
        UPDATE participation
        SET statut = CASE
            WHEN statut = 'en_attente' THEN 'reserve'
            WHEN statut = 'confirmee' THEN 'confirme'
            WHEN statut = 'annulee' THEN 'annule'
            ELSE statut
        END
    ");
    echo "<p class='success'>✅ {$updatedPart} participations mises à jour</p>";

    // Ajouter la nouvelle contrainte de statut APRÈS la mise à jour
    try {
        $pdo->exec("
            ALTER TABLE participation
            ADD CONSTRAINT participation_statut_check
            CHECK (statut IN ('reserve', 'confirme', 'annule', 'termine'))
        ");
        echo "<p class='success'>✅ Nouvelle contrainte statut participation ajoutée</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Ajout contrainte participation: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // ==================================================
    // ÉTAPE 5: MODIFIER LA TABLE AVIS
    // ==================================================
    echo "<h2>⭐ Étape 5: Modification table avis</h2>";

    // Vérifier si les anciennes colonnes existent
    $checkEvaluateur = $pdo->query("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'avis' AND column_name = 'evaluateur_id'
    ")->fetch();

    if ($checkEvaluateur) {
        echo "<p>🔄 Migration des colonnes evaluateur_id/evalue_id → auteur_id/destinataire_id</p>";

        // Renommer evaluateur_id en auteur_id
        try {
            $pdo->exec("ALTER TABLE avis RENAME COLUMN evaluateur_id TO auteur_id");
            echo "<p class='success'>✅ Colonne 'evaluateur_id' renommée en 'auteur_id'</p>";
        } catch (PDOException $e) {
            echo "<p>ℹ️ Colonne auteur_id: " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        // Renommer evalue_id en destinataire_id
        try {
            $pdo->exec("ALTER TABLE avis RENAME COLUMN evalue_id TO destinataire_id");
            echo "<p class='success'>✅ Colonne 'evalue_id' renommée en 'destinataire_id'</p>";
        } catch (PDOException $e) {
            echo "<p>ℹ️ Colonne destinataire_id: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p>✅ Colonnes avis déjà à jour</p>";
    }

    // Ajouter covoiturage_id si manquant
    try {
        $pdo->exec("ALTER TABLE avis ADD COLUMN IF NOT EXISTS covoiturage_id INT");
        echo "<p class='success'>✅ Colonne 'covoiturage_id' ajoutée</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne covoiturage_id: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Ajouter la colonne statut si elle n'existe pas
    try {
        $pdo->exec("ALTER TABLE avis ADD COLUMN IF NOT EXISTS statut VARCHAR(20) DEFAULT 'valide'");
        echo "<p class='success'>✅ Colonne 'statut' ajoutée à avis</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Colonne statut avis: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Mettre tous les avis existants à 'valide' par défaut
    $updatedAvis = $pdo->exec("UPDATE avis SET statut = 'valide' WHERE statut IS NULL");
    echo "<p class='success'>✅ {$updatedAvis} avis mis à jour avec statut='valide'</p>";

    // ==================================================
    // FINALISATION
    // ==================================================
    $pdo->commit();

    echo "<h2 class='success'>✅ Migration terminée avec succès !</h2>";
    echo "<p><strong>Résumé des modifications :</strong></p>";
    echo "<ul>";
    echo "<li>✅ Table voiture : places_disponibles → places, type_vehicule → energie</li>";
    echo "<li>✅ Table covoiturage : DATE+TIME → TIMESTAMP, statut harmonisé</li>";
    echo "<li>✅ Table utilisateur : photo_profil → photo, credits → credit</li>";
    echo "<li>✅ Table participation : statut_reservation → statut, places_reservees → nombre_places</li>";
    echo "<li>✅ Table avis : evaluateur_id → auteur_id, evalue_id → destinataire_id, ajout statut et covoiturage_id</li>";
    echo "</ul>";
    echo "<p><a href='/'>← Retour à l'accueil</a></p>";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<h2 class='error'>❌ Erreur</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
