<?php
/**
 * init-trajets-demo.php
 * Script d'initialisation des trajets de démonstration
 * À exécuter par le correcteur pour avoir des trajets valides à tester
 */

require_once 'config/database.php';

try {
    echo "<h2>🚗 EcoRide - Initialisation des trajets de démonstration</h2>\n";

    // Connexion à la base avec la fonction helper
    $db = db();

    // Vérifier quels utilisateurs existent
    $users = $db->query("SELECT utilisateur_id, pseudo, role FROM utilisateur WHERE role = 'utilisateur' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if (count($users) < 3) {
        die("<p style='color: red;'>❌ Erreur : Il faut au moins 3 utilisateurs dans la base. Exécutez d'abord le script seed.sql</p>");
    }

    // Nettoyer les anciens trajets de test
    echo "<p>🧹 Nettoyage des anciens trajets...</p>\n";
    $db->exec("DELETE FROM participation WHERE covoiturage_id BETWEEN 1 AND 10");
    $db->exec("DELETE FROM covoiturage WHERE covoiturage_id BETWEEN 1 AND 10");

    // Créer des trajets avec dates relatives (toujours valides)
    echo "<p>🚀 Création de nouveaux trajets...</p>\n";

    // Utiliser les vrais IDs d'utilisateurs de la base
    $user_ids = array_column($users, 'utilisateur_id');

    $trajets = [
        [
            'id' => 1,
            'conducteur' => $user_ids[0] ?? 1, 'voiture' => 1,
            'depart' => 'Paris', 'arrivee' => 'Lyon',
            'jour' => 1, 'heure' => '09:00', 'duree' => 150, // +1 jour, 9h00, 2h30
            'places' => 3, 'prix' => 25.00
        ],
        [
            'id' => 2,
            'conducteur' => $user_ids[1] ?? 1, 'voiture' => 1,
            'depart' => 'Lyon', 'arrivee' => 'Marseille',
            'jour' => 1, 'heure' => '14:00', 'duree' => 195, // +1 jour, 14h00, 3h15
            'places' => 2, 'prix' => 20.00
        ],
        [
            'id' => 3,
            'conducteur' => $user_ids[2] ?? 1, 'voiture' => 1,
            'depart' => 'Bordeaux', 'arrivee' => 'Toulouse',
            'jour' => 2, 'heure' => '10:00', 'duree' => 150, // +2 jours, 10h00, 2h30
            'places' => 3, 'prix' => 15.00
        ],
        [
            'id' => 4,
            'conducteur' => $user_ids[0] ?? 1, 'voiture' => 1,
            'depart' => 'Paris', 'arrivee' => 'Orleans',
            'jour' => 3, 'heure' => '16:00', 'duree' => 90, // +3 jours, 16h00, 1h30
            'places' => 3, 'prix' => 10.00
        ],
        [
            'id' => 5,
            'conducteur' => $user_ids[1] ?? 1, 'voiture' => 1,
            'depart' => 'Lyon', 'arrivee' => 'Grenoble',
            'jour' => 4, 'heure' => '15:00', 'duree' => 75, // +4 jours, 15h00, 1h15
            'places' => 2, 'prix' => 8.00
        ]
    ];

    $sql = "INSERT INTO covoiturage
            (covoiturage_id, conducteur_id, voiture_id, ville_depart, adresse_depart,
             ville_arrivee, adresse_arrivee, date_depart, date_arrivee,
             places_disponibles, prix_par_place, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?,
                   DATE_ADD(CURDATE(), INTERVAL ? DAY) + INTERVAL ? HOUR + INTERVAL ? MINUTE,
                   DATE_ADD(CURDATE(), INTERVAL ? DAY) + INTERVAL ? HOUR + INTERVAL ? MINUTE,
                   ?, ?, 'planifie')";

    $stmt = $db->prepare($sql);

    foreach ($trajets as $t) {
        // Calculer heure de départ et arrivée
        list($h_dep, $m_dep) = explode(':', $t['heure']);
        $duree_h = intval($t['duree'] / 60);
        $duree_m = $t['duree'] % 60;

        // Heure d'arrivée
        $total_minutes = (intval($h_dep) * 60) + intval($m_dep) + $t['duree'];
        $h_arr = intval($total_minutes / 60);
        $m_arr = $total_minutes % 60;

        $adresse_depart = $t['depart'] === 'Paris' ? 'Gare du Nord' :
                         ($t['depart'] === 'Lyon' ? 'Bellecour' :
                         ($t['depart'] === 'Bordeaux' ? 'Place Gambetta' : 'Centre-ville'));

        $adresse_arrivee = $t['arrivee'] === 'Lyon' ? 'Gare Part-Dieu' :
                          ($t['arrivee'] === 'Marseille' ? 'Vieux-Port' :
                          ($t['arrivee'] === 'Toulouse' ? 'Capitole' : 'Centre-ville'));

        $stmt->execute([
            $t['id'], $t['conducteur'], $t['voiture'],
            $t['depart'], $adresse_depart, $t['arrivee'], $adresse_arrivee,
            // Date départ
            $t['jour'], intval($h_dep), intval($m_dep),
            // Date arrivée
            $t['jour'], $h_arr, $m_arr,
            $t['places'], $t['prix']
        ]);

        echo "<p>✅ Trajet {$t['depart']} → {$t['arrivee']} créé (dans {$t['jour']} jour(s) à {$t['heure']})</p>\n";
    }

    // Ajouter quelques réservations
    echo "<p>🎫 Ajout de réservations de test...</p>\n";
    $reservations = [
        [1, 7, 1, 25], // Trajet 1, passager 7, 1 place, 25 crédits
        [2, 8, 1, 20], // Trajet 2, passager 8, 1 place, 20 crédits
    ];

    $sqlRes = "INSERT INTO participation (covoiturage_id, passager_id, nombre_places, credit_utilise, statut) VALUES (?, ?, ?, ?, 'reserve')";
    $stmtRes = $db->prepare($sqlRes);

    foreach ($reservations as $r) {
        $stmtRes->execute($r);
        echo "<p>✅ Réservation ajoutée pour le trajet {$r[0]}</p>\n";
    }

    echo "<h3>🎉 Initialisation terminée !</h3>\n";
    echo "<p><strong>Trajets disponibles pour les tests :</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Paris → Lyon (demain 9h00)</li>\n";
    echo "<li>Lyon → Marseille (demain 14h00)</li>\n";
    echo "<li>Bordeaux → Toulouse (dans 2 jours 10h00)</li>\n";
    echo "<li>Paris → Orleans (dans 3 jours 16h00)</li>\n";
    echo "<li>Lyon → Grenoble (dans 4 jours 15h00)</li>\n";
    echo "</ul>\n";
    echo "<p><a href='index.php'>🏠 Retour à l'accueil</a> | <a href='trajets.php'>🔍 Rechercher des trajets</a></p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>