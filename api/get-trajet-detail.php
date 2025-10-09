<?php
/**
 * api/get-trajet-detail.php
 * API pour récupérer tous les détails d'un trajet - VERSION CORRIGÉE
 */

// Configuration
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Connexion à la base de données
require_once '../config/init.php';

try {
    $pdo = db();
} catch(Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur DB: ' . $e->getMessage()
    ]));
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]));
}

// Récupérer l'ID du trajet
$trajet_id = isset($_POST['trajet_id']) ? intval($_POST['trajet_id']) : 0;

if ($trajet_id <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'ID de trajet invalide'
    ]));
}

// ID de l'utilisateur connecté (si connecté)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // ✅ Requête principale CORRIGÉE avec les bons noms de colonnes
    $sql = "
        SELECT
            c.covoiturage_id as id_trajet,
            c.conducteur_id as id_conducteur,
            c.ville_depart,
            c.ville_arrivee,
            c.adresse_depart,
            c.adresse_arrivee,
            c.date_depart,
            c.date_arrivee,
            c.places_disponibles,
            c.prix_par_place as prix,
            c.statut,
            -- Info du conducteur
            u.pseudo as conducteur_pseudo,
            u.photo as conducteur_photo,
            u.created_at as membre_depuis,
            -- Info du véhicule
            v.marque,
            v.modele,
            v.couleur,
            v.places as nombre_places_vehicule,
            v.energie as type_carburant,
            -- Note moyenne du conducteur
            COALESCE(AVG(a.note), 0) as note_moyenne,
            COUNT(DISTINCT a.avis_id) as nb_avis,
            -- Nombre total de trajets du conducteur
            (SELECT COUNT(*) FROM covoiturage WHERE conducteur_id = u.utilisateur_id) as total_trajets
        FROM
            covoiturage c
            INNER JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN avis a ON u.utilisateur_id = a.destinataire_id AND a.statut = 'valide'
        WHERE
            c.covoiturage_id = :trajet_id
        GROUP BY
            c.covoiturage_id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['trajet_id' => $trajet_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        die(json_encode([
            'success' => false,
            'message' => 'Trajet introuvable'
        ]));
    }
    
    // ✅ Récupérer les préférences du conducteur
    $sqlPref = "
        SELECT
            fumeur as accepte_fumeur,
            animaux as accepte_animaux,
            musique as accepte_musique,
            discussion as accepte_discussion,
            preferences_custom as preferences_autres
        FROM
            parametre
        WHERE
            utilisateur_id = :conducteur_id
    ";

    $stmtPref = $pdo->prepare($sqlPref);
    $stmtPref->execute(['conducteur_id' => $trajet['id_conducteur']]);
    $preferences = $stmtPref->fetch(PDO::FETCH_ASSOC);

    // Valeurs par défaut si pas de préférences
    if (!$preferences) {
        $preferences = [
            'accepte_fumeur' => false,
            'accepte_animaux' => false,
            'accepte_musique' => true,
            'accepte_discussion' => true,
            'preferences_autres' => ''
        ];
    }

    $trajet['preferences'] = $preferences;

    // ✅ Récupérer les avis validés sur le conducteur
    $sqlAvis = "
        SELECT
            a.note,
            a.commentaire,
            a.created_at as date_creation,
            u.pseudo as auteur
        FROM
            avis a
            INNER JOIN utilisateur u ON a.auteur_id = u.utilisateur_id
        WHERE
            a.destinataire_id = :conducteur_id
            AND a.statut = 'valide'
        ORDER BY
            a.created_at DESC
        LIMIT 10
    ";

    $stmtAvis = $pdo->prepare($sqlAvis);
    $stmtAvis->execute(['conducteur_id' => $trajet['id_conducteur']]);
    $avis = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);

    $trajet['avis'] = $avis;
    
    // ✅ Vérifier si l'utilisateur a déjà réservé ce trajet
    if ($user_id) {
        $sqlReservation = "
            SELECT participation_id
            FROM participation
            WHERE covoiturage_id = :trajet_id
            AND passager_id = :user_id
            AND statut IN ('reserve', 'confirme')
        ";
        
        $stmtReservation = $pdo->prepare($sqlReservation);
        $stmtReservation->execute([
            'trajet_id' => $trajet_id,
            'user_id' => $user_id
        ]);
        
        $trajet['deja_reserve'] = $stmtReservation->fetch() ? true : false;
    } else {
        $trajet['deja_reserve'] = false;
    }
    
    // Formater certaines données
    $trajet['note_moyenne'] = round($trajet['note_moyenne'], 1);
    $trajet['prix'] = number_format($trajet['prix'], 0, ',', ' ');
    $trajet['places_disponibles'] = intval($trajet['places_disponibles']);
    
    // ✅ S'assurer que les adresses existent (fallback sur villes)
    if (empty($trajet['adresse_depart'])) {
        $trajet['adresse_depart'] = $trajet['ville_depart'];
    }
    if (empty($trajet['adresse_arrivee'])) {
        $trajet['adresse_arrivee'] = $trajet['ville_arrivee'];
    }

    // Retourner les données
    echo json_encode([
        'success' => true,
        'trajet' => $trajet
    ]);
    
} catch (Exception $e) {
    error_log('Erreur get-trajet-detail: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des détails',
        'debug' => $e->getMessage()
    ]);
}
?>