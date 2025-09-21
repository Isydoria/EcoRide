<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour ajouter un véhicule'
    ]));
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]));
}

// Configuration Railway
$host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? 'localhost';
$dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecoride_db';
$username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? 'root';
$password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]));
}

// Récupérer et valider les données
$user_id = $_SESSION['user_id'];
$marque = trim($_POST['marque'] ?? '');
$modele = trim($_POST['modele'] ?? '');
$immatriculation = strtoupper(trim($_POST['immatriculation'] ?? ''));
$couleur = trim($_POST['couleur'] ?? '');
$places = intval($_POST['places'] ?? 0);
$energie = trim($_POST['energie'] ?? '');

// Validations
$errors = [];

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
    // Vérifier si l'immatriculation existe déjà
    $stmt = $pdo->prepare("SELECT voiture_id FROM voiture WHERE immatriculation = :immatriculation");
    $stmt->execute(['immatriculation' => $immatriculation]);

    if ($stmt->fetch()) {
        die(json_encode([
            'success' => false,
            'message' => 'Un véhicule avec cette immatriculation existe déjà'
        ]));
    }

    // Insérer le nouveau véhicule
    $stmt = $pdo->prepare("
        INSERT INTO voiture (
            utilisateur_id, marque, modele, immatriculation,
            couleur, places, energie, created_at
        ) VALUES (
            :utilisateur_id, :marque, :modele, :immatriculation,
            :couleur, :places, :energie, NOW()
        )
    ");

    $result = $stmt->execute([
        'utilisateur_id' => $user_id,
        'marque' => $marque,
        'modele' => $modele,
        'immatriculation' => $immatriculation,
        'couleur' => $couleur,
        'places' => $places,
        'energie' => $energie
    ]);

    if (!$result) {
        throw new Exception('Erreur lors de l\'ajout du véhicule');
    }

    $vehicle_id = $pdo->lastInsertId();

    // Retourner le succès
    echo json_encode([
        'success' => true,
        'message' => 'Véhicule ajouté avec succès ! Vous pouvez maintenant l\'utiliser pour créer des trajets.',
        'vehicle_id' => $vehicle_id
    ]);

} catch (Exception $e) {
    // Log l'erreur pour debug
    error_log('Erreur ajout véhicule: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout du véhicule. Veuillez réessayer.'
    ]);
}
?>