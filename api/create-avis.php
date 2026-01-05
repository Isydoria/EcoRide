<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour laisser un avis'
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
$evaluateur_id = $_SESSION['user_id']; // Celui qui donne l'avis
$evalue_id = intval($_POST['evalue_id'] ?? 0); // Celui qui reçoit l'avis (conducteur ou passager)
$covoiturage_id = intval($_POST['covoiturage_id'] ?? 0);
$note = intval($_POST['note'] ?? 0);
$commentaire = trim($_POST['commentaire'] ?? '');

// Validations
$errors = [];

if ($evalue_id <= 0) {
    $errors[] = 'Utilisateur à évaluer invalide';
}

if ($covoiturage_id <= 0) {
    $errors[] = 'Trajet invalide';
}

if ($note < 1 || $note > 5) {
    $errors[] = 'La note doit être entre 1 et 5 étoiles';
}

if (empty($commentaire)) {
    $errors[] = 'Le commentaire est obligatoire';
} elseif (strlen($commentaire) < 10) {
    $errors[] = 'Le commentaire doit contenir au moins 10 caractères';
} elseif (strlen($commentaire) > 500) {
    $errors[] = 'Le commentaire ne peut pas dépasser 500 caractères';
}

if ($evaluateur_id === $evalue_id) {
    $errors[] = 'Vous ne pouvez pas vous évaluer vous-même';
}

// Si des erreurs existent, les retourner
if (!empty($errors)) {
    die(json_encode([
        'success' => false,
        'message' => implode('<br>', $errors)
    ]));
}

try {
    // Vérifier que l'utilisateur a bien participé à ce trajet - Unifié après migration
    $statutField = $isPostgreSQL ? 'statut_reservation' : 'statut';

    $stmt = $pdo->prepare("
        SELECT p.*, c.conducteur_id, c.statut as trip_statut
        FROM participation p
        JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
        WHERE p.covoiturage_id = :trip_id
        AND (p.passager_id = :user_id OR c.conducteur_id = :user_id)
        AND p.{$statutField} = 'terminee'
    ");
    $stmt->execute([
        'trip_id' => $covoiturage_id,
        'user_id' => $evaluateur_id
    ]);

    $participation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participation) {
        die(json_encode([
            'success' => false,
            'message' => 'Vous devez avoir participé à ce trajet terminé pour laisser un avis'
        ]));
    }

    // Vérifier que le trajet est bien terminé
    if ($participation['trip_statut'] !== 'termine') {
        die(json_encode([
            'success' => false,
            'message' => 'Vous ne pouvez laisser un avis que pour un trajet terminé'
        ]));
    }

    // Déterminer qui évalue qui
    $conducteur_id = $participation['conducteur_id'];
    $passager_id = $participation['passager_id'];

    // Vérifier que l'évaluateur veut bien évaluer quelqu'un du trajet
    if ($evaluateur_id === $conducteur_id) {
        // Le conducteur évalue un passager
        if ($evalue_id !== $passager_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Vous ne pouvez évaluer que les passagers de votre trajet'
            ]));
        }
    } elseif ($evaluateur_id === $passager_id) {
        // Le passager évalue le conducteur
        if ($evalue_id !== $conducteur_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Vous ne pouvez évaluer que le conducteur de ce trajet'
            ]));
        }
    } else {
        die(json_encode([
            'success' => false,
            'message' => 'Vous n\'avez pas participé à ce trajet'
        ]));
    }

    // Vérifier qu'un avis n'a pas déjà été laissé - Unifié après migration
    $stmt = $pdo->prepare("
        SELECT avis_id FROM avis
        WHERE auteur_id = :evaluateur_id
        AND destinataire_id = :evalue_id
        AND covoiturage_id = :trip_id
    ");
    $stmt->execute([
        'evaluateur_id' => $evaluateur_id,
        'evalue_id' => $evalue_id,
        'trip_id' => $covoiturage_id
    ]);

    if ($stmt->fetch()) {
        die(json_encode([
            'success' => false,
            'message' => 'Vous avez déjà laissé un avis pour cette personne sur ce trajet'
        ]));
    }

    // Insérer l'avis - Unifié après migration
    $dateField = $isPostgreSQL ? 'created_at' : 'date_creation';
    $statutDefault = $isPostgreSQL ? 'en_attente' : 'publie';

    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            INSERT INTO avis (auteur_id, destinataire_id, covoiturage_id, note, commentaire, {$dateField}, statut)
            VALUES (:evaluateur_id, :evalue_id, :trip_id, :note, :commentaire, CURRENT_TIMESTAMP, :statut)
            RETURNING avis_id
        ");

        $stmt->execute([
            'evaluateur_id' => $evaluateur_id,
            'evalue_id' => $evalue_id,
            'trip_id' => $covoiturage_id,
            'note' => $note,
            'commentaire' => $commentaire,
            'statut' => $statutDefault
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $avis_id = $result['avis_id'];
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO avis (auteur_id, destinataire_id, covoiturage_id, note, commentaire, {$dateField}, statut)
            VALUES (:evaluateur_id, :evalue_id, :trip_id, :note, :commentaire, NOW(), :statut)
        ");

        $stmt->execute([
            'evaluateur_id' => $evaluateur_id,
            'evalue_id' => $evalue_id,
            'trip_id' => $covoiturage_id,
            'note' => $note,
            'commentaire' => $commentaire,
            'statut' => $statutDefault
        ]);

        $avis_id = $pdo->lastInsertId();
    }

    // Retourner le succès
    echo json_encode([
        'success' => true,
        'message' => 'Merci pour votre avis ! Il a été publié avec succès.',
        'avis_id' => $avis_id
    ]);

} catch (Exception $e) {
    // Log l'erreur pour debug
    error_log('Erreur création avis: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création de l\'avis. Veuillez réessayer.'
    ]);
}
?>
