<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour annuler un trajet'
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

    // Vérifier que le trajet appartient bien à l'utilisateur et qu'il peut être annulé
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(p.participation_id) as participants_count,
                   COALESCE(SUM(CASE WHEN p.statut != 'annulee' THEN (c.prix * p.places_reservees) ELSE 0 END), 0) as credits_to_refund
            FROM covoiturage c
            LEFT JOIN participation p ON c.covoiturage_id = p.covoiturage_id
            WHERE c.covoiturage_id = :trip_id AND c.conducteur_id = :user_id
            GROUP BY c.covoiturage_id
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(p.participation_id) as participants_count,
                   COALESCE(SUM(CASE WHEN p.statut != 'annule' THEN p.credit_utilise ELSE 0 END), 0) as credits_to_refund
            FROM covoiturage c
            LEFT JOIN participation p ON c.covoiturage_id = p.covoiturage_id
            WHERE c.covoiturage_id = :trip_id AND c.conducteur_id = :user_id
            GROUP BY c.covoiturage_id
        ");
    }
    $stmt->execute([
        'trip_id' => $trip_id,
        'user_id' => $user_id
    ]);

    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception('Trajet non trouvé ou non autorisé');
    }

    // Vérifier si le trajet peut être annulé (pas encore commencé)
    if (strtotime($trip['date_depart']) <= time()) {
        throw new Exception('Impossible d\'annuler un trajet déjà commencé ou passé');
    }

    // Vérifier le statut du trajet
    if ($trip['statut'] === 'annule') {
        throw new Exception('Ce trajet est déjà annulé');
    }

    if (in_array($trip['statut'], ['en_cours', 'termine'])) {
        throw new Exception('Impossible d\'annuler un trajet en cours ou terminé');
    }

    // Récupérer les participations actives pour remboursement
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT p.*, (c.prix * p.places_reservees) as credit_utilise, u.pseudo, u.credits as credit,
                   p.passager_id as id_passager
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u ON p.passager_id = u.utilisateur_id
            WHERE p.covoiturage_id = :trip_id AND p.statut IN ('reserve', 'confirme')
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT p.*, u.pseudo, u.credit
            FROM participation p
            JOIN utilisateur u ON p.passager_id = u.utilisateur_id
            WHERE p.covoiturage_id = :trip_id AND p.statut IN ('reserve', 'confirme')
        ");
    }
    $stmt->execute(['trip_id' => $trip_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rembourser tous les participants
    $total_refunded = 0;
    foreach ($participants as $participant) {
        // Rembourser les crédits
        $new_credit = $participant['credit'] + $participant['credit_utilise'];

        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("UPDATE utilisateur SET credits = :new_credit WHERE utilisateur_id = :user_id");
            $stmt->execute([
                'new_credit' => $new_credit,
                'user_id' => $participant['id_passager']
            ]);

            // Marquer la participation comme annulée
            $stmt = $pdo->prepare("UPDATE participation SET statut = 'annulee' WHERE participation_id = :participation_id");
            $stmt->execute(['participation_id' => $participant['participation_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateur SET credit = :new_credit WHERE utilisateur_id = :user_id");
            $stmt->execute([
                'new_credit' => $new_credit,
                'user_id' => $participant['passager_id']
            ]);

            // Marquer la participation comme annulée
            $stmt = $pdo->prepare("UPDATE participation SET statut = 'annule' WHERE participation_id = :participation_id");
            $stmt->execute(['participation_id' => $participant['participation_id']]);
        }

        $total_refunded += $participant['credit_utilise'];
    }

    // Marquer le trajet comme annulé
    $stmt = $pdo->prepare("UPDATE covoiturage SET statut = 'annule' WHERE covoiturage_id = :trip_id");
    $stmt->execute(['trip_id' => $trip_id]);

    // Valider la transaction
    $pdo->commit();

    // Construire le message de succès
    $message = "Trajet annulé avec succès !";
    if (count($participants) > 0) {
        $message .= " " . count($participants) . " participant(s) ont été remboursés ($total_refunded crédits au total).";
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'participants_refunded' => count($participants),
        'credits_refunded' => $total_refunded
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log l'erreur pour debug
    error_log('Erreur annulation trajet: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>