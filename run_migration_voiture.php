<?php
/**
 * Script pour exécuter la migration: ajouter created_at à voiture
 * À exécuter UNE SEULE FOIS sur l'environnement PostgreSQL (Render)
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== MIGRATION: Ajouter created_at à voiture ===\n\n";

require_once 'config/init.php';

try {
    $pdo = db();

    // Vérifier qu'on est bien sur PostgreSQL
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Driver détecté: $driver\n\n";

    if ($driver !== 'pgsql') {
        die("❌ ERREUR: Cette migration est uniquement pour PostgreSQL.\nVous êtes sur $driver\n");
    }

    // Vérifier si la colonne existe déjà
    $stmt = $pdo->query("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'voiture' AND column_name = 'created_at'
    ");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "⚠️  La colonne created_at existe déjà dans la table voiture.\n";
        echo "Migration déjà appliquée ou inutile.\n";
        exit;
    }

    echo "✅ La colonne created_at n'existe pas encore. Début de la migration...\n\n";

    // Commencer une transaction
    $pdo->beginTransaction();

    try {
        // Étape 1: Ajouter la colonne
        echo "1. Ajout de la colonne created_at...\n";
        $pdo->exec("
            ALTER TABLE voiture
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ");
        echo "   ✅ Colonne ajoutée\n\n";

        // Étape 2: Mettre à jour les lignes existantes
        echo "2. Mise à jour des véhicules existants...\n";
        $stmt = $pdo->exec("
            UPDATE voiture
            SET created_at = CURRENT_TIMESTAMP
            WHERE created_at IS NULL
        ");
        echo "   ✅ $stmt véhicules mis à jour\n\n";

        // Étape 3: Rendre la colonne NOT NULL
        echo "3. Application de la contrainte NOT NULL...\n";
        $pdo->exec("
            ALTER TABLE voiture
            ALTER COLUMN created_at SET NOT NULL
        ");
        echo "   ✅ Contrainte NOT NULL ajoutée\n\n";

        // Valider la transaction
        $pdo->commit();

        echo "=== MIGRATION TERMINÉE AVEC SUCCÈS ===\n\n";

        // Vérification finale
        echo "Vérification finale:\n";
        $stmt = $pdo->query("
            SELECT column_name, data_type, is_nullable, column_default
            FROM information_schema.columns
            WHERE table_name = 'voiture' AND column_name = 'created_at'
        ");
        $col = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($col) {
            echo "✅ Colonne created_at:\n";
            echo "   - Type: {$col['data_type']}\n";
            echo "   - Nullable: {$col['is_nullable']}\n";
            echo "   - Défaut: {$col['column_default']}\n";
        }

        // Compter les véhicules
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM voiture");
        $count = $stmt->fetch()['count'];
        echo "\n✅ Total véhicules dans la table: $count\n";

        echo "\n🎉 La table voiture est maintenant synchronisée avec MySQL!\n";
        echo "Vous pouvez maintenant utiliser ORDER BY created_at DESC dans vos requêtes.\n";

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    echo "\n❌ ERREUR PDO: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== Script terminé ===\n";
?>
