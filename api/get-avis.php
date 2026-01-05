<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/init.php';

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');
} catch(PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]));
}

// Récupérer les paramètres
$user_id = intval($_GET['user_id'] ?? 0);
$limit = intval($_GET['limit'] ?? 10);
$offset = intval($_GET['offset'] ?? 0);

// Validation
if ($user_id <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'ID utilisateur invalide'
    ]));
}

if ($limit < 1 || $limit > 50) {
    $limit = 10;
}

try {
    // Récupérer les avis reçus par l'utilisateur - Unifié MySQL/PostgreSQL après migration
    $dateField = $isPostgreSQL ? 'created_at' : 'date_creation';

    $stmt = $pdo->prepare("
        SELECT
            a.avis_id,
            a.note,
            a.commentaire,
            a.{$dateField} as created_at,
            a.covoiturage_id,
            u_auteur.utilisateur_id as evaluateur_id,
            u_auteur.pseudo as evaluateur_pseudo,
            c.ville_depart,
            c.ville_arrivee,
            c.date_depart
        FROM avis a
        JOIN utilisateur u_auteur ON a.auteur_id = u_auteur.utilisateur_id
        JOIN covoiturage c ON a.covoiturage_id = c.covoiturage_id
        WHERE a.destinataire_id = :user_id
        AND a.statut = 'valide'
        ORDER BY a.{$dateField} DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la note moyenne - Unifié MySQL/PostgreSQL après migration
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_avis,
            AVG(note) as note_moyenne
        FROM avis
        WHERE destinataire_id = :user_id
        AND statut = 'valide'
    ");
    $stmt->execute(['user_id' => $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Formater les avis
    $avis_formates = [];
    foreach ($avis as $a) {
        $avis_formates[] = [
            'avis_id' => $a['avis_id'],
            'note' => intval($a['note']),
            'commentaire' => $a['commentaire'],
            'date' => $a['created_at'],
            'evaluateur' => [
                'id' => $a['evaluateur_id'],
                'pseudo' => $a['evaluateur_pseudo']
            ],
            'trajet' => [
                'id' => $a['covoiturage_id'],
                'depart' => $a['ville_depart'],
                'arrivee' => $a['ville_arrivee'],
                'date' => $a['date_depart']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'avis' => $avis_formates,
        'stats' => [
            'total' => intval($stats['total_avis']),
            'moyenne' => $stats['note_moyenne'] ? round(floatval($stats['note_moyenne']), 1) : 0
        ],
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => count($avis) === $limit
        ]
    ]);

} catch (Exception $e) {
    error_log('Erreur récupération avis: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des avis'
    ]);
}
?>
