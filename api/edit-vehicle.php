<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour modifier un véhicule'
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
$vehicle_id = intval($_POST['vehicle_id'] ?? 0);
$marque = trim($_POST['marque'] ?? '');
$modele = trim($_POST['modele'] ?? '');
$immatriculation = strtoupper(trim($_POST['immatriculation'] ?? ''));
$couleur = trim($_POST['couleur'] ?? '');
$places = intval($_POST['places'] ?? 0);
$energie = trim($_POST['energie'] ?? '');

// Validations
$errors = [];

if ($vehicle_id <= 0) {
    $errors[] = 'ID véhicule invalide';
}

if (empty($marque)) {
    $errors[] = 'La marque est obligatoire';
}

if (empty($modele)) {
    $errors[] = 'Le modèle est obligatoire';
}

if (empty($immatriculation)) {
    $errors[] = 'L\'immatriculation est obligatoire';
} elseif (!preg_match('/^[A-Z0-9-]{7,10}$/', $immatriculation)) {
    $errors[] = 'Format d\'immatriculation invalide (ex: AB-123-CD)';
}

if ($places <= 1 || $places > 9) {
    $errors[] = 'Le nombre de places doit être entre 2 et 9';
}

if (empty($energie)) {
    $errors[] = 'Le type d\'énergie est obligatoire';
} elseif (!in_array($energie, ['electrique', 'hybride', 'essence', 'diesel'])) {
    $errors[] = 'Type d\'énergie invalide';
}

// Si des erreurs existent, les retourner
if (!empty($errors)) {
    die(json_encode([
        'success' => false,
        'message' => implode('<br>', $errors)
    ]));
}

try {
    // Vérifier que le véhicule appartient bien à l'utilisateur
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("SELECT voiture_id FROM voiture WHERE voiture_id = :vehicle_id AND utilisateur_id = :user_id");
    } else {
        $stmt = $pdo->prepare("SELECT voiture_id FROM voiture WHERE voiture_id = :vehicle_id AND utilisateur_id = :user_id");
    }
    $stmt->execute([
        'vehicle_id' => $vehicle_id,
        'user_id' => $user_id
    ]);

    if (!$stmt->fetch()) {
        die(json_encode([
            'success' => false,
            'message' => 'Véhicule non trouvé ou non autorisé'
        ]));
    }

    // Vérifier si l'immatriculation existe déjà (sauf pour ce véhicule)
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("SELECT voiture_id FROM voiture WHERE immatriculation = :immatriculation AND voiture_id != :vehicle_id");
    } else {
        $stmt = $pdo->prepare("SELECT voiture_id FROM voiture WHERE immatriculation = :immatriculation AND voiture_id != :vehicle_id");
    }
    $stmt->execute([
        'immatriculation' => $immatriculation,
        'vehicle_id' => $vehicle_id
    ]);

    if ($stmt->fetch()) {
        die(json_encode([
            'success' => false,
            'message' => 'Un autre véhicule avec cette immatriculation existe déjà'
        ]));
    }

    // Mettre à jour le véhicule
    $stmt = $pdo->prepare("
        UPDATE voiture SET
            marque = :marque,
            modele = :modele,
            immatriculation = :immatriculation,
            couleur = :couleur,
            places = :places,
            energie = :energie
        WHERE voiture_id = :vehicle_id AND utilisateur_id = :user_id
    ");

    $result = $stmt->execute([
        'marque' => $marque,
        'modele' => $modele,
        'immatriculation' => $immatriculation,
        'couleur' => $couleur,
        'places' => $places,
        'energie' => $energie,
        'vehicle_id' => $vehicle_id,
        'user_id' => $user_id
    ]);

    if (!$result) {
        throw new Exception('Erreur lors de la modification du véhicule');
    }

    // Retourner le succès
    echo json_encode([
        'success' => true,
        'message' => 'Véhicule modifié avec succès !',
        'vehicle' => [
            'vehicle_id' => $vehicle_id,
            'marque' => $marque,
            'modele' => $modele,
            'immatriculation' => $immatriculation,
            'couleur' => $couleur,
            'places' => $places,
            'energie' => $energie
        ]
    ]);

} catch (Exception $e) {
    // Log l'erreur pour debug
    error_log('Erreur modification véhicule: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la modification du véhicule. Veuillez réessayer.'
    ]);
}
?>