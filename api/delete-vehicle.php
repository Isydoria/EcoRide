<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour supprimer un véhicule'
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
} catch(PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]));
}

// Récupérer et valider les données
$user_id = $_SESSION['user_id'];
$vehicle_id = intval($_POST['vehicle_id'] ?? 0);

// Validation
if ($vehicle_id <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'ID véhicule invalide'
    ]));
}

try {
    // Vérifier que le véhicule appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT voiture_id, marque, modele FROM voiture WHERE voiture_id = :vehicle_id AND utilisateur_id = :user_id");
    $stmt->execute([
        'vehicle_id' => $vehicle_id,
        'user_id' => $user_id
    ]);

    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        die(json_encode([
            'success' => false,
            'message' => 'Véhicule non trouvé ou non autorisé'
        ]));
    }

    // Vérifier si le véhicule est utilisé dans des trajets à venir
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM covoiturage
        WHERE voiture_id = :vehicle_id
        AND date_depart >= CURDATE()
        AND statut IN ('actif', 'en_cours')
    ");
    $stmt->execute(['vehicle_id' => $vehicle_id]);
    $upcoming_trips = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($upcoming_trips['count'] > 0) {
        die(json_encode([
            'success' => false,
            'message' => 'Impossible de supprimer ce véhicule car il est utilisé dans des trajets à venir. Annulez d\'abord ces trajets.'
        ]));
    }

    // Commencer une transaction
    $pdo->beginTransaction();

    // Mettre à jour les anciens trajets pour ne plus référencer ce véhicule
    $stmt = $pdo->prepare("UPDATE covoiturage SET voiture_id = NULL WHERE voiture_id = :vehicle_id");
    $stmt->execute(['vehicle_id' => $vehicle_id]);

    // Supprimer le véhicule
    $stmt = $pdo->prepare("DELETE FROM voiture WHERE voiture_id = :vehicle_id AND utilisateur_id = :user_id");
    $result = $stmt->execute([
        'vehicle_id' => $vehicle_id,
        'user_id' => $user_id
    ]);

    if (!$result || $stmt->rowCount() === 0) {
        throw new Exception('Erreur lors de la suppression du véhicule');
    }

    // Valider la transaction
    $pdo->commit();

    // Retourner le succès
    echo json_encode([
        'success' => true,
        'message' => 'Véhicule "' . $vehicle['marque'] . ' ' . $vehicle['modele'] . '" supprimé avec succès !'
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log l'erreur pour debug
    error_log('Erreur suppression véhicule: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression du véhicule. Veuillez réessayer.'
    ]);
}
?>