<?php
/**
 * Script d'initialisation de la table avis sur PostgreSQL Render
 * À exécuter une seule fois via : https://ecoride-om7c.onrender.com/init-avis-table.php
 */

require_once 'config/init.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Initialisation table AVIS - PostgreSQL Render</h1>";
echo "<pre>";

try {
    $pdo = db();

    // Vérifier le driver
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "✅ Driver détecté : " . $driver . "\n\n";

    if ($driver !== 'pgsql') {
        die("❌ Ce script est uniquement pour PostgreSQL (Render)");
    }

    // 1. Vérifier si la table avis existe
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'avis'
        );
    ");
    $avisExists = $stmt->fetchColumn();

    echo "📋 Table avis existe : " . ($avisExists ? "OUI ✅" : "NON ❌") . "\n";

    // 2. Vérifier si la table participation existe
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'participation'
        );
    ");
    $participationExists = $stmt->fetchColumn();

    echo "📋 Table participation existe : " . ($participationExists ? "OUI ✅" : "NON ❌") . "\n\n";

    // 3. Créer la table avis si elle n'existe pas
    if (!$avisExists) {
        echo "🔨 Création de la table avis...\n";
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
        echo "✅ Table avis créée avec succès !\n\n";
    } else {
        echo "ℹ️ Table avis existe déjà, vérification des colonnes...\n";

        // Vérifier les colonnes
        $stmt = $pdo->query("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'avis'
            ORDER BY ordinal_position;
        ");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   Colonnes : " . implode(", ", $columns) . "\n\n";
    }

    // 4. Créer/Vérifier la table participation
    if (!$participationExists) {
        echo "🔨 Création de la table participation...\n";
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
        echo "✅ Table participation créée avec succès !\n\n";
    } else {
        echo "ℹ️ Table participation existe déjà, vérification des colonnes...\n";

        // Vérifier les colonnes
        $stmt = $pdo->query("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'participation'
            ORDER BY ordinal_position;
        ");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   Colonnes : " . implode(", ", $columns) . "\n\n";

        // Vérifier si 'terminee' est dans le constraint
        $stmt = $pdo->query("
            SELECT check_clause
            FROM information_schema.check_constraints
            WHERE constraint_name LIKE '%statut_reservation%';
        ");
        $constraint = $stmt->fetchColumn();

        if ($constraint && strpos($constraint, 'terminee') === false) {
            echo "🔧 Ajout du statut 'terminee' au constraint...\n";
            try {
                $pdo->exec("
                    ALTER TABLE participation
                    DROP CONSTRAINT IF EXISTS participation_statut_reservation_check;
                ");
                $pdo->exec("
                    ALTER TABLE participation
                    ADD CONSTRAINT participation_statut_reservation_check
                    CHECK (statut_reservation IN ('en_attente', 'confirmee', 'annulee', 'terminee'));
                ");
                echo "✅ Statut 'terminee' ajouté avec succès !\n\n";
            } catch (Exception $e) {
                echo "⚠️ Erreur ajout constraint : " . $e->getMessage() . "\n\n";
            }
        } else {
            echo "✅ Statut 'terminee' déjà présent dans le constraint\n\n";
        }
    }

    // 5. Test final : compter les tables
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = 'public'
        AND table_name IN ('avis', 'participation');
    ");
    $count = $stmt->fetchColumn();

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🎉 INITIALISATION TERMINÉE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Tables présentes : $count / 2\n";

    if ($count == 2) {
        echo "✅ Toutes les tables sont créées !\n";
        echo "\n📍 Vous pouvez maintenant tester le système d'avis :\n";
        echo "   → https://ecoride-om7c.onrender.com/user/dashboard.php?section=avis\n";
    } else {
        echo "⚠️ Certaines tables sont manquantes, vérifiez les logs ci-dessus.\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
    echo "\nTrace : \n" . $e->getTraceAsString();
}

echo "</pre>";
echo "<hr>";
echo "<p><a href='/user/dashboard.php?section=avis'>→ Tester le système d'avis</a></p>";
echo "<p><a href='/'>← Retour à l'accueil</a></p>";
?>
