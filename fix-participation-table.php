<?php
/**
 * Script de correction de la table participation pour PostgreSQL
 * URL: https://ecoride-om7c.onrender.com/fix-participation-table.php
 *
 * ⚠️ ATTENTION : Ce script va supprimer et recréer la table participation !
 */

require_once 'config/init.php';

header('Content-Type: text/html; charset=utf-8');

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

echo "<h1>🔧 Correction table PARTICIPATION</h1>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    pre { background: white; padding: 15px; border-radius: 8px; border: 1px solid #ddd; }
    .success { color: #27ae60; font-weight: bold; }
    .error { color: #e74c3c; font-weight: bold; }
    .warning { color: #f39c12; font-weight: bold; }
    .danger-zone { background: #ffe6e6; border: 2px solid #e74c3c; padding: 20px; border-radius: 8px; margin: 20px 0; }
    .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .btn-danger { background: #e74c3c; color: white; }
    .btn-danger:hover { background: #c0392b; }
    .btn-cancel { background: #95a5a6; color: white; }
    .btn-cancel:hover { background: #7f8c8d; }
</style>";

if (!$confirm) {
    echo "<div class='danger-zone'>";
    echo "<h2>⚠️ ATTENTION - Zone dangereuse</h2>";
    echo "<p><strong>Problème détecté :</strong></p>";
    echo "<p>La table <code>participation</code> utilise une ancienne structure avec :</p>";
    echo "<ul>";
    echo "<li>❌ <code>id_trajet</code> au lieu de <code>covoiturage_id</code></li>";
    echo "<li>❌ <code>id_passager</code> au lieu de <code>passager_id</code></li>";
    echo "<li>❌ <code>statut</code> au lieu de <code>statut_reservation</code></li>";
    echo "</ul>";
    echo "<p><strong>Ce script va :</strong></p>";
    echo "<ul>";
    echo "<li>🗑️ Supprimer la table <code>participation</code> (avec toutes les réservations)</li>";
    echo "<li>🔨 Recréer la table avec la structure PostgreSQL correcte</li>";
    echo "<li>✅ Ajouter les bons noms de colonnes et contraintes</li>";
    echo "</ul>";
    echo "<p><strong style='color: #e74c3c;'>⚠️ Toutes les réservations existantes seront supprimées !</strong></p>";
    echo "<p>Êtes-vous sûr de vouloir continuer ?</p>";
    echo "<form method='get'>";
    echo "<input type='hidden' name='confirm' value='yes'>";
    echo "<button type='submit' class='btn btn-danger'>🔧 Oui, corriger la table</button>";
    echo "<a href='/diagnostic-tables.php'><button type='button' class='btn btn-cancel'>❌ Annuler</button></a>";
    echo "</form>";
    echo "</div>";

    echo "<p><a href='/diagnostic-tables.php'>← Retour au diagnostic</a></p>";
    exit;
}

echo "<pre>";

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    echo "✅ Connexion établie (Driver: $driver)\n\n";

    if ($driver !== 'pgsql') {
        die("<span class='error'>❌ Ce script est uniquement pour PostgreSQL</span>");
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔧 CORRECTION TABLE PARTICIPATION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // 1. Afficher structure actuelle
    echo "📋 Structure actuelle:\n";
    $stmt = $pdo->query("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'participation'
        ORDER BY ordinal_position;
    ");
    $currentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($currentColumns as $col) {
        echo "   - {$col['column_name']} ({$col['data_type']})\n";
    }
    echo "\n";

    // 2. Compter les participations
    $stmt = $pdo->query("SELECT COUNT(*) FROM participation");
    $count = $stmt->fetchColumn();
    echo "📊 Participations existantes: $count\n\n";

    // 3. Supprimer la table
    echo "🗑️ Suppression de la table 'participation'...\n";
    $pdo->exec("DROP TABLE IF EXISTS participation CASCADE;");
    echo "<span class='success'>✅ Table supprimée</span>\n\n";

    // 4. Recréer la table avec bonne structure
    echo "🔨 Création de la table 'participation' (structure PostgreSQL correcte)...\n";
    $pdo->exec("
        CREATE TABLE participation (
            participation_id SERIAL PRIMARY KEY,
            covoiturage_id INT NOT NULL,
            passager_id INT NOT NULL,
            places_reservees INT NOT NULL DEFAULT 1,
            statut_reservation VARCHAR(50) DEFAULT 'en_attente'
                CHECK (statut_reservation IN ('en_attente', 'confirmee', 'annulee', 'terminee')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
            FOREIGN KEY (passager_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
        );
    ");
    echo "<span class='success'>✅ Table 'participation' créée avec succès</span>\n\n";

    // 5. Afficher nouvelle structure
    echo "📋 Nouvelle structure:\n";
    $stmt = $pdo->query("
        SELECT column_name, data_type, column_default
        FROM information_schema.columns
        WHERE table_name = 'participation'
        ORDER BY ordinal_position;
    ");
    $newColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($newColumns as $col) {
        $default = $col['column_default'] ? " (default: {$col['column_default']})" : '';
        echo "   ✅ {$col['column_name']} ({$col['data_type']})$default\n";
    }
    echo "\n";

    // 6. Vérifier les contraintes
    echo "🔒 Vérification des contraintes:\n";
    $stmt = $pdo->query("
        SELECT constraint_name, constraint_type
        FROM information_schema.table_constraints
        WHERE table_name = 'participation'
        ORDER BY constraint_type;
    ");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($constraints as $constraint) {
        echo "   ✅ {$constraint['constraint_name']} ({$constraint['constraint_type']})\n";
    }
    echo "\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "<span class='success'>🎉 CORRECTION TERMINÉE AVEC SUCCÈS !</span>\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "✅ La table 'participation' utilise maintenant:\n";
    echo "   • covoiturage_id (au lieu de id_trajet)\n";
    echo "   • passager_id (au lieu de id_passager)\n";
    echo "   • statut_reservation (au lieu de statut)\n";
    echo "   • places_reservees (au lieu de nombre_places)\n";
    echo "   • Statuts: en_attente, confirmee, annulee, terminee\n\n";

    echo "✅ Le système d'avis peut maintenant fonctionner correctement !\n";

} catch (Exception $e) {
    echo "<span class='error'>❌ ERREUR: " . $e->getMessage() . "</span>\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
}

echo "</pre>";

echo "<hr>";
echo "<h2>📍 Prochaines étapes</h2>";
echo "<p><strong>Les tables sont maintenant correctement configurées !</strong></p>";
echo "<ul>";
echo "<li>✅ Table 'avis' : structure PostgreSQL</li>";
echo "<li>✅ Table 'participation' : structure PostgreSQL</li>";
echo "</ul>";
echo "<p><a href='/user/dashboard.php?section=avis' style='display: inline-block; background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🎉 Tester le système d'avis</a></p>";
echo "<p><a href='/diagnostic-tables.php'>→ Voir le diagnostic complet</a> | <a href='/'>← Retour accueil</a></p>";
?>
