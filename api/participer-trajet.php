<?php
/**
 * api/participer-trajet.php
 * API pour réserver un trajet (US6) - VERSION CORRIGÉE
 */

// Configuration
session_start();

// Charger les fonctions globales (inclut jsonResponse)
require_once __DIR__ . '/../config/init.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    jsonResponse(false, 'Token CSRF invalide. Veuillez recharger la page.');
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Vous devez être connecté pour réserver un trajet');
}

$user_id = $_SESSION['user_id'];

// Connexion à la base de données
try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');
} catch(Exception $e) {
    jsonResponse(false, 'Erreur de connexion à la base de données', null, $e->getMessage());
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Méthode non autorisée');
}

// Récupérer les paramètres
$trajet_id = isset($_POST['trajet_id']) ? intval($_POST['trajet_id']) : 0;
$nombre_places = isset($_POST['nombre_places']) ? intval($_POST['nombre_places']) : 1;

// Validation des paramètres
if ($trajet_id <= 0) {
    jsonResponse(false, 'ID de trajet invalide');
}

if ($nombre_places <= 0 || $nombre_places > 4) {
    jsonResponse(false, 'Nombre de places invalide (1-4)');
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // ✅ 1. Récupérer les informations du trajet avec verrouillage
    if ($isPostgreSQL) {
        $sqlTrajet = "
            SELECT
                c.covoiturage_id,
                c.conducteur_id,
                c.places_disponibles,
                c.prix as prix_par_place,
                c.statut,
                c.date_depart,
                c.ville_depart,
                c.ville_arrivee
            FROM
                covoiturage c
            WHERE
                c.covoiturage_id = :trajet_id
            FOR UPDATE
        ";
    } else {
        $sqlTrajet = "
            SELECT
                c.covoiturage_id,
                c.conducteur_id,
                c.places_disponibles,
                c.prix_par_place,
                c.statut,
                c.date_depart,
                c.ville_depart,
                c.ville_arrivee
            FROM
                covoiturage c
            WHERE
                c.covoiturage_id = :trajet_id
            FOR UPDATE
        ";
    }

    $stmt = $pdo->prepare($sqlTrajet);
    $stmt->execute(['trajet_id' => $trajet_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trajet) {
        $pdo->rollBack();
        jsonResponse(false, 'Trajet introuvable');
    }
    
    // ✅ 2. Vérifications diverses

    // Vérifier que le trajet est encore planifié
    if ($trajet['statut'] !== 'planifie') {
        $pdo->rollBack();
        jsonResponse(false, 'Ce trajet n\'est plus disponible à la réservation');
    }

    // Vérifier que le trajet n'est pas dans le passé
    if (strtotime($trajet['date_depart']) <= time()) {
        $pdo->rollBack();
        jsonResponse(false, 'Impossible de réserver un trajet dont la date de départ est passée');
    }

    // Vérifier que l'utilisateur n'est pas le conducteur
    if ($trajet['conducteur_id'] == $user_id) {
        $pdo->rollBack();
        jsonResponse(false, 'Vous ne pouvez pas réserver votre propre trajet');
    }
    
    // Vérifier qu'il reste assez de places
    if ($trajet['places_disponibles'] < $nombre_places) {
        $pdo->rollBack();
        jsonResponse(false, 'Il ne reste que ' . $trajet['places_disponibles'] . ' place(s) disponible(s)');
    }
    
    // Vérifier que l'utilisateur n'a pas déjà réservé ce trajet
    if ($isPostgreSQL) {
        $sqlCheck = "
            SELECT participation_id
            FROM participation
            WHERE covoiturage_id = :trajet_id
            AND passager_id = :user_id
            AND statut_reservation IN ('en_attente', 'confirmee')
        ";
    } else {
        $sqlCheck = "
            SELECT participation_id
            FROM participation
            WHERE covoiturage_id = :trajet_id
            AND passager_id = :user_id
            AND statut IN ('reserve', 'confirme')
        ";
    }

    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        'trajet_id' => $trajet_id,
        'user_id' => $user_id
    ]);

    if ($stmtCheck->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'Vous avez déjà réservé ce trajet');
    }
    
    // ✅ 3. Calculer le coût total (prix + 2 crédits de commission)
    $prix_place = floatval($trajet['prix_par_place']);
    $commission = 2;
    $cout_total = round(($prix_place * $nombre_places) + $commission, 2);
    
    // ✅ 4. Vérifier que l'utilisateur a assez de crédits
    if ($isPostgreSQL) {
        $sqlCredits = "SELECT credits FROM utilisateur WHERE utilisateur_id = :user_id";
    } else {
        $sqlCredits = "SELECT credit FROM utilisateur WHERE utilisateur_id = :user_id";
    }
    $stmtCredits = $pdo->prepare($sqlCredits);
    $stmtCredits->execute(['user_id' => $user_id]);
    $credits_actuels = $stmtCredits->fetchColumn();
    
    if ($credits_actuels < $cout_total) {
        $pdo->rollBack();
        jsonResponse(false, 'Crédits insuffisants. Vous avez ' . $credits_actuels . ' crédits, il vous en faut ' . $cout_total);
    }
    
    // ✅ 5. Créer la réservation
    if ($isPostgreSQL) {
        $sqlReservation = "
            INSERT INTO participation (
                covoiturage_id,
                passager_id,
                places_reservees,
                statut_reservation,
                created_at
            ) VALUES (
                :trajet_id,
                :user_id,
                :nombre_places,
                'en_attente',
                CURRENT_TIMESTAMP
            )
            RETURNING participation_id
        ";
    } else {
        $sqlReservation = "
            INSERT INTO participation (
                covoiturage_id,
                passager_id,
                nombre_places,
                credit_utilise,
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
    }

    $stmtReservation = $pdo->prepare($sqlReservation);

    if ($isPostgreSQL) {
        $stmtReservation->execute([
            'trajet_id' => $trajet_id,
            'user_id' => $user_id,
            'nombre_places' => $nombre_places
        ]);
        $result = $stmtReservation->fetch(PDO::FETCH_ASSOC);
        $reservation_id = $result['participation_id'];
    } else {
        $stmtReservation->execute([
            'trajet_id' => $trajet_id,
            'user_id' => $user_id,
            'nombre_places' => $nombre_places,
            'credits_utilises' => $cout_total
        ]);
        $reservation_id = $pdo->lastInsertId();
    }
    
    // ✅ 6. Mettre à jour les places disponibles du trajet
    $sqlUpdatePlaces = "
        UPDATE covoiturage 
        SET places_disponibles = places_disponibles - :nombre_places 
        WHERE covoiturage_id = :trajet_id
    ";
    
    $stmtUpdatePlaces = $pdo->prepare($sqlUpdatePlaces);
    $stmtUpdatePlaces->execute([
        'nombre_places' => $nombre_places,
        'trajet_id' => $trajet_id
    ]);
    
    // ✅ 7. Débiter les crédits de l'utilisateur
    if ($isPostgreSQL) {
        $sqlUpdateCredits = "
            UPDATE utilisateur
            SET credits = credits - :cout_total
            WHERE utilisateur_id = :user_id
        ";
    } else {
        $sqlUpdateCredits = "
            UPDATE utilisateur
            SET credit = credit - :cout_total
            WHERE utilisateur_id = :user_id
        ";
    }

    $stmtUpdateCredits = $pdo->prepare($sqlUpdateCredits);
    $stmtUpdateCredits->execute([
        'cout_total' => $cout_total,
        'user_id' => $user_id
    ]);
    
    // ✅ 8. Enregistrer la transaction (seulement pour MySQL)
    if (!$isPostgreSQL) {
        $sqlTransaction = "
            INSERT INTO transaction_credit (
                utilisateur_id,
                montant,
                type,
                description,
                reference_id,
                reference_type
            ) VALUES (
                :user_id,
                :montant,
                'debit',
                :description,
                :reference_id,
                'participation'
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
    }
    
    // ✅ 9. Valider la transaction
    $pdo->commit();
    
    // ✅ 10. Mettre à jour la session avec les nouveaux crédits
    $_SESSION['credits'] = $credits_actuels - $cout_total;
    
    // Retourner le succès
    jsonResponse(true, 'Réservation confirmée ! ' . $cout_total . ' crédits ont été débités.', [
        'reservation_id' => $reservation_id,
        'nouveaux_credits' => $credits_actuels - $cout_total,
        'cout_total' => $cout_total
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log l'erreur pour debug
    error_log('Erreur participer-trajet: ' . $e->getMessage());
    
    jsonResponse(false, 'Une erreur est survenue lors de la réservation', null, $e->getMessage());
}
?>