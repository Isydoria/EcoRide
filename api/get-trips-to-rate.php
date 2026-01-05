<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/init.php';

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');
} catch(PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]));
}

// Récupérer les paramètres
$user_id = intval($_GET['user_id'] ?? 0);

// Validation
if ($user_id <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'ID utilisateur invalide'
    ]));
}

try {
    $trips_to_rate = [];

    if ($isPostgreSQL) {
        // Récupérer les trajets terminés où l'utilisateur était conducteur
        // et doit évaluer les passagers
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                c.covoiturage_id,
                c.ville_depart,
                c.ville_arrivee,
                c.date_depart,
                c.prix as prix,
                p.passager_id as other_user_id,
                u.pseudo as other_user_pseudo,
                TRUE as is_conductor
            FROM covoiturage c
            JOIN participation p ON c.covoiturage_id = p.covoiturage_id
            JOIN utilisateur u ON p.passager_id = u.utilisateur_id
            WHERE c.conducteur_id = :user_id
            AND c.statut = 'termine'
            AND p.statut = 'terminee'
            AND NOT EXISTS (
                SELECT 1 FROM avis a
                WHERE a.auteur_id = :user_id
                AND a.destinataire_id = p.passager_id
                AND a.covoiturage_id = c.covoiturage_id
            )
            ORDER BY c.date_depart DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        $conductor_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les trajets terminés où l'utilisateur était passager
        // et doit évaluer le conducteur
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                c.covoiturage_id,
                c.ville_depart,
                c.ville_arrivee,
                c.date_depart,
                c.prix as prix,
                c.conducteur_id as other_user_id,
                u.pseudo as other_user_pseudo,
                FALSE as is_conductor
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            WHERE p.passager_id = :user_id
            AND c.statut = 'termine'
            AND p.statut = 'terminee'
            AND NOT EXISTS (
                SELECT 1 FROM avis a
                WHERE a.auteur_id = :user_id
                AND a.destinataire_id = c.conducteur_id
                AND a.covoiturage_id = c.covoiturage_id
            )
            ORDER BY c.date_depart DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        $passenger_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Combiner les deux listes
        $trips_to_rate = array_merge($conductor_trips, $passenger_trips);

    } else {
        // MySQL: Récupérer les trajets terminés où l'utilisateur était conducteur
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                c.covoiturage_id,
                c.ville_depart,
                c.ville_arrivee,
                c.date_depart,
                c.prix_par_place as prix,
                p.passager_id as other_user_id,
                u.pseudo as other_user_pseudo,
                1 as is_conductor
            FROM covoiturage c
            JOIN participation p ON c.covoiturage_id = p.covoiturage_id
            JOIN utilisateur u ON p.passager_id = u.utilisateur_id
            WHERE c.conducteur_id = :user_id
            AND c.statut = 'termine'
            AND p.statut = 'terminee'
            AND NOT EXISTS (
                SELECT 1 FROM avis a
                WHERE a.auteur_id = :user_id
                AND a.destinataire_id = p.passager_id
                AND a.covoiturage_id = c.covoiturage_id
                AND a.statut = 'publie'
            )
            ORDER BY c.date_depart DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        $conductor_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // MySQL: Récupérer les trajets terminés où l'utilisateur était passager
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                c.covoiturage_id,
                c.ville_depart,
                c.ville_arrivee,
                c.date_depart,
                c.prix_par_place as prix,
                c.conducteur_id as other_user_id,
                u.pseudo as other_user_pseudo,
                0 as is_conductor
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            WHERE p.passager_id = :user_id
            AND c.statut = 'termine'
            AND p.statut = 'terminee'
            AND NOT EXISTS (
                SELECT 1 FROM avis a
                WHERE a.auteur_id = :user_id
                AND a.destinataire_id = c.conducteur_id
                AND a.covoiturage_id = c.covoiturage_id
                AND a.statut = 'publie'
            )
            ORDER BY c.date_depart DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        $passenger_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Combiner les deux listes
        $trips_to_rate = array_merge($conductor_trips, $passenger_trips);
    }

    // Formater les résultats
    $formatted_trips = [];
    foreach ($trips_to_rate as $trip) {
        $formatted_trips[] = [
            'covoiturage_id' => intval($trip['covoiturage_id']),
            'ville_depart' => $trip['ville_depart'],
            'ville_arrivee' => $trip['ville_arrivee'],
            'date_depart' => $trip['date_depart'],
            'prix' => floatval($trip['prix']),
            'other_user_id' => intval($trip['other_user_id']),
            'other_user_pseudo' => $trip['other_user_pseudo'],
            'is_conductor' => (bool)$trip['is_conductor']
        ];
    }

    echo json_encode([
        'success' => true,
        'trips' => $formatted_trips,
        'count' => count($formatted_trips)
    ]);

} catch (Exception $e) {
    error_log('Erreur récupération trajets à évaluer: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des trajets à évaluer'
    ]);
}
?>
