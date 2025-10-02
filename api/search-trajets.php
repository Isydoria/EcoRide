<?php
/**
 * api/search-trajets.php
 * API pour rechercher les trajets disponibles
 */

// Configuration
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Connexion à la base de données avec la classe Database
require_once '../config/init.php';

try {
    $pdo = db();
} catch(Exception $e) {
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

// Récupérer les filtres (US4)
$ecologique = isset($_POST['ecologique']) ? $_POST['ecologique'] === 'true' : false;
$prix_max = isset($_POST['prix_max']) && !empty($_POST['prix_max']) ? floatval($_POST['prix_max']) : null;
$duree_max = isset($_POST['duree_max']) && !empty($_POST['duree_max']) ? floatval($_POST['duree_max']) : null;
$note_min = isset($_POST['note_min']) && !empty($_POST['note_min']) ? intval($_POST['note_min']) : null;

// Validation des paramètres
if (empty($ville_depart) || empty($ville_arrivee) || empty($date_depart)) {
    die(json_encode([
        'success' => false,
        'message' => 'Veuillez remplir tous les champs de recherche'
    ]));
}

try {
    // Construire la requête avec les filtres dynamiques (US4)
    $whereConditions = [
        "LOWER(t.ville_depart) LIKE LOWER(:ville_depart)",
        "LOWER(t.ville_arrivee) LIKE LOWER(:ville_arrivee)",
        "DATE(t.date_depart) = :date_depart",
        "t.places_disponibles > 0",
        "t.statut = 'planifie'",
        "u.statut = 'actif'"
    ];

    $params = [
        'ville_depart' => '%' . $ville_depart . '%',
        'ville_arrivee' => '%' . $ville_arrivee . '%',
        'date_depart' => $date_depart
    ];

    // Filtre écologique (US4)
    if ($ecologique) {
        $whereConditions[] = "v.energie = 'electrique'";
    }

    // Filtre prix maximum (US4)
    if ($prix_max !== null) {
        $whereConditions[] = "t.prix_par_place <= :prix_max";
        $params['prix_max'] = $prix_max;
    }

    // Filtre durée maximum (US4) - calculée entre date_depart et date_arrivee
    if ($duree_max !== null) {
        $whereConditions[] = "TIMESTAMPDIFF(HOUR, t.date_depart, t.date_arrivee) <= :duree_max";
        $params['duree_max'] = $duree_max;
    }

    $havingConditions = [];
    // Filtre note minimum (US4) - appliqué après GROUP BY
    if ($note_min !== null) {
        $havingConditions[] = "note_moyenne >= :note_min";
        $params['note_min'] = $note_min;
    }

    $sql = "
        SELECT
            t.covoiturage_id as id_trajet,
            t.ville_depart,
            t.ville_arrivee,
            t.date_depart,
            t.date_depart as heure_depart,
            t.date_arrivee as heure_arrivee,
            t.places_disponibles,
            t.prix_par_place as prix,
            t.statut,
            -- Durée calculée
            TIMESTAMPDIFF(HOUR, t.date_depart, t.date_arrivee) as duree_heures,
            -- Info du conducteur
            u.pseudo as conducteur_pseudo,
            u.photo as conducteur_photo,
            -- Info du véhicule
            v.marque,
            v.modele,
            v.energie as type_carburant,
            v.couleur,
            -- Note moyenne du conducteur (calculée depuis les avis)
            COALESCE(AVG(a.note), 0) as note_moyenne,
            COUNT(DISTINCT a.avis_id) as nb_avis
        FROM
            covoiturage t
            INNER JOIN utilisateur u ON t.conducteur_id = u.utilisateur_id
            INNER JOIN voiture v ON t.voiture_id = v.voiture_id
            LEFT JOIN avis a ON u.utilisateur_id = a.destinataire_id AND a.statut = 'valide'
        WHERE
            " . implode(' AND ', $whereConditions) . "
        GROUP BY
            t.covoiturage_id
        " . (empty($havingConditions) ? "" : "HAVING " . implode(' AND ', $havingConditions)) . "
        ORDER BY
            t.date_depart ASC
    ";

    // Préparer et exécuter la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les résultats
    foreach ($trajets as &$trajet) {
        // Arrondir la note moyenne
        $trajet['note_moyenne'] = round($trajet['note_moyenne'], 1);

        // Formater le prix (garder la valeur numérique pour les filtres)
        $trajet['prix_formatted'] = number_format($trajet['prix'], 0, ',', ' ');

        // Convertir les places en nombre
        $trajet['places_disponibles'] = intval($trajet['places_disponibles']);

        // Formater la durée
        $trajet['duree_heures'] = floatval($trajet['duree_heures'] ?? 0);
        $trajet['duree_formatted'] = $trajet['duree_heures'] . 'h';

        // Ajouter indicateur écologique
        $trajet['is_ecologique'] = ($trajet['type_carburant'] === 'electrique');
    }
    
    // Si aucun trajet trouvé, chercher des dates alternatives
    $alternatives = [];
    if (count($trajets) === 0) {
        $sqlAlt = "
            SELECT DISTINCT DATE(t.date_depart) as date_alternative
            FROM covoiturage t
            INNER JOIN utilisateur u ON t.conducteur_id = u.utilisateur_id
            WHERE
                LOWER(t.ville_depart) LIKE LOWER(:ville_depart)
                AND LOWER(t.ville_arrivee) LIKE LOWER(:ville_arrivee)
                AND t.places_disponibles > 0
                AND t.statut = 'planifie'
                AND u.statut = 'actif'
                AND DATE(t.date_depart) > CURDATE()
            ORDER BY DATE(t.date_depart) ASC
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