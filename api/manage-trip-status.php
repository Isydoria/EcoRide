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

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Token CSRF invalide. Veuillez recharger la page.'
    ]));
}

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
            SELECT c.*, COUNT(p.participation_id) as participants_count
            FROM covoiturage c
            LEFT JOIN participation p ON c.covoiturage_id = p.covoiturage_id
                AND p.statut IN ('reserve', 'confirme')
            WHERE c.covoiturage_id = :trip_id AND c.conducteur_id = :user_id
            GROUP BY c.covoiturage_id
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
        if ($trip['statut'] !== 'planifie') {
            throw new Exception('Ce trajet ne peut plus être démarré (statut: ' . $trip['statut'] . ')');
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
        $stmt = $pdo->prepare("UPDATE covoiturage SET statut = 'en_cours' WHERE covoiturage_id = :trip_id");
        $stmt->execute(['trip_id' => $trip_id]);

        // Mettre à jour le statut des participations
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("
                UPDATE participation
                SET statut = 'confirme'
                WHERE covoiturage_id = :trip_id AND statut = 'en_attente'
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE participation
                SET statut = 'confirme'
                WHERE covoiturage_id = :trip_id AND statut = 'en_attente'
            ");
        }
        $stmt->execute(['trip_id' => $trip_id]);

        $message = "Trajet démarré avec succès !";
        if ($trip['participants_count'] > 0) {
            $message .= " Vos {$trip['participants_count']} passager(s) ont été notifiés.";
        }

    } elseif ($action === 'finish') {
        // Terminer le trajet (US11)
        if ($trip['statut'] !== 'en_cours') {
            throw new Exception('Ce trajet ne peut être terminé que s\'il est en cours');
        }

        // Mettre à jour le statut du trajet
        $stmt = $pdo->prepare("UPDATE covoiturage SET statut = 'termine' WHERE covoiturage_id = :trip_id");
        $stmt->execute(['trip_id' => $trip_id]);

        // Mettre à jour le statut des participations
        $stmt = $pdo->prepare("
            UPDATE participation
            SET statut = 'termine'
            WHERE covoiturage_id = :trip_id AND statut = 'confirme'
        ");
        $stmt->execute(['trip_id' => $trip_id]);

        // Récupérer les participants pour notification et calcul des crédits
        $stmt = $pdo->prepare("
            SELECT p.*, u.pseudo, u.email, c.prix_par_place as prix
            FROM participation p
            JOIN utilisateur u ON p.passager_id = u.utilisateur_id
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            WHERE p.covoiturage_id = :trip_id AND p.statut = 'termine'
        ");
        $stmt->execute(['trip_id' => $trip_id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculer le total des crédits à transférer au conducteur
        $total_credits = 0;
        foreach ($participants as $participant) {
            $places = $participant['nombre_places'];
            $prix_unitaire = floatval($participant['prix']);
            $total_credits += ($prix_unitaire * $places);
        }
        // Arrondir à 2 décimales
        $total_credits = round($total_credits, 2);

        // Transférer les crédits au conducteur
        if ($total_credits > 0) {
            $stmt = $pdo->prepare("
                UPDATE utilisateur
                SET credit = credit + :amount
                WHERE utilisateur_id = :user_id
            ");
            $stmt->execute([
                'amount' => $total_credits,
                'user_id' => $user_id
            ]);

            // Mettre à jour les crédits en session
            if (isset($_SESSION['credits'])) {
                $_SESSION['credits'] = floatval($_SESSION['credits']) + $total_credits;
            }
        }

        $message = "Trajet terminé avec succès !";
        if (count($participants) > 0) {
            $message .= " " . count($participants) . " passager(s) vont recevoir une demande d'évaluation.";
            if ($total_credits > 0) {
                $message .= " Vous avez reçu " . number_format($total_credits, 2) . " crédits.";
            }

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