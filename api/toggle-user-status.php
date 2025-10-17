<?php
// api/toggle-user-status.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    die(json_encode(['success' => false, 'message' => 'Accès non autorisé']));
}

require_once '../config/init.php';

$data = json_decode(file_get_contents('php://input'), true);

// Vérifier le token CSRF
if (!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Token CSRF invalide. Veuillez recharger la page.'
    ]));
}
$user_id = $data['user_id'] ?? null;
$action = $data['action'] ?? null;

if (!$user_id || !$action) {
    die(json_encode(['success' => false, 'message' => 'Paramètres manquants']));
}

try {
    $pdo = db();
    
    // Empêcher l'admin de se suspendre lui-même
    if ($user_id == $_SESSION['user_id']) {
        throw new Exception("Vous ne pouvez pas modifier votre propre statut");
    }
    
    $new_status = ($action === 'suspend') ? 'suspendu' : 'actif';

    $stmt = $pdo->prepare("UPDATE utilisateur SET statut = :statut WHERE utilisateur_id = :user_id");
    $stmt->execute([
        'statut' => $new_status,
        'user_id' => $user_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Statut modifié avec succès",
        'new_status' => $new_status
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>