<?php
/**
 * api/get-trajet-detail.php
 * API pour récupérer tous les détails d'un trajet spécifique
 */

// Configuration
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session pour vérifier si l'utilisateur est connecté
session_start();

// Connexion à la base de données Railway
try {
    $host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? 'localhost';
    $dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecoride_db';
    $username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? 'root';
    $password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur DB: ' . $e->getMessage()
    ]));
}

// Log pour debug
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

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
    // Requête principale pour récupérer les détails du trajet
    $sql = "
        SELECT
            t.covoiturage_id as id_trajet,
            t.conducteur_id as id_conducteur,
            t.ville_depart,
            t.ville_arrivee,
            t.date_depart,
            t.date_depart as heure_depart,
            t.date_arrivee as heure_arrivee,
            t.places_disponibles,
            t.prix_par_place as prix,
            t.statut,
            -- Adresses détaillées
            t.ville_depart as adresse_depart,
            t.ville_arrivee as adresse_arrivee,
            -- Info du conducteur
            u.pseudo as conducteur_pseudo,
            u.photo as conducteur_photo,
            u.created_at as membre_depuis,
            -- Info du véhicule
            v.marque,
            v.modele,
            v.energie as type_carburant,
            v.couleur,
            v.places as nombre_places_vehicule,
            -- Note moyenne du conducteur
            COALESCE(AVG(av.note), 0) as note_moyenne,
            COUNT(DISTINCT av.avis_id) as nb_avis,
            -- Nombre total de trajets du conducteur
            (SELECT COUNT(*) FROM covoiturage WHERE conducteur_id = u.utilisateur_id) as total_trajets
        FROM
            covoiturage t
            INNER JOIN utilisateur u ON t.conducteur_id = u.utilisateur_id
            INNER JOIN voiture v ON t.voiture_id = v.voiture_id
            LEFT JOIN avis av ON u.utilisateur_id = av.destinataire_id AND av.statut = 'valide'
        WHERE
            t.covoiturage_id = :trajet_id
        GROUP BY
            t.covoiturage_id
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
    
    // Récupérer les préférences du conducteur
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
    
    // Ajouter les préférences au trajet
    $trajet['preferences'] = $preferences;
    
    // Récupérer les avis validés sur le conducteur
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
    
    // Ajouter les avis au trajet
    $trajet['avis'] = $avis;
    
    // Si l'utilisateur est connecté, vérifier s'il a déjà réservé ce trajet
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
    
    // Retourner les données
    echo json_encode([
        'success' => true,
        'trajet' => $trajet
    ]);
    
} catch (Exception $e) {
    // Log l'erreur pour debug
    error_log('Erreur get-trajet-detail: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des détails'
    ]);
}
?>