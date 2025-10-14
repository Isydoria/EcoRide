<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour annuler une réservation'
    ]));
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]));
}

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

// Récupérer et valider les données
$user_id = $_SESSION['user_id'];
$trip_id = intval($_POST['trip_id'] ?? 0);

// Validation
if ($trip_id <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'ID de trajet invalide'
    ]));
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();

    // Vérifier que la participation existe et peut être annulée
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT r.*, t.date_depart, t.ville_depart, t.ville_arrivee, t.is_active as trip_status,
                   u.pseudo as conducteur, u.credits as user_credit
            FROM reservation r
            JOIN trajet t ON r.id_trajet = t.id_trajet
            JOIN utilisateur u ON r.id_passager = u.id_utilisateur
            WHERE r.id_trajet = :trip_id AND r.id_passager = :user_id
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT p.*, c.date_depart, c.ville_depart, c.ville_arrivee, c.statut as trip_status,
                   u.pseudo as conducteur, u.credit as user_credit
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u ON p.passager_id = u.utilisateur_id
            WHERE p.covoiturage_id = :trip_id AND p.passager_id = :user_id
        ");
    }
    $stmt->execute([
        'trip_id' => $trip_id,
        'user_id' => $user_id
    ]);

    $participation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participation) {
        throw new Exception('Réservation non trouvée');
    }

    // Vérifier si la réservation peut être annulée
    if ($participation['statut'] === 'annule') {
        throw new Exception('Cette réservation est déjà annulée');
    }

    if ($participation['statut'] === 'termine') {
        throw new Exception('Impossible d\'annuler une réservation terminée');
    }

    // Vérifier si le trajet n'est pas déjà commencé
    if (strtotime($participation['date_depart']) <= time()) {
        throw new Exception('Impossible d\'annuler une réservation pour un trajet déjà commencé ou passé');
    }

    // Vérifier si le trajet n'est pas annulé
    if ($isPostgreSQL) {
        if (!$participation['trip_status']) {
            throw new Exception('Ce trajet a été annulé par le conducteur');
        }
    } else {
        if ($participation['trip_status'] === 'annule') {
            throw new Exception('Ce trajet a été annulé par le conducteur');
        }
    }

    // Rembourser les crédits à l'utilisateur
    $new_credit = $participation['user_credit'] + $participation['credit_utilise'];

    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("UPDATE utilisateur SET credits = :new_credit WHERE id_utilisateur = :user_id");
        $stmt->execute([
            'new_credit' => $new_credit,
            'user_id' => $user_id
        ]);

        // Marquer la participation comme annulée
        $stmt = $pdo->prepare("UPDATE reservation SET statut = 'annule' WHERE id_reservation = :participation_id");
        $stmt->execute(['participation_id' => $participation['id_reservation']]);
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateur SET credit = :new_credit WHERE utilisateur_id = :user_id");
        $stmt->execute([
            'new_credit' => $new_credit,
            'user_id' => $user_id
        ]);

        // Marquer la participation comme annulée
        $stmt = $pdo->prepare("UPDATE participation SET statut = 'annule' WHERE participation_id = :participation_id");
        $stmt->execute(['participation_id' => $participation['participation_id']]);
    }

    // Augmenter le nombre de places disponibles dans le covoiturage
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            UPDATE trajet
            SET places_disponibles = places_disponibles + :places_liberated
            WHERE id_trajet = :trip_id
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE covoiturage
            SET places_disponibles = places_disponibles + :places_liberated
            WHERE covoiturage_id = :trip_id
        ");
    }
    $stmt->execute([
        'places_liberated' => $participation['nombre_places'],
        'trip_id' => $trip_id
    ]);

    // Créer une transaction de crédit pour la traçabilité
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            INSERT INTO transaction_credit (id_utilisateur, montant, type, description, reference_id, reference_type, date_inscription)
            VALUES (:user_id, :montant, 'credit', :description, :ref_id, 'remboursement', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'montant' => $participation['credit_utilise'],
            'description' => 'Remboursement annulation trajet ' . $participation['ville_depart'] . ' → ' . $participation['ville_arrivee'],
            'ref_id' => $participation['id_reservation']
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO transaction_credit (utilisateur_id, montant, type, description, reference_id, reference_type, created_at)
            VALUES (:user_id, :montant, 'credit', :description, :ref_id, 'remboursement', NOW())
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'montant' => $participation['credit_utilise'],
            'description' => 'Remboursement annulation trajet ' . $participation['ville_depart'] . ' → ' . $participation['ville_arrivee'],
            'ref_id' => $participation['participation_id']
        ]);
    }

    // Valider la transaction
    $pdo->commit();

    // Construire le message de succès
    $route = $participation['ville_depart'] . ' → ' . $participation['ville_arrivee'];
    $date = date('d/m/Y à H:i', strtotime($participation['date_depart']));

    $message = "Réservation annulée avec succès ! ";
    $message .= $participation['credit_utilise'] . " crédits ont été remboursés sur votre compte.";

    echo json_encode([
        'success' => true,
        'message' => $message,
        'credits_refunded' => $participation['credit_utilise'],
        'places_liberated' => $participation['nombre_places'],
        'new_credit_balance' => $new_credit,
        'trip_info' => [
            'route' => $route,
            'date' => $date
        ]
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log l'erreur pour debug
    error_log('Erreur annulation réservation: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>