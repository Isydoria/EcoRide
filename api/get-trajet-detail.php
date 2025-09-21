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
            t.id_trajet,
            t.id_conducteur,
            t.ville_depart,
            t.ville_arrivee,
            t.date_depart,
            t.heure_depart,
            t.heure_arrivee,
            t.places_disponibles,
            t.prix,
            t.statut,
            -- Adresses détaillées (si disponibles dans votre base)
            t.ville_depart as adresse_depart,
            t.ville_arrivee as adresse_arrivee,
            -- Info du conducteur
            u.pseudo as conducteur_pseudo,
            u.photo as conducteur_photo,
            u.date_inscription as membre_depuis,
            -- Info du véhicule
            v.marque,
            v.modele,
            v.type_carburant,
            v.couleur,
            v.nombre_places as nombre_places_vehicule,
            -- Note moyenne du conducteur
            COALESCE(AVG(av.note), 0) as note_moyenne,
            COUNT(DISTINCT av.id_avis) as nb_avis,
            -- Nombre total de trajets du conducteur
            (SELECT COUNT(*) FROM trajets WHERE id_conducteur = u.id_utilisateur) as total_trajets
        FROM 
            trajets t
            INNER JOIN utilisateur u ON t.id_conducteur = u.id_utilisateur
            INNER JOIN vehicules v ON t.id_vehicule = v.id_vehicule
            LEFT JOIN avis av ON u.id_utilisateur = av.id_destinataire AND av.statut = 'valide'
        WHERE 
            t.id_trajet = :trajet_id
        GROUP BY 
            t.id_trajet
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
            accepte_fumeur,
            accepte_animaux,
            accepte_musique,
            accepte_discussion,
            preferences_autres
        FROM 
            preferences_conducteur
        WHERE 
            id_utilisateur = :conducteur_id
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
            a.date_creation,
            u.pseudo as auteur
        FROM 
            avis a
            INNER JOIN utilisateur u ON a.id_auteur = u.id_utilisateur
        WHERE 
            a.id_destinataire = :conducteur_id
            AND a.statut = 'valide'
        ORDER BY 
            a.date_creation DESC
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
            SELECT id_reservation 
            FROM reservations 
            WHERE id_trajet = :trajet_id 
            AND id_passager = :user_id
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