<?php
/**
 * api/search-trajets.php
 * API pour rechercher les trajets disponibles
 */

// Configuration
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Connexion à la base de données
try {
    // Connexion Railway adaptative
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

// Récupérer les paramètres de recherche
$ville_depart = isset($_POST['ville_depart']) ? trim($_POST['ville_depart']) : '';
$ville_arrivee = isset($_POST['ville_arrivee']) ? trim($_POST['ville_arrivee']) : '';
$date_depart = isset($_POST['date_depart']) ? $_POST['date_depart'] : '';

// Validation des paramètres
if (empty($ville_depart) || empty($ville_arrivee) || empty($date_depart)) {
    die(json_encode([
        'success' => false,
        'message' => 'Veuillez remplir tous les champs de recherche'
    ]));
}

try {
    // Requête pour chercher les trajets
    // On joint plusieurs tables pour avoir toutes les infos nécessaires
    $sql = "
        SELECT 
            t.id_trajet,
            t.ville_depart,
            t.ville_arrivee,
            t.date_depart,
            t.heure_depart,
            t.heure_arrivee,
            t.places_disponibles,
            t.prix,
            t.statut,
            -- Info du conducteur
            u.pseudo as conducteur_pseudo,
            u.photo as conducteur_photo,
            -- Info du véhicule
            v.marque,
            v.modele,
            v.type_carburant,
            v.couleur,
            -- Note moyenne du conducteur (calculée depuis les avis)
            COALESCE(AVG(a.note), 0) as note_moyenne,
            COUNT(DISTINCT a.id_avis) as nb_avis
        FROM 
            trajets t
            INNER JOIN utilisateur u ON t.id_conducteur = u.id_utilisateur
            INNER JOIN vehicules v ON t.id_vehicule = v.id_vehicule
            LEFT JOIN avis a ON u.id_utilisateur = a.id_destinataire AND a.statut = 'valide'
        WHERE 
            LOWER(t.ville_depart) LIKE LOWER(:ville_depart)
            AND LOWER(t.ville_arrivee) LIKE LOWER(:ville_arrivee)
            AND DATE(t.date_depart) = :date_depart
            AND t.places_disponibles > 0
            AND t.statut = 'planifie'
            AND u.actif = 1
        GROUP BY 
            t.id_trajet
        ORDER BY 
            t.heure_depart ASC
    ";
    
    // Préparer et exécuter la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'ville_depart' => '%' . $ville_depart . '%',
        'ville_arrivee' => '%' . $ville_arrivee . '%',
        'date_depart' => $date_depart
    ]);
    
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les résultats
    foreach ($trajets as &$trajet) {
        // Arrondir la note moyenne
        $trajet['note_moyenne'] = round($trajet['note_moyenne'], 1);
        
        // Formater le prix
        $trajet['prix'] = number_format($trajet['prix'], 0, ',', ' ');
        
        // Convertir les places en nombre
        $trajet['places_disponibles'] = intval($trajet['places_disponibles']);
    }
    
    // Si aucun trajet trouvé, chercher des dates alternatives
    $alternatives = [];
    if (count($trajets) === 0) {
        $sqlAlt = "
            SELECT DISTINCT DATE(date_depart) as date_alternative
            FROM trajets t
            INNER JOIN utilisateur u ON t.id_conducteur = u.id_utilisateur
            WHERE 
                LOWER(t.ville_depart) LIKE LOWER(:ville_depart)
                AND LOWER(t.ville_arrivee) LIKE LOWER(:ville_arrivee)
                AND t.places_disponibles > 0
                AND t.statut = 'planifie'
                AND u.actif = 1
                AND DATE(t.date_depart) > CURDATE()
            ORDER BY date_depart ASC
            LIMIT 5
        ";
        
        $stmtAlt = $pdo->prepare($sqlAlt);
        $stmtAlt->execute([
            'ville_depart' => '%' . $ville_depart . '%',
            'ville_arrivee' => '%' . $ville_arrivee . '%'
        ]);
        
        $altResults = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($altResults as $alt) {
            $alternatives[] = $alt['date_alternative'];
        }
    }
    
    // Retourner les résultats
    echo json_encode([
        'success' => true,
        'trajets' => $trajets,
        'alternatives' => $alternatives,
        'message' => count($trajets) . ' trajet(s) trouvé(s)'
    ]);
    
} catch (Exception $e) {
    // Log l'erreur pour debug
    error_log('Erreur recherche trajets: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la recherche des trajets : ' . $e->getMessage()
    ]);
}
?>