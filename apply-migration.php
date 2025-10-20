<?php
/**
 * Script d'application de la migration des colonnes de modération
 * À exécuter UNE SEULE FOIS pour ajouter les colonnes statut, valide_par, date_validation
 *
 * Accéder via : https://votre-domaine.com/apply-migration.php
 *
 * ATTENTION : Supprimer ce fichier après utilisation pour des raisons de sécurité
 */

require_once 'config/init.php';

// Mot de passe de sécurité - CHANGEZ-LE !
$SECRET_PASSWORD = 'migration2025'; // Changez ce mot de passe !

// Vérifier le mot de passe
if (!isset($_GET['password']) || $_GET['password'] !== $SECRET_PASSWORD) {
    die('❌ Accès refusé. Utilisez ?password=VOTRE_MOT_DE_PASSE');
}

echo "<h1>🔧 Application de la migration - Système de modération des avis</h1>";
echo "<hr>";

try {
    $pdo = db();

    // Vérifier le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver !== 'pgsql') {
        die("⚠️ Cette migration est uniquement pour PostgreSQL. Votre base est : $driver");
    }

    echo "<h2>📊 Base de données : PostgreSQL</h2>";

    // Lire le fichier de migration
    $migrationFile = __DIR__ . '/database/migrations/add_avis_moderation_columns.sql';

    if (!file_exists($migrationFile)) {
        die("❌ Fichier de migration introuvable : $migrationFile");
    }

    $migration = file_get_contents($migrationFile);

    echo "<h2>🚀 Exécution de la migration...</h2>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";

    // Exécuter la migration
    $pdo->exec($migration);

    echo "✅ Migration exécutée avec succès !\n\n";

    // Vérifier que les colonnes ont été ajoutées
    echo "<h2>🔍 Vérification des colonnes ajoutées...</h2>";

    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'avis'
        AND column_name IN ('statut', 'valide_par', 'date_validation')
        ORDER BY column_name
    ");

    $colonnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($colonnes) === 3) {
        echo "✅ Les 3 colonnes ont été ajoutées avec succès :\n\n";
        foreach ($colonnes as $col) {
            echo "  • {$col['column_name']} ({$col['data_type']}) - ";
            echo "NULL: {$col['is_nullable']} - ";
            echo "Default: " . ($col['column_default'] ?: 'NULL') . "\n";
        }
    } else {
        echo "⚠️ Seulement " . count($colonnes) . " colonne(s) trouvée(s)\n";
    }

    // Statistiques des avis
    echo "\n<h2>📈 Statistiques des avis après migration...</h2>";

    $stmt = $pdo->query("
        SELECT statut, COUNT(*) as nombre
        FROM avis
        GROUP BY statut
        ORDER BY statut
    ");

    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($stats)) {
        echo "Distribution des avis par statut :\n\n";
        foreach ($stats as $stat) {
            echo "  • {$stat['statut']} : {$stat['nombre']} avis\n";
        }
    } else {
        echo "ℹ️ Aucun avis dans la base de données\n";
    }

    // Vérifier l'index
    echo "\n<h2>🔎 Vérification de l'index...</h2>";

    $stmt = $pdo->query("
        SELECT indexname
        FROM pg_indexes
        WHERE tablename = 'avis' AND indexname = 'idx_avis_statut'
    ");

    if ($stmt->fetch()) {
        echo "✅ Index idx_avis_statut créé avec succès\n";
    } else {
        echo "⚠️ Index idx_avis_statut non trouvé\n";
    }

    echo "</pre>";

    echo "<hr>";
    echo "<h2>✅ Migration terminée avec succès !</h2>";
    echo "<p><strong>Actions suivantes :</strong></p>";
    echo "<ol>";
    echo "<li>Supprimez ce fichier (apply-migration.php) pour des raisons de sécurité</li>";
    echo "<li>Rechargez le dashboard employé pour voir les avis en attente</li>";
    echo "<li>Testez la modération des avis (approuver/refuser)</li>";
    echo "</ol>";

    echo "<p><a href='employee/dashboard.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>→ Aller au Dashboard Employé</a></p>";

} catch (PDOException $e) {
    echo "</pre>";
    echo "<h2 style='color: red;'>❌ Erreur lors de la migration</h2>";
    echo "<pre style='background: #fee; padding: 15px; border-radius: 5px; color: red;'>";
    echo $e->getMessage();
    echo "</pre>";
    echo "<p><strong>💡 Conseils de dépannage :</strong></p>";
    echo "<ul>";
    echo "<li>Vérifiez que l'utilisateur PostgreSQL a les droits ALTER TABLE</li>";
    echo "<li>Vérifiez que les colonnes n'existent pas déjà (erreur normale si migration déjà appliquée)</li>";
    echo "<li>Consultez les logs PostgreSQL pour plus de détails</li>";
    echo "</ul>";
}
?>
