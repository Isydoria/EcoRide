<?php
/**
 * Script d'insertion d'avis de démonstration sur PostgreSQL Render
 * URL: https://ecoride-om7c.onrender.com/seed-avis-demo.php
 */

require_once 'config/init.php';

header('Content-Type: text/html; charset=utf-8');

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

echo "<h1>🌱 Insertion d'avis de démonstration</h1>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    pre { background: white; padding: 15px; border-radius: 8px; border: 1px solid #ddd; }
    .success { color: #27ae60; font-weight: bold; }
    .error { color: #e74c3c; font-weight: bold; }
    .info { color: #3498db; font-weight: bold; }
    .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .btn-primary { background: #3498db; color: white; }
    .btn-primary:hover { background: #2980b9; }
    .btn-cancel { background: #95a5a6; color: white; }
    .btn-cancel:hover { background: #7f8c8d; }
</style>";

if (!$confirm) {
    echo "<div style='background: #e8f4f8; border: 2px solid #3498db; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>ℹ️ Création d'avis de démonstration</h2>";
    echo "<p>Ce script va créer environ <strong>10-15 avis de test</strong> entre les utilisateurs existants.</p>";
    echo "<p><strong>Conditions requises :</strong></p>";
    echo "<ul>";
    echo "<li>✅ Au moins 3 utilisateurs dans la base</li>";
    echo "<li>✅ Au moins 5 trajets (covoiturage) terminés</li>";
    echo "<li>✅ Table 'avis' vide ou avec peu d'avis</li>";
    echo "</ul>";
    echo "<p><strong>Avis qui seront créés :</strong></p>";
    echo "<ul>";
    echo "<li>Notes variées : de 3 à 5 étoiles</li>";
    echo "<li>Commentaires réalistes en français</li>";
    echo "<li>Dates échelonnées (derniers 30 jours)</li>";
    echo "</ul>";
    echo "<p>Voulez-vous continuer ?</p>";
    echo "<form method='get'>";
    echo "<input type='hidden' name='confirm' value='yes'>";
    echo "<button type='submit' class='btn btn-primary'>🌱 Créer les avis de démo</button>";
    echo "<a href='/user/dashboard.php'><button type='button' class='btn btn-cancel'>❌ Annuler</button></a>";
    echo "</form>";
    echo "</div>";
    exit;
}

echo "<pre>";

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    echo "✅ Connexion établie (Driver: $driver)\n\n";

    if ($driver !== 'pgsql') {
        die("<span class='error'>❌ Ce script est uniquement pour PostgreSQL (Render)</span>");
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔍 VÉRIFICATION DES DONNÉES\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Compter les utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateur");
    $userCount = $stmt->fetchColumn();
    echo "👥 Utilisateurs: $userCount\n";

    if ($userCount < 3) {
        die("<span class='error'>❌ Il faut au moins 3 utilisateurs pour créer des avis</span>");
    }

    // Compter les trajets
    $stmt = $pdo->query("SELECT COUNT(*) FROM covoiturage");
    $tripCount = $stmt->fetchColumn();
    echo "🚗 Trajets: $tripCount\n";

    if ($tripCount < 3) {
        die("<span class='error'>❌ Il faut au moins 3 trajets pour créer des avis</span>");
    }

    // Compter les avis existants
    $stmt = $pdo->query("SELECT COUNT(*) FROM avis");
    $existingAvis = $stmt->fetchColumn();
    echo "⭐ Avis existants: $existingAvis\n\n";

    // Récupérer quelques utilisateurs
    $stmt = $pdo->query("SELECT utilisateur_id, pseudo FROM utilisateur ORDER BY utilisateur_id LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer quelques trajets
    $stmt = $pdo->query("
        SELECT covoiturage_id, id_conducteur as conducteur_id, ville_depart, ville_arrivee
        FROM covoiturage
        ORDER BY covoiturage_id
        LIMIT 10
    ");
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🌱 CRÉATION DES AVIS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Commentaires de démonstration
    $commentaires = [
        [5, "Excellent conducteur ! Très ponctuel et sympathique. Trajet agréable et sécuritaire."],
        [5, "Parfait ! Voiture propre, conduite souple. Je recommande vivement ce covoitureur."],
        [4, "Très bon trajet. Conducteur aimable et respectueux. Quelques petits retards mais rien de grave."],
        [4, "Bonne expérience globale. Conversation agréable pendant le trajet."],
        [5, "Top ! Conducteur professionnel, à l'heure et courtois. Je referai volontiers un trajet avec lui."],
        [3, "Trajet correct mais conducteur un peu pressé. Conduite un peu sportive à mon goût."],
        [4, "Bien dans l'ensemble. Véhicule confortable et trajet dans les temps."],
        [5, "Excellente expérience ! Musique au top et ambiance détendue. Merci !"],
        [4, "Conducteur sympathique et ponctuel. Petit bémol sur la propreté du véhicule mais rien de grave."],
        [5, "Parfait de A à Z ! Communication facile, horaires respectés, conduite sécuritaire. 5 étoiles méritées !"],
        [4, "Très satisfait du trajet. Conducteur discret et respectueux."],
        [5, "Super expérience ! Le conducteur est très arrangeant et la voiture est impeccable."],
        [3, "Trajet acceptable mais conducteur pas très bavard. On est bien arrivé c'est le principal."],
        [4, "Bon covoiturage. Rien à redire sur la ponctualité et le trajet."],
        [5, "Excellent ! Je recommande ce conducteur les yeux fermés. Très professionnel."]
    ];

    $inserted = 0;
    $skipped = 0;

    // Créer des avis
    foreach ($trips as $index => $trip) {
        if ($inserted >= 15) break;

        // Sélectionner un évaluateur différent du conducteur
        $evaluateur = null;
        foreach ($users as $user) {
            if ($user['utilisateur_id'] != $trip['conducteur_id']) {
                $evaluateur = $user;
                break;
            }
        }

        if (!$evaluateur) {
            $skipped++;
            continue;
        }

        // Vérifier si l'avis n'existe pas déjà
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM avis
            WHERE evaluateur_id = :eval AND evalue_id = :evalu AND covoiturage_id = :trip
        ");
        $stmt->execute([
            'eval' => $evaluateur['utilisateur_id'],
            'evalu' => $trip['conducteur_id'],
            'trip' => $trip['covoiturage_id']
        ]);

        if ($stmt->fetchColumn() > 0) {
            $skipped++;
            continue;
        }

        // Sélectionner un commentaire aléatoire
        $comment = $commentaires[array_rand($commentaires)];
        $note = $comment[0];
        $commentaire = $comment[1];

        // Date aléatoire dans les 30 derniers jours
        $daysAgo = rand(1, 30);
        $created_at = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));

        // Insérer l'avis
        try {
            $stmt = $pdo->prepare("
                INSERT INTO avis (evaluateur_id, evalue_id, covoiturage_id, note, commentaire, created_at)
                VALUES (:evaluateur, :evalue, :covoiturage, :note, :commentaire, :created_at)
            ");
            $stmt->execute([
                'evaluateur' => $evaluateur['utilisateur_id'],
                'evalue' => $trip['conducteur_id'],
                'covoiturage' => $trip['covoiturage_id'],
                'note' => $note,
                'commentaire' => $commentaire,
                'created_at' => $created_at
            ]);

            $inserted++;
            echo "✅ Avis #{$inserted}: {$evaluateur['pseudo']} → Conducteur (Note: {$note}/5)\n";
            echo "   Trajet: {$trip['ville_depart']} → {$trip['ville_arrivee']}\n";
            echo "   \"{$commentaire}\"\n\n";

        } catch (PDOException $e) {
            $skipped++;
            echo "⚠️ Avis ignoré: {$e->getMessage()}\n\n";
        }
    }

    // Créer aussi quelques avis de passagers vers conducteurs (inversés)
    foreach ($trips as $index => $trip) {
        if ($inserted >= 15) break;

        // Le conducteur évalue un passager
        $passager = $users[($index + 2) % count($users)]; // Sélectionner un autre utilisateur

        if ($passager['utilisateur_id'] == $trip['conducteur_id']) {
            continue;
        }

        // Vérifier si l'avis n'existe pas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM avis
            WHERE evaluateur_id = :eval AND evalue_id = :evalu AND covoiturage_id = :trip
        ");
        $stmt->execute([
            'eval' => $trip['conducteur_id'],
            'evalu' => $passager['utilisateur_id'],
            'trip' => $trip['covoiturage_id']
        ]);

        if ($stmt->fetchColumn() > 0) {
            continue;
        }

        $comment = $commentaires[array_rand($commentaires)];
        $note = $comment[0];
        $commentaire = str_replace('Conducteur', 'Passager', $comment[1]);

        $daysAgo = rand(1, 30);
        $created_at = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));

        try {
            $stmt = $pdo->prepare("
                INSERT INTO avis (evaluateur_id, evalue_id, covoiturage_id, note, commentaire, created_at)
                VALUES (:evaluateur, :evalue, :covoiturage, :note, :commentaire, :created_at)
            ");
            $stmt->execute([
                'evaluateur' => $trip['conducteur_id'],
                'evalue' => $passager['utilisateur_id'],
                'covoiturage' => $trip['covoiturage_id'],
                'note' => $note,
                'commentaire' => $commentaire,
                'created_at' => $created_at
            ]);

            $inserted++;
            echo "✅ Avis #{$inserted}: Conducteur → {$passager['pseudo']} (Note: {$note}/5)\n";
            echo "   Trajet: {$trip['ville_depart']} → {$trip['ville_arrivee']}\n";
            echo "   \"{$commentaire}\"\n\n";

        } catch (PDOException $e) {
            // Ignorer silencieusement
        }
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "<span class='success'>🎉 CRÉATION TERMINÉE</span>\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "✅ Avis créés: <span class='success'>$inserted</span>\n";
    echo "⚠️ Avis ignorés: $skipped\n\n";

    // Statistiques finales
    $stmt = $pdo->query("SELECT COUNT(*) FROM avis");
    $totalAvis = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT AVG(note) FROM avis");
    $avgNote = round($stmt->fetchColumn(), 1);

    echo "📊 STATISTIQUES GLOBALES:\n";
    echo "   Total d'avis: $totalAvis\n";
    echo "   Note moyenne: $avgNote / 5\n\n";

    echo "✅ Vous pouvez maintenant tester l'affichage des avis !\n";

} catch (Exception $e) {
    echo "<span class='error'>❌ ERREUR: " . $e->getMessage() . "</span>\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
}

echo "</pre>";

echo "<hr>";
echo "<h2>📍 Prochaines étapes</h2>";
echo "<p><strong>Les avis de démonstration sont prêts !</strong></p>";
echo "<p><a href='/user/dashboard.php?section=avis' style='display: inline-block; background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🎉 Voir les avis</a></p>";
echo "<p><a href='/'>← Retour accueil</a></p>";
?>
