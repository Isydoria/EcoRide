<?php
/**
 * Script de correction automatique des tables PostgreSQL
 * URL: https://ecoride-om7c.onrender.com/fix-tables.php
 *
 * ⚠️ ATTENTION : Ce script va supprimer et recréer la table avis !
 */

require_once 'config/init.php';

header('Content-Type: text/html; charset=utf-8');

// Vérifier si confirmation
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

echo "<h1>🔧 Correction des tables PostgreSQL</h1>";
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
    // Afficher page de confirmation
    echo "<div class='danger-zone'>";
    echo "<h2>⚠️ ATTENTION - Zone dangereuse</h2>";
    echo "<p>Ce script va effectuer les actions suivantes :</p>";
    echo "<ul>";
    echo "<li>🗑️ Supprimer la table <code>avis</code> si elle existe (avec toutes les données)</li>";
    echo "<li>🔨 Recréer la table <code>avis</code> avec la structure PostgreSQL correcte</li>";
    echo "<li>🔧 Créer/corriger la table <code>participation</code></li>";
    echo "<li>✅ Ajouter le statut 'terminee' si nécessaire</li>";
    echo "</ul>";
    echo "<p><strong style='color: #e74c3c;'>⚠️ Tous les avis existants seront supprimés !</strong></p>";
    echo "<p>Êtes-vous sûr de vouloir continuer ?</p>";
    echo "<form method='get'>";
    echo "<input type='hidden' name='confirm' value='yes'>";
    echo "<button type='submit' class='btn btn-danger'>🔧 Oui, corriger les tables</button>";
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
    echo "🔧 CORRECTION TABLE AVIS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // 1. Vérifier si avis existe
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = 'avis'
        );
    ");
    $avisExists = $stmt->fetchColumn();

    if ($avisExists) {
        // Compter les avis existants
        $stmt = $pdo->query("SELECT COUNT(*) FROM avis");
        $count = $stmt->fetchColumn();
        echo "📊 Avis existants: $count\n";

        echo "🗑️ Suppression de la table 'avis'...\n";
        $pdo->exec("DROP TABLE IF EXISTS avis CASCADE;");
        echo "<span class='success'>✅ Table supprimée</span>\n\n";
    } else {
        echo "ℹ️ Table 'avis' n'existe pas\n\n";
    }

    // 2. Créer la table avis avec structure PostgreSQL
    echo "🔨 Création de la table 'avis' (structure PostgreSQL)...\n";
    $pdo->exec("
        CREATE TABLE avis (
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
    ");
    echo "<span class='success'>✅ Table 'avis' créée avec succès</span>\n";
    echo "   Colonnes: evaluateur_id, evalue_id, covoiturage_id, note, commentaire, created_at\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔧 CORRECTION TABLE PARTICIPATION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // 3. Vérifier table participation
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = 'participation'
        );
    ");
    $participationExists = $stmt->fetchColumn();

    if (!$participationExists) {
        echo "🔨 Création de la table 'participation'...\n";
        $pdo->exec("
            CREATE TABLE participation (
                participation_id SERIAL PRIMARY KEY,
                covoiturage_id INT NOT NULL,
                passager_id INT NOT NULL,
                places_reservees INT NOT NULL,
                statut_reservation VARCHAR(50) DEFAULT 'en_attente' CHECK (statut_reservation IN ('en_attente', 'confirmee', 'annulee', 'terminee')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE,
                FOREIGN KEY (passager_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE
            );
        ");
        echo "<span class='success'>✅ Table 'participation' créée</span>\n\n";
    } else {
        echo "ℹ️ Table 'participation' existe déjà\n";

        // Vérifier le constraint
        $stmt = $pdo->query("
            SELECT check_clause
            FROM information_schema.check_constraints cc
            JOIN information_schema.constraint_column_usage ccu USING (constraint_name)
            WHERE ccu.table_name = 'participation'
            AND cc.check_clause LIKE '%statut%';
        ");
        $constraint = $stmt->fetchColumn();

        if ($constraint && strpos($constraint, 'terminee') === false) {
            echo "🔧 Ajout du statut 'terminee'...\n";

            // Récupérer le nom du constraint
            $stmt = $pdo->query("
                SELECT constraint_name
                FROM information_schema.check_constraints cc
                JOIN information_schema.constraint_column_usage ccu USING (constraint_name)
                WHERE ccu.table_name = 'participation'
                AND cc.check_clause LIKE '%statut%'
                LIMIT 1;
            ");
            $constraintName = $stmt->fetchColumn();

            if ($constraintName) {
                $pdo->exec("ALTER TABLE participation DROP CONSTRAINT $constraintName;");
                echo "  ✅ Ancien constraint supprimé\n";
            }

            $pdo->exec("
                ALTER TABLE participation
                ADD CONSTRAINT participation_statut_reservation_check
                CHECK (statut_reservation IN ('en_attente', 'confirmee', 'annulee', 'terminee'));
            ");
            echo "  <span class='success'>✅ Nouveau constraint ajouté avec 'terminee'</span>\n\n";
        } else {
            echo "  <span class='success'>✅ Statut 'terminee' déjà présent</span>\n\n";
        }
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ VÉRIFICATION FINALE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Vérifier les colonnes de avis
    $stmt = $pdo->query("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'avis'
        ORDER BY ordinal_position;
    ");
    $avisColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Table 'avis' - Colonnes: " . implode(", ", $avisColumns) . "\n";

    // Vérifier les colonnes de participation
    $stmt = $pdo->query("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'participation'
        ORDER BY ordinal_position;
    ");
    $participationColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Table 'participation' - Colonnes: " . implode(", ", $participationColumns) . "\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "<span class='success'>🎉 CORRECTION TERMINÉE AVEC SUCCÈS !</span>\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "✅ La table 'avis' a été recréée avec la structure PostgreSQL\n";
    echo "✅ La table 'participation' est configurée correctement\n";
    echo "✅ Le système d'avis est maintenant opérationnel\n";

} catch (Exception $e) {
    echo "<span class='error'>❌ ERREUR: " . $e->getMessage() . "</span>\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
}

echo "</pre>";

echo "<hr>";
echo "<h2>📍 Prochaines étapes</h2>";
echo "<p><strong>Le système d'avis est maintenant prêt !</strong></p>";
echo "<p><a href='/user/dashboard.php?section=avis' style='display: inline-block; background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🎉 Tester le système d'avis</a></p>";
echo "<p><a href='/diagnostic-tables.php'>→ Voir le diagnostic</a> | <a href='/'>← Retour accueil</a></p>";
?>
