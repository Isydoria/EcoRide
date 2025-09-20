<?php
/**
 * api/participer-trajet.php
 * API pour permettre à un utilisateur de participer à un trajet (US6)
 */

// Configuration
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Démarrer la session pour vérifier l'utilisateur
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour réserver un trajet'
    ]));
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$user_credits = $_SESSION['user_credits'] ?? 0;

// Connexion à la base de données
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=ecoride_db;charset=utf8mb4',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]));
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]));
}

// Récupérer les paramètres
$trajet_id = isset($_POST['trajet_id']) ? intval($_POST['trajet_id']) : 0;
$nombre_places = isset($_POST['nombre_places']) ? intval($_POST['nombre_places']) : 1;

// Validation des paramètres
if ($trajet_id <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'ID de trajet invalide'
    ]));
}

if ($nombre_places <= 0 || $nombre_places > 4) {
    die(json_encode([
        'success' => false,
        'message' => 'Nombre de places invalide'
    ]));
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // 1. Récupérer les informations du trajet avec verrouillage
    $sqlTrajet = "
        SELECT 
            t.id_trajet,
            t.id_conducteur,
            t.places_disponibles,
            t.prix,
            t.statut,
            t.date_depart,
            t.ville_depart,
            t.ville_arrivee
        FROM 
            trajets t
        WHERE 
            t.id_trajet = :trajet_id
        FOR UPDATE
    ";
    
    $stmt = $pdo->prepare($sqlTrajet);
    $stmt->execute(['trajet_id' => $trajet_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trajet) {
        $pdo->rollBack();
        die(json_encode([
            'success' => false,
            'message' => 'Trajet introuvable'
        ]));
    }
    
    // 2. Vérifications diverses
    
    // Vérifier que le trajet est encore planifié
    if ($trajet['statut'] !== 'planifie') {
        $pdo->rollBack();
        die(json_encode([
            'success' => false,
            'message' => 'Ce trajet n\'est plus disponible à la réservation'
        ]));
    }
    
    // Vérifier que l'utilisateur n'est pas le conducteur
    if ($trajet['id_conducteur'] == $user_id) {
        $pdo->rollBack();
        die(json_encode([
            'success' => false,
            'message' => 'Vous ne pouvez pas réserver votre propre trajet'
        ]));
    }
    
    // Vérifier qu'il reste assez de places
    if ($trajet['places_disponibles'] < $nombre_places) {
        $pdo->rollBack();
        die(json_encode([
            'success' => false,
            'message' => 'Il ne reste que ' . $trajet['places_disponibles'] . ' place(s) disponible(s)'
        ]));
    }
    
    // Vérifier que l'utilisateur n'a pas déjà réservé ce trajet
    $sqlCheck = "
        SELECT id_reservation 
        FROM reservations 
        WHERE id_trajet = :trajet_id 
        AND id_passager = :user_id 
        AND statut IN ('reserve', 'confirme')
    ";
    
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        'trajet_id' => $trajet_id,
        'user_id' => $user_id
    ]);
    
    if ($stmtCheck->fetch()) {
        $pdo->rollBack();
        die(json_encode([
            'success' => false,
            'message' => 'Vous avez déjà réservé ce trajet'
        ]));
    }
    
    // 3. Calculer le coût total (prix + 2 crédits de commission)
    $prix_place = floatval($trajet['prix']);
    $commission = 2;
    $cout_total = ($prix_place * $nombre_places) + $commission;
    
    // 4. Vérifier que l'utilisateur a assez de crédits
    // D'abord, récupérer les crédits actuels depuis la base
    $sqlCredits = "SELECT credits FROM utilisateurs WHERE id_utilisateur = :user_id";
    $stmtCredits = $pdo->prepare($sqlCredits);
    $stmtCredits->execute(['user_id' => $user_id]);
    $credits_actuels = $stmtCredits->fetchColumn();
    
    if ($credits_actuels < $cout_total) {
        $pdo->rollBack();
        die(json_encode([
            'success' => false,
            'message' => 'Crédits insuffisants. Vous avez ' . $credits_actuels . ' crédits, il vous en faut ' . $cout_total
        ]));
    }
    
    // 5. Créer la réservation
    $sqlReservation = "
        INSERT INTO reservations (
            id_trajet, 
            id_passager, 
            nombre_places, 
            credits_utilises, 
            statut, 
            date_reservation
        ) VALUES (
            :trajet_id, 
            :user_id, 
            :nombre_places, 
            :credits_utilises, 
            'reserve', 
            NOW()
        )
    ";
    
    $stmtReservation = $pdo->prepare($sqlReservation);
    $stmtReservation->execute([
        'trajet_id' => $trajet_id,
        'user_id' => $user_id,
        'nombre_places' => $nombre_places,
        'credits_utilises' => $cout_total
    ]);
    
    $reservation_id = $pdo->lastInsertId();
    
    // 6. Mettre à jour les places disponibles du trajet
    $sqlUpdatePlaces = "
        UPDATE trajets 
        SET places_disponibles = places_disponibles - :nombre_places 
        WHERE id_trajet = :trajet_id
    ";
    
    $stmtUpdatePlaces = $pdo->prepare($sqlUpdatePlaces);
    $stmtUpdatePlaces->execute([
        'nombre_places' => $nombre_places,
        'trajet_id' => $trajet_id
    ]);
    
    // 7. Débiter les crédits de l'utilisateur
    $sqlUpdateCredits = "
        UPDATE utilisateurs 
        SET credits = credits - :cout_total 
        WHERE id_utilisateur = :user_id
    ";
    
    $stmtUpdateCredits = $pdo->prepare($sqlUpdateCredits);
    $stmtUpdateCredits->execute([
        'cout_total' => $cout_total,
        'user_id' => $user_id
    ]);
    
    // 8. Enregistrer la transaction dans la table transactions
    $sqlTransaction = "
        INSERT INTO transactions (
            id_utilisateur, 
            montant, 
            type_transaction, 
            description, 
            reference_id, 
            type_reference
        ) VALUES (
            :user_id, 
            :montant, 
            'debit', 
            :description, 
            :reference_id, 
            'reservation'
        )
    ";
    
    $description = 'Réservation trajet ' . $trajet['ville_depart'] . ' → ' . $trajet['ville_arrivee'];
    
    $stmtTransaction = $pdo->prepare($sqlTransaction);
    $stmtTransaction->execute([
        'user_id' => $user_id,
        'montant' => $cout_total,
        'description' => $description,
        'reference_id' => $reservation_id
    ]);
    
    // 9. Valider la transaction
    $pdo->commit();
    
    // 10. Mettre à jour la session avec les nouveaux crédits
    $_SESSION['user_credits'] = $credits_actuels - $cout_total;
    
    // Retourner le succès
    echo json_encode([
        'success' => true,
        'message' => 'Réservation confirmée ! ' . $cout_total . ' crédits ont été débités.',
        'reservation_id' => $reservation_id,
        'nouveaux_credits' => $credits_actuels - $cout_total
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log l'erreur pour debug
    error_log('Erreur participer-trajet: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la réservation. Veuillez réessayer.'
    ]);
}
?>