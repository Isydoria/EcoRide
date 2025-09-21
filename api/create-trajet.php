<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour créer un trajet'
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
$ville_depart = trim($_POST['ville_depart'] ?? '');
$ville_arrivee = trim($_POST['ville_arrivee'] ?? '');
$date_depart = $_POST['date_depart'] ?? '';
$heure_depart = $_POST['heure_depart'] ?? '';
$voiture_id = intval($_POST['voiture_id'] ?? 0);
$places_disponibles = intval($_POST['places_disponibles'] ?? 0);
$prix_par_place = floatval($_POST['prix_par_place'] ?? 0);
$commentaire = trim($_POST['commentaire'] ?? '');

// Validations
$errors = [];

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

// Vérifier que la date n'est pas dans le passé
$datetime_depart = $date_depart . ' ' . $heure_depart . ':00';
if (strtotime($datetime_depart) <= time()) {
    $errors[] = 'La date de départ doit être dans le futur';
}

if ($voiture_id <= 0) {
    $errors[] = 'Vous devez sélectionner un véhicule';
}

if ($places_disponibles <= 0 || $places_disponibles > 4) {
    $errors[] = 'Le nombre de places doit être entre 1 et 4';
}

if ($prix_par_place <= 0 || $prix_par_place > 100) {
    $errors[] = 'Le prix par place doit être entre 0.50€ et 100€';
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

    // Calculer l'heure d'arrivée estimée (ajouter 2 heures par défaut)
    $datetime_arrivee = date('Y-m-d H:i:s', strtotime($datetime_depart . ' +2 hours'));

    // Commencer une transaction
    $pdo->beginTransaction();

    // Insérer le nouveau trajet
    $stmt = $pdo->prepare("
        INSERT INTO covoiturage (
            conducteur_id, voiture_id, ville_depart, ville_arrivee,
            date_depart, date_arrivee, places_disponibles, prix_par_place,
            statut, created_at
        ) VALUES (
            :conducteur_id, :voiture_id, :ville_depart, :ville_arrivee,
            :date_depart, :date_arrivee, :places_disponibles, :prix_par_place,
            'planifie', NOW()
        )
    ");

    $result = $stmt->execute([
        'conducteur_id' => $user_id,
        'voiture_id' => $voiture_id,
        'ville_depart' => $ville_depart,
        'ville_arrivee' => $ville_arrivee,
        'date_depart' => $datetime_depart,
        'date_arrivee' => $datetime_arrivee,
        'places_disponibles' => $places_disponibles,
        'prix_par_place' => $prix_par_place
    ]);

    if (!$result) {
        throw new Exception('Erreur lors de la création du trajet');
    }

    $trajet_id = $pdo->lastInsertId();

    // Si un commentaire a été ajouté, l'enregistrer dans une table dédiée
    if (!empty($commentaire)) {
        // Pour l'instant on peut l'ignorer car la table commentaires n'existe pas encore
        // On pourrait l'ajouter plus tard
    }

    // Valider la transaction
    $pdo->commit();

    // Retourner le succès
    echo json_encode([
        'success' => true,
        'message' => 'Trajet créé avec succès ! Il est maintenant visible par les autres utilisateurs.',
        'trajet_id' => $trajet_id
    ]);

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log l'erreur pour debug
    error_log('Erreur création trajet: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création du trajet. Veuillez réessayer.'
    ]);
}
?>