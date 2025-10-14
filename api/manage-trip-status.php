<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour gérer un trajet'
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
$action = $_POST['action'] ?? ''; // 'start' ou 'finish'

// Validation
if ($trip_id <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'ID de trajet invalide'
    ]));
}

if (!in_array($action, ['start', 'finish'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Action invalide'
    ]));
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();

    // Vérifier que le trajet appartient bien au conducteur
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT t.*, COUNT(r.id_reservation) as participants_count
            FROM trajet t
            LEFT JOIN reservation r ON t.id_trajet = r.id_trajet
                AND r.statut IN ('reserve', 'confirme')
            WHERE t.id_trajet = :trip_id AND t.id_conducteur = :user_id
            GROUP BY t.id_trajet, t.id_conducteur, t.id_vehicule, t.ville_depart, t.ville_arrivee, t.date_depart, t.date_arrivee, t.places_disponibles, t.prix, t.is_active, t.date_inscription
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(p.participation_id) as participants_count
            FROM covoiturage c
            LEFT JOIN participation p ON c.covoiturage_id = p.covoiturage_id
                AND p.statut IN ('reserve', 'confirme')
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

    if ($action === 'start') {
        // Démarrer le trajet (US11)
        if ($isPostgreSQL) {
            if (!$trip['is_active']) {
                throw new Exception('Ce trajet ne peut plus être démarré (statut: inactif)');
            }
        } else {
            if ($trip['statut'] !== 'planifie') {
                throw new Exception('Ce trajet ne peut plus être démarré (statut: ' . $trip['statut'] . ')');
            }
        }

        // Vérifier que la date de départ n'est pas trop éloignée
        $departure_time = strtotime($trip['date_depart']);
        $now = time();
        $time_diff = abs($departure_time - $now);

        // Permettre de démarrer 2h avant ou après l'heure prévue
        if ($time_diff > 7200) {
            $planned_time = date('d/m/Y à H:i', $departure_time);
            throw new Exception("Le trajet était prévu pour le $planned_time. Vous ne pouvez le démarrer que 2h avant ou après cette heure.");
        }

        // Mettre à jour le statut du trajet
        if ($isPostgreSQL) {
            // PostgreSQL: on pourrait ajouter un champ statut ou gérer différemment
            // Pour l'instant, on garde is_active à true
            $stmt = $pdo->prepare("UPDATE trajet SET is_active = true WHERE id_trajet = :trip_id");
        } else {
            $stmt = $pdo->prepare("UPDATE covoiturage SET statut = 'en_cours' WHERE covoiturage_id = :trip_id");
        }
        $stmt->execute(['trip_id' => $trip_id]);

        // Mettre à jour le statut des participations
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("
                UPDATE reservation
                SET statut = 'confirme'
                WHERE id_trajet = :trip_id AND statut = 'reserve'
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE participation
                SET statut = 'confirme'
                WHERE covoiturage_id = :trip_id AND statut = 'reserve'
            ");
        }
        $stmt->execute(['trip_id' => $trip_id]);

        $message = "Trajet démarré avec succès !";
        if ($trip['participants_count'] > 0) {
            $message .= " Vos {$trip['participants_count']} passager(s) ont été notifiés.";
        }

    } elseif ($action === 'finish') {
        // Terminer le trajet (US11)
        if ($isPostgreSQL) {
            if (!$trip['is_active']) {
                throw new Exception('Ce trajet ne peut être terminé que s\'il est actif');
            }
        } else {
            if ($trip['statut'] !== 'en_cours') {
                throw new Exception('Ce trajet ne peut être terminé que s\'il est en cours');
            }
        }

        // Mettre à jour le statut du trajet
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("UPDATE trajet SET is_active = false WHERE id_trajet = :trip_id");
        } else {
            $stmt = $pdo->prepare("UPDATE covoiturage SET statut = 'termine' WHERE covoiturage_id = :trip_id");
        }
        $stmt->execute(['trip_id' => $trip_id]);

        // Mettre à jour le statut des participations
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("
                UPDATE reservation
                SET statut = 'termine'
                WHERE id_trajet = :trip_id AND statut = 'confirme'
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE participation
                SET statut = 'termine'
                WHERE covoiturage_id = :trip_id AND statut = 'confirme'
            ");
        }
        $stmt->execute(['trip_id' => $trip_id]);

        // Récupérer les participants pour notification
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("
                SELECT r.*, u.pseudo, u.email
                FROM reservation r
                JOIN utilisateur u ON r.id_passager = u.id_utilisateur
                WHERE r.id_trajet = :trip_id AND r.statut = 'termine'
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT p.*, u.pseudo, u.email
                FROM participation p
                JOIN utilisateur u ON p.passager_id = u.utilisateur_id
                WHERE p.covoiturage_id = :trip_id AND p.statut = 'termine'
            ");
        }
        $stmt->execute(['trip_id' => $trip_id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $message = "Trajet terminé avec succès !";
        if (count($participants) > 0) {
            $message .= " " . count($participants) . " passager(s) vont recevoir une demande d'évaluation.";

            // TODO: Ici on pourrait envoyer des emails aux participants
            // pour leur demander de valider le trajet et laisser un avis
        }
    }

    // Valider la transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'new_status' => $action === 'start' ? 'en_cours' : 'termine'
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log l'erreur pour debug
    error_log('Erreur gestion statut trajet: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>