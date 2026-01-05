<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour modifier un trajet'
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
} catch(Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]));
}

// Récupérer et valider les données
$user_id = $_SESSION['user_id'];
$trip_id = intval($_POST['trip_id'] ?? 0);
$ville_depart = trim($_POST['ville_depart'] ?? '');
$ville_arrivee = trim($_POST['ville_arrivee'] ?? '');
$date_depart = $_POST['date_depart'] ?? '';
$heure_depart = $_POST['heure_depart'] ?? '';
$date_arrivee = $_POST['date_arrivee'] ?? '';
$heure_arrivee = $_POST['heure_arrivee'] ?? '';
$voiture_id = intval($_POST['voiture_id'] ?? 0);
$places_disponibles = intval($_POST['places_disponibles'] ?? 0);
$prix_par_place = floatval($_POST['prix_par_place'] ?? 0);

// Validations
$errors = [];

if ($trip_id <= 0) {
    $errors[] = 'ID de trajet invalide';
}

if (empty($ville_depart)) {
    $errors[] = 'La ville de départ est obligatoire';
}

if (empty($ville_arrivee)) {
    $errors[] = 'La ville d\'arrivée est obligatoire';
}

if (strtolower($ville_depart) === strtolower($ville_arrivee)) {
    $errors[] = 'La ville de départ et d\'arrivée doivent être différentes';
}

if (empty($date_depart) || empty($heure_depart)) {
    $errors[] = 'La date et l\'heure de départ sont obligatoires';
}

if (empty($date_arrivee) || empty($heure_arrivee)) {
    $errors[] = 'La date et l\'heure d\'arrivée sont obligatoires';
}

// Vérifier que la date n'est pas dans le passé
$datetime_depart = $date_depart . ' ' . $heure_depart . ':00';
$datetime_arrivee = $date_arrivee . ' ' . $heure_arrivee . ':00';

if (strtotime($datetime_depart) <= time()) {
    $errors[] = 'La date de départ doit être dans le futur';
}

// Vérifier que l'arrivée est après le départ
if (strtotime($datetime_arrivee) <= strtotime($datetime_depart)) {
    $errors[] = 'L\'heure d\'arrivée doit être après l\'heure de départ';
}

if ($voiture_id <= 0) {
    $errors[] = 'Vous devez sélectionner un véhicule';
}

if ($places_disponibles <= 0 || $places_disponibles > 4) {
    $errors[] = 'Le nombre de places doit être entre 1 et 4';
}

if ($prix_par_place <= 0 || $prix_par_place > 100) {
    $errors[] = 'Le prix par place doit être entre 0.50 et 100 crédits';
}

// Si des erreurs existent, les retourner
if (!empty($errors)) {
    die(json_encode([
        'success' => false,
        'message' => implode('<br>', $errors)
    ]));
}

try {
    // Vérifier que le trajet appartient bien à l'utilisateur et peut être modifié
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(p.participation_id) as participants
            FROM covoiturage c
            LEFT JOIN participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_reservation = 'confirmee'
            WHERE c.covoiturage_id = :trip_id AND c.conducteur_id = :user_id
            GROUP BY c.covoiturage_id, c.conducteur_id, c.voiture_id, c.ville_depart, c.ville_arrivee, c.date_depart, c.date_arrivee, c.places_disponibles, c.prix, c.statut, c.created_at
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(p.participation_id) as participants
            FROM covoiturage c
            LEFT JOIN participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut = 'confirme'
            WHERE c.covoiturage_id = :trip_id AND c.conducteur_id = :user_id
            GROUP BY c.covoiturage_id
        ");
    }
    $stmt->execute([
        'trip_id' => $trip_id,
        'user_id' => $user_id
    ]);

    $trip = $stmt->fetch();
    if (!$trip) {
        die(json_encode([
            'success' => false,
            'message' => 'Trajet non trouvé ou non autorisé'
        ]));
    }

    // Vérifier que le trajet peut être modifié (statut "planifie")
    if ($isPostgreSQL) {
        if ($trip['statut'] !== 'planifie') {
            die(json_encode([
                'success' => false,
                'message' => 'Ce trajet ne peut plus être modifié car il a déjà commencé ou est terminé'
            ]));
        }
    } else {
        if ($trip['statut'] !== 'planifie') {
            die(json_encode([
                'success' => false,
                'message' => 'Ce trajet ne peut plus être modifié car il a déjà commencé ou est terminé'
            ]));
        }
    }

    // Si des passagers ont déjà réservé, vérifier que les places ne sont pas réduites
    if ($trip['participants'] > 0 && $places_disponibles < $trip['places_disponibles']) {
        die(json_encode([
            'success' => false,
            'message' => 'Vous ne pouvez pas réduire le nombre de places car ' . $trip['participants'] . ' passager(s) ont déjà réservé'
        ]));
    }

    // Vérifier que le véhicule appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT places FROM voiture WHERE voiture_id = :voiture_id AND utilisateur_id = :user_id");
    $stmt->execute([
        'voiture_id' => $voiture_id,
        'user_id' => $user_id
    ]);

    $vehicle = $stmt->fetch();
    if (!$vehicle) {
        die(json_encode([
            'success' => false,
            'message' => 'Véhicule invalide ou non autorisé'
        ]));
    }

    // Vérifier que les places demandées ne dépassent pas la capacité du véhicule
    if ($places_disponibles >= $vehicle['places']) {
        die(json_encode([
            'success' => false,
            'message' => 'Le nombre de places disponibles ne peut pas égaler ou dépasser la capacité totale du véhicule'
        ]));
    }

    // Commencer une transaction
    $pdo->beginTransaction();

    // Mettre à jour le trajet
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            UPDATE covoiturage SET
                voiture_id = :voiture_id,
                ville_depart = :ville_depart,
                ville_arrivee = :ville_arrivee,
                date_depart = :date_depart,
                date_arrivee = :date_arrivee,
                places_disponibles = :places_disponibles,
                prix = :prix_par_place
            WHERE covoiturage_id = :trip_id AND conducteur_id = :user_id
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE covoiturage SET
                voiture_id = :voiture_id,
                ville_depart = :ville_depart,
                ville_arrivee = :ville_arrivee,
                date_depart = :date_depart,
                date_arrivee = :date_arrivee,
                places_disponibles = :places_disponibles,
                prix_par_place = :prix_par_place
            WHERE covoiturage_id = :trip_id AND conducteur_id = :user_id
        ");
    }

    $result = $stmt->execute([
        'voiture_id' => $voiture_id,
        'ville_depart' => $ville_depart,
        'ville_arrivee' => $ville_arrivee,
        'date_depart' => $datetime_depart,
        'date_arrivee' => $datetime_arrivee,
        'places_disponibles' => $places_disponibles,
        'prix_par_place' => $prix_par_place,
        'trip_id' => $trip_id,
        'user_id' => $user_id
    ]);

    if (!$result) {
        throw new Exception('Erreur lors de la modification du trajet');
    }

    // Valider la transaction
    $pdo->commit();

    // Retourner le succès
    echo json_encode([
        'success' => true,
        'message' => 'Trajet modifié avec succès ! Les modifications sont maintenant visibles.',
        'trip_id' => $trip_id
    ]);

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log l'erreur pour debug
    error_log('Erreur modification trajet: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la modification du trajet: ' . $e->getMessage()
    ]);
}
?>