<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion.php');
    exit;
}

require_once '../config/init.php';

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

// Variables par défaut
$user_data = null;
$vehicles = [];
$my_trips = [];
$my_bookings = [];
$history_trips = [];
$history_bookings = [];
$stats = [
    'credits' => 0,
    'trips_created' => 0,
    'trips_taken' => 0,
    'vehicles' => 0
];

try {
    $pdo = db();

    // Détecter le type de base de données
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    // Récupérer les infos utilisateur actualisées
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE utilisateur_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        throw new Exception("Utilisateur non trouvé (ID: $user_id)");
    }

    // Récupérer les véhicules de l'utilisateur
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("SELECT * FROM vehicule WHERE id_conducteur = :user_id ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :user_id ORDER BY created_at DESC");
    }
    $stmt->execute(['user_id' => $user_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les trajets créés par l'utilisateur (conducteur)
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT c.*, c.covoiturage_id AS trip_id, v.marque, v.modele
            FROM covoiturage c
            LEFT JOIN vehicule v ON c.id_vehicule = v.vehicule_id
            WHERE c.id_conducteur = :user_id
            ORDER BY c.date_depart DESC
            LIMIT 10
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, c.covoiturage_id AS trip_id, v.marque, v.modele
            FROM covoiturage c
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            WHERE c.conducteur_id = :user_id
            ORDER BY c.date_depart DESC
            LIMIT 10
        ");
    }
    $stmt->execute(['user_id' => $user_id]);
    $my_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les participations (passager)
    if ($isPostgreSQL) {
        $stmt = $pdo->prepare("
            SELECT p.*, c.ville_depart, c.ville_arrivee, c.date_depart, c.prix,
                   (c.prix * p.places_reservees) as credit_utilise, u.pseudo as conducteur,
                   p.statut_reservation as statut
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u ON c.id_conducteur = u.utilisateur_id
            WHERE p.passager_id = :user_id
            ORDER BY c.date_depart DESC
            LIMIT 10
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT p.*, c.ville_depart, c.ville_arrivee, c.date_depart, c.prix_par_place, u.pseudo as conducteur
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            WHERE p.passager_id = :user_id
            ORDER BY c.date_depart DESC
            LIMIT 10
        ");
    }
    $stmt->execute(['user_id' => $user_id]);
    $my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Charger l'historique complet si on est dans la section historique
    if (isset($_GET['section']) && $_GET['section'] === 'history') {
        // Tous les trajets créés (conducteur) avec filtres de statut
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("
                SELECT c.*, v.marque, v.modele, 'conducteur' as role,
                       COUNT(p.participation_id) as participants
                FROM covoiturage c
                LEFT JOIN vehicule v ON c.id_vehicule = v.vehicule_id
                LEFT JOIN participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_reservation != 'annulee'
                WHERE c.id_conducteur = :user_id
                GROUP BY c.covoiturage_id, v.marque, v.modele
                ORDER BY c.date_depart DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT c.*, v.marque, v.modele, 'conducteur' as role,
                       COUNT(p.participation_id) as participants
                FROM covoiturage c
                LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
                LEFT JOIN participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut != 'annule'
                WHERE c.conducteur_id = :user_id
                GROUP BY c.covoiturage_id
                ORDER BY c.date_depart DESC
            ");
        }
        $stmt->execute(['user_id' => $user_id]);
        $history_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Toutes les participations (passager) avec filtres de statut
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("
                SELECT p.*, c.covoiturage_id, c.ville_depart, c.ville_arrivee, c.date_depart, c.date_arrivee,
                       c.prix, (c.prix * p.places_reservees) as credit_utilise, u.pseudo as conducteur, 'passager' as role,
                       c.statut as trip_status, p.statut_reservation as statut
                FROM participation p
                JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
                JOIN utilisateur u ON c.id_conducteur = u.utilisateur_id
                WHERE p.passager_id = :user_id
                ORDER BY c.date_depart DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT p.*, c.covoiturage_id, c.ville_depart, c.ville_arrivee, c.date_depart, c.date_arrivee,
                       c.prix_par_place, u.pseudo as conducteur, 'passager' as role,
                       c.statut as trip_status
                FROM participation p
                JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
                JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
                WHERE p.passager_id = :user_id
                ORDER BY c.date_depart DESC
            ");
        }
        $stmt->execute(['user_id' => $user_id]);
        $history_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Statistiques
    $stats = [
        'credits' => $isPostgreSQL ? ($user_data['credits'] ?? 0) : ($user_data['credit'] ?? 0),
        'trips_created' => count($my_trips),
        'trips_taken' => count($my_bookings),
        'vehicles' => count($vehicles)
    ];

} catch (Exception $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
}

// Gestion section active
$active_section = $_GET['section'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace - EcoRide</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            margin: 0;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #2ECC71, #27AE60);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .dashboard-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .dashboard-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .dashboard-nav a:hover {
            background: rgba(255,255,255,0.1);
        }

        .create-trip-btn {
            background: #f39c12 !important;
            font-weight: 600;
            padding: 12px 25px !important;
            border-radius: 25px !important;
            font-size: 16px;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }

        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            height: fit-content;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .sidebar h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 15px;
            color: #7f8c8d;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #2ECC71;
            color: white;
        }

        .main-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card.credits {
            background: linear-gradient(135deg, #2ECC71, #27AE60);
        }

        .stat-card.trips {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .stat-card.vehicles {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            opacity: 0.9;
            font-size: 0.9em;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .trips-list {
            display: grid;
            gap: 15px;
        }

        .trip-card {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            transition: shadow 0.3s;
        }

        .trip-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .trip-route {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .trip-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            color: #7f8c8d;
            font-size: 0.9em;
        }

        /* Véhicules */
        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .vehicle-card {
            background: white;
            border: 1px solid #ecf0f1;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .vehicle-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .vehicle-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .vehicle-title strong {
            font-size: 1.2em;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }

        .vehicle-registration {
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            color: #7f8c8d;
            font-family: monospace;
        }

        .vehicle-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            background: none;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .btn-icon:hover {
            background: #f8f9fa;
        }

        .edit-vehicle:hover {
            background: #e3f2fd;
        }

        .delete-vehicle:hover {
            background: #ffebee;
        }

        .vehicle-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-item .label {
            font-size: 0.9em;
            color: #7f8c8d;
            font-weight: 500;
        }

        .detail-item .value {
            font-weight: 600;
            color: #2c3e50;
        }

        .energy-electrique {
            color: #2ecc71;
        }

        .energy-hybride {
            color: #f39c12;
        }

        .energy-essence {
            color: #e74c3c;
        }

        .energy-diesel {
            color: #34495e;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }

        .empty-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .add-vehicle-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        /* Historique des trajets */
        .history-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-control {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .history-list {
            display: grid;
            gap: 15px;
        }

        .history-item {
            background: white;
            border: 1px solid #ecf0f1;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .history-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .history-route {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
        }

        .history-badges {
            display: flex;
            gap: 10px;
        }

        .role-badge, .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .role-conductor {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-passenger {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .status-planned {
            background: #fff3e0;
            color: #f57f17;
        }

        .status-active {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-completed {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .history-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .history-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            flex: 1;
        }

        .history-info > div {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .history-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85em;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        /* Responsive pour mobile */
        @media (max-width: 768px) {
            .history-filters {
                grid-template-columns: 1fr;
            }

            .history-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .history-badges {
                flex-wrap: wrap;
            }

            .history-details {
                flex-direction: column;
                align-items: flex-start;
            }

            .history-info {
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2ECC71;
            color: white;
        }

        .btn-primary:hover {
            background: #27AE60;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .vehicle-card {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .vehicle-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            align-items: center;
            gap: 15px;
        }

        /* ========= AVIS ========= */
        .avis-list {
            display: grid;
            gap: 20px;
        }

        .avis-card {
            background: white;
            border: 1px solid #ecf0f1;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .avis-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .avis-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .avis-author {
            font-weight: 600;
            color: #2c3e50;
        }

        .avis-date {
            font-size: 0.85em;
            color: #7f8c8d;
        }

        .avis-rating {
            display: flex;
            gap: 4px;
            font-size: 1.2em;
        }

        .star {
            color: #ddd;
        }

        .star.filled {
            color: #f39c12;
        }

        .avis-comment {
            color: #2c3e50;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .avis-trip-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .avis-stats {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .avis-stats-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .avis-stats-label {
            opacity: 0.9;
        }

        /* Modal pour laisser un avis */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        .modal-title {
            font-size: 1.5em;
            color: #2c3e50;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: #2c3e50;
        }

        .rating-input {
            text-align: center;
            margin: 20px 0;
        }

        .rating-input label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
        }

        .stars-input {
            display: flex;
            justify-content: center;
            gap: 10px;
            font-size: 2.5em;
            cursor: pointer;
        }

        .stars-input .star {
            transition: all 0.2s;
        }

        .stars-input .star:hover,
        .stars-input .star.hover {
            transform: scale(1.2);
        }

        .comment-input {
            margin: 20px 0;
        }

        .comment-input label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
        }

        .comment-input textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .comment-input textarea:focus {
            outline: none;
            border-color: #f39c12;
        }

        .char-counter {
            text-align: right;
            font-size: 0.85em;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit-avis {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-submit-avis:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
        }

        .btn-submit-avis:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-cancel-avis {
            background: #ecf0f1;
            color: #2c3e50;
        }

        .btn-cancel-avis:hover {
            background: #bdc3c7;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .modal-content {
                padding: 20px;
            }

            .stars-input {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div>
                <h1>👋 Bonjour <?= htmlspecialchars($_SESSION['pseudo'] ?? 'Utilisateur') ?></h1>
                <p>Gérez vos trajets et votre profil EcoRide</p>
            </div>
            <div class="dashboard-nav">
                <a href="../index.php">← Accueil</a>
                <a href="../creer-trajet.php" class="create-trip-btn">🚗 Créer un trajet</a>
                <?php if (($_SESSION['role'] ?? '') === 'employe'): ?>
                    <a href="../employee/dashboard.php" style="background: #3498db; padding: 10px 15px; border-radius: 5px;">👔 Dashboard Employé</a>
                <?php endif; ?>
                <?php if (($_SESSION['role'] ?? '') === 'administrateur'): ?>
                    <a href="../admin/dashboard.php" style="background: #e74c3c; padding: 10px 15px; border-radius: 5px;">🛠️ Admin</a>
                <?php endif; ?>
                <a href="../logout.php">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h3>Navigation</h3>
            <ul class="sidebar-menu">
                <li><a href="?section=overview" class="<?= $active_section === 'overview' ? 'active' : '' ?>">📊 Vue d'ensemble</a></li>
                <li><a href="?section=my-trips" class="<?= $active_section === 'my-trips' ? 'active' : '' ?>">🚗 Mes trajets</a></li>
                <li><a href="?section=my-bookings" class="<?= $active_section === 'my-bookings' ? 'active' : '' ?>">🎫 Mes réservations</a></li>
                <li><a href="?section=history" class="<?= $active_section === 'history' ? 'active' : '' ?>">📋 Historique complet</a></li>
                <li><a href="?section=avis" class="<?= $active_section === 'avis' ? 'active' : '' ?>">⭐ Mes avis</a></li>
                <li><a href="?section=vehicles" class="<?= $active_section === 'vehicles' ? 'active' : '' ?>">🚙 Mes véhicules</a></li>
                <li><a href="?section=profile" class="<?= $active_section === 'profile' ? 'active' : '' ?>">👤 Mon profil</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Vue d'ensemble -->
            <div class="section <?= $active_section === 'overview' ? 'active' : '' ?>">
                <h2>📊 Vue d'ensemble</h2>

                <div class="stats-grid">
                    <div class="stat-card credits">
                        <div class="stat-number"><?= $stats['credits'] ?></div>
                        <div class="stat-label">Crédits disponibles</div>
                    </div>
                    <div class="stat-card trips">
                        <div class="stat-number"><?= $stats['trips_created'] ?></div>
                        <div class="stat-label">Trajets créés</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['trips_taken'] ?></div>
                        <div class="stat-label">Trajets effectués</div>
                    </div>
                    <div class="stat-card vehicles">
                        <div class="stat-number"><?= $stats['vehicles'] ?></div>
                        <div class="stat-label">Véhicules enregistrés</div>
                    </div>
                </div>

                <div style="text-align: center; margin: 40px 0;">
                    <a href="../creer-trajet.php" class="btn btn-primary" style="font-size: 18px; padding: 15px 30px; border-radius: 25px;">
                        🚗 Créer un nouveau trajet
                    </a>
                </div>
            </div>

            <!-- Mes trajets -->
            <div class="section <?= $active_section === 'my-trips' ? 'active' : '' ?>">
                <!-- DEBUG: Code mis à jour le 29/09/2025 à 13:50 -->
                <h2>🚗 Mes trajets (en tant que conducteur)</h2>

                <?php if (empty($my_trips)): ?>
                    <div class="empty-state">
                        <h3>Aucun trajet créé</h3>
                        <p>Vous n'avez pas encore publié de trajets.</p>
                        <a href="../creer-trajet.php" class="btn btn-primary">Créer mon premier trajet</a>
                    </div>
                <?php else: ?>
                    <div class="trips-list">
                        <?php foreach ($my_trips as $trip): ?>
                            <div class="trip-card">
                                <div class="trip-route">
                                    <?= htmlspecialchars($trip['ville_depart'] ?? '') ?> → <?= htmlspecialchars($trip['ville_arrivee'] ?? '') ?>
                                </div>
                                <div class="trip-details">
                                    <div>📅 <?= date('d/m/Y à H:i', strtotime($trip['date_depart'])) ?></div>
                                    <div>💰 <?= number_format($trip['prix_par_place'] ?? $trip['prix'] ?? 0, 2) ?> crédits/place</div>
                                    <div>👥 <?= $trip['places_disponibles'] ?> places</div>
                                    <div>🚗 <?= htmlspecialchars(($trip['marque'] ?? '') . ' ' . ($trip['modele'] ?? '')) ?></div>
                                    <div>📊 <?= ucfirst($trip['statut']) ?></div>
                                </div>

                                <div class="trip-actions" style="margin-top: 15px; display: flex; gap: 10px; justify-content: flex-end;">
                                    <a href="../trajet-detail.php?id=<?= $trip['trip_id'] ?>" class="btn btn-sm btn-info">👁️ Détails</a>
                                    <?php if ($trip['statut'] === 'planifie'): ?>
                                        <button class="btn btn-sm btn-warning edit-trip" data-trip-id="<?= $trip['trip_id'] ?>">✏️ Modifier</button>
                                        <button class="btn btn-sm btn-danger delete-trip" data-trip-id="<?= $trip['trip_id'] ?>">🗑️ Supprimer</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mes réservations -->
            <div class="section <?= $active_section === 'my-bookings' ? 'active' : '' ?>">
                <h2>🎫 Mes réservations (en tant que passager)</h2>

                <?php if (empty($my_bookings)): ?>
                    <div class="empty-state">
                        <h3>Aucune réservation</h3>
                        <p>Vous n'avez pas encore réservé de trajets.</p>
                        <a href="../trajets.php" class="btn btn-primary">Rechercher des trajets</a>
                    </div>
                <?php else: ?>
                    <div class="trips-list">
                        <?php foreach ($my_bookings as $booking): ?>
                            <div class="trip-card">
                                <div class="trip-route">
                                    <?= htmlspecialchars($booking['ville_depart'] ?? '') ?> → <?= htmlspecialchars($booking['ville_arrivee'] ?? '') ?>
                                </div>
                                <div class="trip-details">
                                    <div>📅 <?= date('d/m/Y à H:i', strtotime($booking['date_depart'])) ?></div>
                                    <div>👨‍✈️ Conducteur: <?= htmlspecialchars($booking['conducteur'] ?? '') ?></div>
                                    <div>🎫 <?= $booking['places_reservees'] ?? $booking['nombre_places'] ?? 0 ?> place(s)</div>
                                    <div>💳 <?= $booking['credit_utilise'] ?> crédits</div>
                                    <div>📊 <?= ucfirst($booking['statut']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mes avis -->
            <div class="section <?= $active_section === 'avis' ? 'active' : '' ?>">
                <h2>⭐ Mes avis</h2>

                <!-- Avis reçus -->
                <div id="avisReceived" style="margin-bottom: 40px;">
                    <h3>Avis que j'ai reçus</h3>
                    <div id="avisReceivedList" class="avis-list">
                        <div class="loading">Chargement des avis...</div>
                    </div>
                </div>

                <!-- Trajets à évaluer -->
                <div id="avisToGive">
                    <h3>Trajets à évaluer</h3>
                    <div id="avisToGiveList" class="trips-list">
                        <div class="loading">Chargement des trajets...</div>
                    </div>
                </div>
            </div>

            <!-- Mes véhicules -->
            <div class="section <?= $active_section === 'vehicles' ? 'active' : '' ?>">
                <h2>🚙 Mes véhicules</h2>

                <?php if (!empty($vehicles)): ?>
                    <div class="vehicles-grid">
                        <?php foreach ($vehicles as $vehicle): ?>
                            <div class="vehicle-card" data-vehicle-id="<?= $vehicle['voiture_id'] ?? $vehicle['vehicule_id'] ?? '' ?>">
                                <div class="vehicle-header">
                                    <div class="vehicle-title">
                                        <strong><?= htmlspecialchars(($vehicle['marque'] ?? '') . ' ' . ($vehicle['modele'] ?? '')) ?></strong>
                                        <span class="vehicle-registration"><?= htmlspecialchars($vehicle['immatriculation'] ?? '') ?></span>
                                    </div>
                                    <div class="vehicle-actions">
                                        <button class="btn-icon edit-vehicle" title="Modifier">✏️</button>
                                        <button class="btn-icon delete-vehicle" title="Supprimer">🗑️</button>
                                    </div>
                                </div>
                                <div class="vehicle-details">
                                    <div class="detail-item">
                                        <span class="label">Couleur:</span>
                                        <span class="value"><?= htmlspecialchars($vehicle['couleur'] ?: 'Non spécifiée') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Places:</span>
                                        <span class="value"><?= $vehicle['places'] ?> places</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Énergie:</span>
                                        <span class="value energy-<?= $vehicle['energie'] ?? $vehicle['type_carburant'] ?? '' ?>"><?= ucfirst($vehicle['energie'] ?? $vehicle['type_carburant'] ?? '') ?></span>
                                    </div>
                                    <?php if (!empty($vehicle['date_premiere_immatriculation'])): ?>
                                    <div class="detail-item">
                                        <span class="label">1ère immat:</span>
                                        <span class="value"><?= date('d/m/Y', strtotime($vehicle['date_premiere_immatriculation'])) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🚗</div>
                        <h3>Aucun véhicule enregistré</h3>
                        <p>Ajoutez votre premier véhicule pour commencer à proposer des trajets en tant que conducteur.</p>
                    </div>
                <?php endif; ?>

                <div class="add-vehicle-form">
                    <h3>Ajouter un véhicule</h3>
                    <form id="addVehicleForm" method="POST" action="../api/add-vehicle.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Marque *</label>
                                <input type="text" name="marque" required placeholder="Ex: Renault">
                            </div>
                            <div class="form-group">
                                <label>Modèle *</label>
                                <input type="text" name="modele" required placeholder="Ex: Clio">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Immatriculation *</label>
                                <input type="text" name="immatriculation" required placeholder="Ex: AB-123-CD">
                            </div>
                            <div class="form-group">
                                <label>Couleur</label>
                                <input type="text" name="couleur" placeholder="Ex: Rouge">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre de places *</label>
                                <select name="places" required>
                                    <option value="">Choisir...</option>
                                    <option value="2">2 places</option>
                                    <option value="4">4 places</option>
                                    <option value="5">5 places</option>
                                    <option value="7">7 places</option>
                                    <option value="9">9 places</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Type d'énergie *</label>
                                <select name="energie" required>
                                    <option value="">Choisir...</option>
                                    <option value="electrique">🔋 Électrique</option>
                                    <option value="hybride">⚡ Hybride</option>
                                    <option value="essence">⛽ Essence</option>
                                    <option value="diesel">🛢️ Diesel</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter ce véhicule</button>
                    </form>
                </div>
            </div>

            <!-- Historique complet -->
            <div class="section <?= $active_section === 'history' ? 'active' : '' ?>">
                <h2>📋 Historique complet des trajets</h2>

                <!-- Filtres -->
                <div class="history-filters">
                    <div class="filter-group">
                        <label>Filtrer par :</label>
                        <select id="statusFilter" class="form-control">
                            <option value="all">Tous les statuts</option>
                            <option value="planifie">Planifiés</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Terminés</option>
                            <option value="annule">Annulés</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Type :</label>
                        <select id="typeFilter" class="form-control">
                            <option value="all">Tous</option>
                            <option value="conducteur">En tant que conducteur</option>
                            <option value="passager">En tant que passager</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Période :</label>
                        <select id="periodFilter" class="form-control">
                            <option value="all">Toutes les périodes</option>
                            <option value="future">À venir</option>
                            <option value="past">Passés</option>
                            <option value="today">Aujourd'hui</option>
                        </select>
                    </div>
                </div>

                <!-- Historique -->
                <div class="history-container">
                    <?php
                    // Combiner et trier tous les trajets par date
                    $all_trips = [];

                    // Ajouter les trajets en tant que conducteur
                    foreach ($history_trips as $trip) {
                        $trip['role'] = 'conducteur';
                        $trip['trip_id'] = $trip['covoiturage_id'];
                        $trip['date_sort'] = strtotime($trip['date_depart']);
                        $all_trips[] = $trip;
                    }

                    // Ajouter les trajets en tant que passager
                    foreach ($history_bookings as $booking) {
                        $booking['role'] = 'passager';
                        $booking['trip_id'] = $booking['covoiturage_id'];
                        $booking['date_sort'] = strtotime($booking['date_depart']);
                        $booking['statut'] = $booking['statut']; // statut de participation
                        $booking['trip_status'] = $booking['trip_status']; // statut du trajet
                        $all_trips[] = $booking;
                    }

                    // Trier par date décroissante
                    usort($all_trips, function($a, $b) {
                        return $b['date_sort'] - $a['date_sort'];
                    });
                    ?>

                    <?php if (empty($all_trips)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">🗓️</div>
                            <h3>Aucun historique</h3>
                            <p>Vous n'avez pas encore de trajets dans votre historique.</p>
                            <a href="../trajets.php" class="btn btn-primary">Rechercher des trajets</a>
                        </div>
                    <?php else: ?>
                        <div class="history-list">
                            <?php foreach ($all_trips as $trip): ?>
                                <?php
                                $is_future = $trip['date_sort'] > time();
                                $is_conductor = $trip['role'] === 'conducteur';
                                $status_class = '';
                                $can_cancel = false;

                                if ($is_conductor) {
                                    $status = $trip['statut'];
                                    $can_cancel = $is_future && in_array($status, ['planifie']);
                                } else {
                                    $status = $trip['statut']; // statut de participation
                                    $can_cancel = $is_future && in_array($status, ['reserve', 'confirme']);
                                }

                                switch ($status) {
                                    case 'planifie':
                                    case 'reserve':
                                        $status_class = 'status-planned';
                                        break;
                                    case 'en_cours':
                                    case 'confirme':
                                        $status_class = 'status-active';
                                        break;
                                    case 'termine':
                                        $status_class = 'status-completed';
                                        break;
                                    case 'annule':
                                        $status_class = 'status-cancelled';
                                        break;
                                }
                                ?>

                                <div class="history-item"
                                     data-status="<?= $status ?>"
                                     data-type="<?= $trip['role'] ?>"
                                     data-period="<?= $is_future ? 'future' : 'past' ?>"
                                     data-trip-id="<?= $trip['trip_id'] ?>"
                                     data-role="<?= $trip['role'] ?>">

                                    <div class="history-header">
                                        <div class="history-route">
                                            <strong><?= htmlspecialchars($trip['ville_depart'] ?? '') ?> → <?= htmlspecialchars($trip['ville_arrivee'] ?? '') ?></strong>
                                        </div>
                                        <div class="history-badges">
                                            <span class="role-badge <?= $is_conductor ? 'role-conductor' : 'role-passenger' ?>">
                                                <?= $is_conductor ? '🚗 Conducteur' : '🎫 Passager' ?>
                                            </span>
                                            <span class="status-badge <?= $status_class ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="history-details">
                                        <div class="history-info">
                                            <div>📅 <?= date('d/m/Y à H:i', strtotime($trip['date_depart'])) ?></div>
                                            <div>💰 <?= number_format($trip['prix_par_place'] ?? $trip['prix'] ?? 0, 2) ?> crédits/place</div>

                                            <?php if ($is_conductor): ?>
                                                <div>👥 <?= $trip['participants'] ?> participant(s)</div>
                                                <div>🚗 <?= htmlspecialchars(($trip['marque'] ?? '') . ' ' . ($trip['modele'] ?? '')) ?></div>
                                            <?php else: ?>
                                                <div>👨‍✈️ <?= htmlspecialchars($trip['conducteur'] ?? '') ?></div>
                                                <div>🎫 <?= $trip['places_reservees'] ?? $trip['nombre_places'] ?? 0 ?> place(s)</div>
                                                <div>💳 <?= $trip['credit_utilise'] ?? 0 ?> crédits</div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="history-actions">
                                            <a href="../trajet-detail.php?id=<?= $trip['trip_id'] ?>" class="btn btn-sm btn-info">👁️ Détails</a>

                                            <?php if ($can_cancel): ?>
                                                <button class="btn btn-sm btn-danger cancel-trip"
                                                        data-trip-id="<?= $trip['trip_id'] ?>"
                                                        data-role="<?= $trip['role'] ?>">
                                                    🚫 Annuler
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($is_conductor): ?>
                                                <?php if (in_array($status, ['planifie', 'en_attente', 'disponible']) && $is_future): ?>
                                                    <button class="btn btn-sm btn-success start-trip"
                                                            data-trip-id="<?= $trip['trip_id'] ?>">
                                                        🚀 Démarrer
                                                    </button>
                                                <?php elseif ($status === 'en_cours'): ?>
                                                    <button class="btn btn-sm btn-warning finish-trip"
                                                            data-trip-id="<?= $trip['trip_id'] ?>">
                                                        🏁 Terminer
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if ($status === 'termine' && !$is_conductor): ?>
                                                <button class="btn btn-sm btn-warning rate-trip"
                                                        data-trip-id="<?= $trip['trip_id'] ?>">
                                                    ⭐ Noter
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mon profil -->
            <div class="section <?= $active_section === 'profile' ? 'active' : '' ?>">
                <h2>👤 Mon profil</h2>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pseudo</label>
                            <input type="text" value="<?= htmlspecialchars($user_data['pseudo'] ?? '') ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Crédits</label>
                            <input type="text" value="<?= $user_data['credit'] ?? $user_data['credits'] ?? 0 ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Membre depuis</label>
                            <input type="text" value="<?= date('d/m/Y', strtotime($user_data['created_at'] ?? $user_data['date_inscription'] ?? 'now')) ?>" readonly>
                        </div>
                    </div>
                    <p><em>💡 La modification du profil sera disponible dans une prochaine version.</em></p>
                </div>
            </div>
        </div>
    </div>

    </div>

    <!-- Footer -->
    <footer style="background: #2c3e50; color: white; padding: 40px 0; margin-top: 50px;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 30px;">
                <div>
                    <h4 style="color: #2ECC71; margin-bottom: 15px;">🚗🌱 EcoRide</h4>
                    <p style="color: #bdc3c7; line-height: 1.6;">La plateforme de covoiturage écologique qui révolutionne vos déplacements tout en préservant l'environnement.</p>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Navigation</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><a href="../index.php" style="color: #bdc3c7; text-decoration: none;">Accueil</a></li>
                        <li style="margin-bottom: 8px;"><a href="../trajets.php" style="color: #bdc3c7; text-decoration: none;">Rechercher des trajets</a></li>
                        <li style="margin-bottom: 8px;"><a href="../creer-trajet.php" style="color: #bdc3c7; text-decoration: none;">Créer un trajet</a></li>
                        <li style="margin-bottom: 8px;"><a href="../contact.php" style="color: #bdc3c7; text-decoration: none;">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Mon compte</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><a href="?section=overview" style="color: #bdc3c7; text-decoration: none;">Vue d'ensemble</a></li>
                        <li style="margin-bottom: 8px;"><a href="?section=my-trips" style="color: #bdc3c7; text-decoration: none;">Mes trajets</a></li>
                        <li style="margin-bottom: 8px;"><a href="?section=vehicles" style="color: #bdc3c7; text-decoration: none;">Mes véhicules</a></li>
                        <li style="margin-bottom: 8px;"><a href="?section=profile" style="color: #bdc3c7; text-decoration: none;">Mon profil</a></li>
                    </ul>
                </div>

                <div>
                    <h4 style="color: white; margin-bottom: 15px;">Support</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><a href="../comment-ca-marche.php" style="color: #bdc3c7; text-decoration: none;">Comment ça marche</a></li>
                        <li style="margin-bottom: 8px;"><a href="mailto:contact@ecoride.fr" style="color: #bdc3c7; text-decoration: none;">contact@ecoride.fr</a></li>
                        <li style="margin-bottom: 8px;"><span style="color: #bdc3c7;">Mentions légales</span></li>
                        <li style="margin-bottom: 8px;"><span style="color: #bdc3c7;">Politique de confidentialité</span></li>
                    </ul>
                </div>
            </div>

            <div style="border-top: 1px solid #34495e; padding-top: 20px; text-align: center;">
                <p style="color: #bdc3c7; margin: 0;">
                    © 2025 EcoRide - Plateforme de covoiturage écologique |
                    Développé pour l'évaluation RNCP Développeur Web et Web Mobile
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Variables globales
        const csrfToken = '<?php echo generateCSRFToken(); ?>';
        let editingVehicle = null;

        // Gestion du formulaire d'ajout/modification de véhicule
        document.getElementById('addVehicleForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const isEditing = editingVehicle !== null;

            if (isEditing) {
                formData.append('vehicle_id', editingVehicle.vehicle_id);
            }

            const apiUrl = isEditing ? '../api/edit-vehicle.php' : '../api/add-vehicle.php';
            const actionText = isEditing ? 'modification' : 'ajout';

            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert(`Erreur lors de l'${actionText} du véhicule`);
            });
        });

        // Gestion des boutons modifier
        document.querySelectorAll('.edit-vehicle').forEach(button => {
            button.addEventListener('click', function() {
                const vehicleCard = this.closest('.vehicle-card');
                const vehicleId = vehicleCard.dataset.vehicleId;

                // Récupérer les données du véhicule
                const title = vehicleCard.querySelector('.vehicle-title strong').textContent.trim();
                const [marque, ...modeleArray] = title.split(' ');
                const modele = modeleArray.join(' ');
                const registration = vehicleCard.querySelector('.vehicle-registration').textContent.trim();

                // Récupérer les détails
                const details = {};
                vehicleCard.querySelectorAll('.detail-item').forEach(item => {
                    const label = item.querySelector('.label').textContent.replace(':', '').trim();
                    const value = item.querySelector('.value').textContent.trim();

                    if (label === 'Couleur') details.couleur = value === 'Non spécifiée' ? '' : value;
                    if (label === 'Places') details.places = value.replace(' places', '');
                    if (label === 'Énergie') details.energie = value.toLowerCase();
                });

                // Remplir le formulaire avec les données existantes
                const form = document.getElementById('addVehicleForm');
                form.querySelector('[name="marque"]').value = marque;
                form.querySelector('[name="modele"]').value = modele;
                form.querySelector('[name="immatriculation"]').value = registration;
                form.querySelector('[name="couleur"]').value = details.couleur || '';
                form.querySelector('[name="places"]').value = details.places;
                form.querySelector('[name="energie"]').value = details.energie;

                // Changer le titre et le bouton
                const formTitle = form.parentElement.querySelector('h3');
                const submitButton = form.querySelector('button[type="submit"]');

                formTitle.textContent = 'Modifier le véhicule';
                submitButton.textContent = 'Enregistrer les modifications';
                submitButton.style.background = '#f39c12';

                // Mémoriser le véhicule en cours de modification
                editingVehicle = { vehicle_id: vehicleId };

                // Ajouter un bouton d'annulation
                if (!form.querySelector('.btn-cancel')) {
                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = 'btn btn-cancel';
                    cancelButton.textContent = 'Annuler';
                    cancelButton.style.cssText = 'background: #95a5a6; color: white; margin-left: 10px;';

                    cancelButton.addEventListener('click', resetForm);
                    submitButton.parentNode.insertBefore(cancelButton, submitButton.nextSibling);
                }

                // Faire défiler vers le formulaire
                form.scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Gestion des boutons supprimer
        document.querySelectorAll('.delete-vehicle').forEach(button => {
            button.addEventListener('click', function() {
                const vehicleCard = this.closest('.vehicle-card');
                const vehicleId = vehicleCard.dataset.vehicleId;
                const title = vehicleCard.querySelector('.vehicle-title strong').textContent.trim();

                if (!confirm(`Êtes-vous sûr de vouloir supprimer le véhicule "${title}" ?\n\nCette action est irréversible.`)) {
                    return;
                }

                const formData = new FormData();
                formData.append('vehicle_id', vehicleId);
                formData.append('csrf_token', csrfToken);

                fetch('../api/delete-vehicle.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression du véhicule');
                });
            });
        });

        // Fonction pour réinitialiser le formulaire
        function resetForm() {
            const form = document.getElementById('addVehicleForm');
            form.reset();

            // Restaurer le titre et le bouton
            const formTitle = form.parentElement.querySelector('h3');
            const submitButton = form.querySelector('button[type="submit"]');

            formTitle.textContent = 'Ajouter un véhicule';
            submitButton.textContent = 'Ajouter ce véhicule';
            submitButton.style.background = '';

            // Supprimer le bouton d'annulation
            const cancelButton = form.querySelector('.btn-cancel');
            if (cancelButton) {
                cancelButton.remove();
            }

            // Réinitialiser la variable d'édition
            editingVehicle = null;
        }

        // ========= HISTORIQUE DES TRAJETS =========

        // Filtrage de l'historique
        function setupHistoryFilters() {
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            const periodFilter = document.getElementById('periodFilter');

            if (!statusFilter) return; // Si pas sur la page historique

            function filterHistory() {
                const statusValue = statusFilter.value;
                const typeValue = typeFilter.value;
                const periodValue = periodFilter.value;

                const historyItems = document.querySelectorAll('.history-item');
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                historyItems.forEach(item => {
                    let showItem = true;

                    // Filtre par statut
                    if (statusValue !== 'all') {
                        const itemStatus = item.dataset.status;
                        if (itemStatus !== statusValue) {
                            showItem = false;
                        }
                    }

                    // Filtre par type
                    if (typeValue !== 'all') {
                        const itemType = item.dataset.type;
                        if (itemType !== typeValue) {
                            showItem = false;
                        }
                    }

                    // Filtre par période
                    if (periodValue !== 'all') {
                        const itemPeriod = item.dataset.period;
                        if (periodValue === 'today') {
                            // Logique spéciale pour aujourd'hui
                            showItem = showItem && (itemPeriod === 'today');
                        } else if (itemPeriod !== periodValue) {
                            showItem = false;
                        }
                    }

                    item.style.display = showItem ? 'block' : 'none';
                });

                // Afficher message si aucun résultat
                const visibleItems = document.querySelectorAll('.history-item[style="display: block"], .history-item:not([style*="display: none"])').length;
                let noResultsMsg = document.querySelector('.no-results-message');

                if (visibleItems === 0 && historyItems.length > 0) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-results-message';
                        noResultsMsg.style.cssText = 'text-align: center; padding: 40px; color: #7f8c8d;';
                        noResultsMsg.innerHTML = '<h3>Aucun trajet trouvé</h3><p>Aucun trajet ne correspond aux filtres sélectionnés.</p>';
                        document.querySelector('.history-list').appendChild(noResultsMsg);
                    }
                    noResultsMsg.style.display = 'block';
                } else if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }

            statusFilter.addEventListener('change', filterHistory);
            typeFilter.addEventListener('change', filterHistory);
            periodFilter.addEventListener('change', filterHistory);
        }

        // Annulation de trajets
        function setupCancelTrips() {
            document.querySelectorAll('.cancel-trip').forEach(button => {
                button.addEventListener('click', function() {
                    const tripId = this.dataset.tripId;
                    const role = this.dataset.role;

                    const confirmMessage = role === 'conducteur'
                        ? 'Êtes-vous sûr de vouloir annuler ce trajet ?\n\nTous les passagers seront remboursés automatiquement.'
                        : 'Êtes-vous sûr de vouloir annuler votre réservation ?\n\nVos crédits vous seront remboursés.';

                    if (!confirm(confirmMessage)) {
                        return;
                    }

                    // Désactiver le bouton pendant la requête
                    this.disabled = true;
                    this.textContent = '⏳ Annulation...';

                    const formData = new FormData();
                    formData.append('trip_id', tripId);
                    formData.append('role', role);
                    formData.append('csrf_token', csrfToken);

                    const apiUrl = role === 'conducteur' ? '../api/cancel-trip.php' : '../api/cancel-booking.php';

                    fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ ' + data.message);
                            location.reload(); // Recharger pour voir les changements
                        } else {
                            alert('❌ ' + data.message);
                            this.disabled = false;
                            this.textContent = '🚫 Annuler';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Erreur lors de l\'annulation. Veuillez réessayer.');
                        this.disabled = false;
                        this.textContent = '🚫 Annuler';
                    });
                });
            });
        }

        // Gestion démarrer/terminer trajets (US11)
        function setupTripManagement() {
            // Boutons démarrer trajet
            document.querySelectorAll('.start-trip').forEach(button => {
                button.addEventListener('click', function() {
                    const tripId = this.dataset.tripId;

                    if (!confirm('Êtes-vous sûr de vouloir démarrer ce trajet maintenant ?\n\nVos passagers seront notifiés automatiquement.')) {
                        return;
                    }

                    // Désactiver le bouton pendant la requête
                    this.disabled = true;
                    this.textContent = '⏳ Démarrage...';

                    const formData = new FormData();
                    formData.append('trip_id', tripId);
                    formData.append('action', 'start');
                    formData.append('csrf_token', csrfToken);

                    fetch('../api/manage-trip-status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ ' + data.message);
                            location.reload(); // Recharger pour voir les changements
                        } else {
                            alert('❌ ' + data.message);
                            this.disabled = false;
                            this.textContent = '🚀 Démarrer';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Erreur lors du démarrage. Veuillez réessayer.');
                        this.disabled = false;
                        this.textContent = '🚀 Démarrer';
                    });
                });
            });

            // Boutons terminer trajet
            document.querySelectorAll('.finish-trip').forEach(button => {
                button.addEventListener('click', function() {
                    const tripId = this.dataset.tripId;

                    if (!confirm('Êtes-vous sûr d\'avoir terminé ce trajet ?\n\nVos passagers recevront une demande d\'évaluation et vos crédits seront crédités après leur validation.')) {
                        return;
                    }

                    // Désactiver le bouton pendant la requête
                    this.disabled = true;
                    this.textContent = '⏳ Finalisation...';

                    const formData = new FormData();
                    formData.append('trip_id', tripId);
                    formData.append('action', 'finish');
                    formData.append('csrf_token', csrfToken);

                    fetch('../api/manage-trip-status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ ' + data.message);
                            location.reload(); // Recharger pour voir les changements
                        } else {
                            alert('❌ ' + data.message);
                            this.disabled = false;
                            this.textContent = '🏁 Terminer';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Erreur lors de la finalisation. Veuillez réessayer.');
                        this.disabled = false;
                        this.textContent = '🏁 Terminer';
                    });
                });
            });
        }

        // Gestion des boutons Modifier et Supprimer de la section "Mes trajets"
        function setupTripActions() {
            // Boutons de suppression
            document.querySelectorAll('.delete-trip').forEach(button => {
                button.addEventListener('click', function() {
                    const tripId = this.dataset.tripId;

                    if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement ce trajet ?\n\nTous les passagers ayant réservé seront remboursés automatiquement.')) {
                        return;
                    }

                    // Désactiver le bouton pendant la requête
                    this.disabled = true;
                    this.textContent = '⏳ Suppression...';

                    const formData = new FormData();
                    formData.append('trip_id', tripId);

                    fetch('../api/cancel-trip.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ ' + data.message);
                            location.reload(); // Recharger pour voir les changements
                        } else {
                            alert('❌ ' + data.message);
                            this.disabled = false;
                            this.textContent = '🗑️ Supprimer';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Erreur lors de la suppression. Veuillez réessayer.');
                        this.disabled = false;
                        this.textContent = '🗑️ Supprimer';
                    });
                });
            });

            // Boutons de modification (redirection vers page d'édition)
            document.querySelectorAll('.edit-trip').forEach(button => {
                button.addEventListener('click', function() {
                    const tripId = this.dataset.tripId;
                    // Rediriger vers une page de modification (à créer si nécessaire)
                    window.location.href = '../modifier-trajet.php?id=' + tripId;
                });
            });
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            setupHistoryFilters();
            setupCancelTrips();
            setupTripManagement();
            setupTripActions();
        });

        // Ré-initialiser lors des changements de section (si navigation AJAX)
        function reinitializeHistoryFeatures() {
            setupHistoryFilters();
            setupCancelTrips();
            setupTripManagement();
            setupTripActions();
        }

        // ========= SYSTÈME D'AVIS =========

        let currentRating = 0;
        let currentTripData = null;

        // Charger les avis reçus
        function loadReceivedAvis() {
            const userId = <?= $user_id ?>;

            fetch(`../api/get-avis.php?user_id=${userId}&limit=50`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayReceivedAvis(data);
                    } else {
                        document.getElementById('avisReceivedList').innerHTML =
                            '<div class="empty-state"><p>Erreur lors du chargement des avis</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement avis:', error);
                    document.getElementById('avisReceivedList').innerHTML =
                        '<div class="empty-state"><p>Erreur lors du chargement des avis</p></div>';
                });
        }

        // Afficher les avis reçus
        function displayReceivedAvis(data) {
            const container = document.getElementById('avisReceivedList');

            if (data.avis.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>Vous n\'avez pas encore reçu d\'avis</p></div>';
                return;
            }

            // Afficher les statistiques
            let html = '';
            if (data.stats.total > 0) {
                html += `
                    <div class="avis-stats">
                        <div class="avis-stats-number">${data.stats.moyenne} ⭐</div>
                        <div class="avis-stats-label">Note moyenne sur ${data.stats.total} avis</div>
                    </div>
                `;
            }

            // Afficher les avis
            data.avis.forEach(avis => {
                const stars = generateStars(avis.note);
                const date = new Date(avis.date).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                html += `
                    <div class="avis-card">
                        <div class="avis-header">
                            <div>
                                <div class="avis-author">${escapeHtml(avis.evaluateur.pseudo)}</div>
                                <div class="avis-rating">${stars}</div>
                            </div>
                            <div class="avis-date">${date}</div>
                        </div>
                        <div class="avis-comment">${escapeHtml(avis.commentaire)}</div>
                        <div class="avis-trip-info">
                            📍 Trajet: ${escapeHtml(avis.trajet.depart)} → ${escapeHtml(avis.trajet.arrivee)}
                            (${new Date(avis.trajet.date).toLocaleDateString('fr-FR')})
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Charger les trajets à évaluer
        function loadTripsToRate() {
            const userId = <?= $user_id ?>;
            const isPostgreSQL = <?= $isPostgreSQL ? 'true' : 'false' ?>;

            // Récupérer les trajets terminés sans avis
            fetch(`../api/get-trips-to-rate.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTripsToRate(data.trips);
                    } else {
                        // Si l'API n'existe pas encore, on affiche un message
                        document.getElementById('avisToGiveList').innerHTML =
                            '<div class="empty-state"><p>Aucun trajet à évaluer pour le moment</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement trajets à évaluer:', error);
                    document.getElementById('avisToGiveList').innerHTML =
                        '<div class="empty-state"><p>Aucun trajet à évaluer pour le moment</p></div>';
                });
        }

        // Afficher les trajets à évaluer
        function displayTripsToRate(trips) {
            const container = document.getElementById('avisToGiveList');

            if (trips.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>Aucun trajet à évaluer pour le moment</p></div>';
                return;
            }

            let html = '<div class="trips-list">';
            trips.forEach(trip => {
                const date = new Date(trip.date_depart).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div class="trip-card">
                        <div class="trip-route">
                            ${escapeHtml(trip.ville_depart)} → ${escapeHtml(trip.ville_arrivee)}
                        </div>
                        <div class="trip-details">
                            <div>📅 ${date}</div>
                            <div>👤 ${trip.is_conductor ? 'Passager' : 'Conducteur'}: ${escapeHtml(trip.other_user_pseudo)}</div>
                            <div>💰 ${trip.prix} crédits</div>
                        </div>
                        <div style="margin-top: 15px;">
                            <button class="btn btn-primary btn-leave-avis"
                                    data-trip-id="${trip.covoiturage_id}"
                                    data-evaluate-id="${trip.other_user_id}"
                                    data-evaluate-pseudo="${escapeHtml(trip.other_user_pseudo)}"
                                    data-depart="${escapeHtml(trip.ville_depart)}"
                                    data-arrivee="${escapeHtml(trip.ville_arrivee)}">
                                ⭐ Laisser un avis
                            </button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            container.innerHTML = html;

            // Attacher les événements aux boutons "Laisser un avis"
            document.querySelectorAll('.btn-leave-avis').forEach(button => {
                button.addEventListener('click', function() {
                    const tripId = parseInt(this.dataset.tripId);
                    const evaluateId = parseInt(this.dataset.evaluateId);
                    const evaluatePseudo = this.dataset.evaluatePseudo;
                    const depart = this.dataset.depart;
                    const arrivee = this.dataset.arrivee;

                    openAvisModal(tripId, evaluateId, evaluatePseudo, depart, arrivee);
                });
            });
        }

        // Générer les étoiles pour l'affichage
        function generateStars(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                stars += `<span class="star ${i <= rating ? 'filled' : ''}">★</span>`;
            }
            return stars;
        }

        // Ouvrir le modal pour laisser un avis
        function openAvisModal(tripId, evaluateId, evaluatePseudo, depart, arrivee) {
            currentTripData = {
                tripId: tripId,
                evaluateId: evaluateId,
                evaluatePseudo: evaluatePseudo,
                depart: depart,
                arrivee: arrivee
            };

            currentRating = 0;
            document.getElementById('avisModal').classList.add('active');
            document.getElementById('avisComment').value = '';
            document.getElementById('charCount').textContent = '0';
            document.getElementById('modalTripInfo').textContent =
                `${depart} → ${arrivee} avec ${evaluatePseudo}`;

            updateStarDisplay();
            updateSubmitButton();
        }

        // Fermer le modal
        function closeAvisModal() {
            document.getElementById('avisModal').classList.remove('active');
            currentTripData = null;
            currentRating = 0;
        }

        // Gérer les clics sur les étoiles
        function handleStarClick(rating) {
            currentRating = rating;
            updateStarDisplay();
            updateSubmitButton();
        }

        // Gérer le survol des étoiles
        function handleStarHover(rating) {
            document.querySelectorAll('.stars-input .star').forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('hover');
                } else {
                    star.classList.remove('hover');
                }
            });
        }

        // Réinitialiser le survol
        function resetStarHover() {
            document.querySelectorAll('.stars-input .star').forEach(star => {
                star.classList.remove('hover');
            });
        }

        // Mettre à jour l'affichage des étoiles
        function updateStarDisplay() {
            document.querySelectorAll('.stars-input .star').forEach((star, index) => {
                if (index < currentRating) {
                    star.classList.add('filled');
                } else {
                    star.classList.remove('filled');
                }
            });
        }

        // Mettre à jour le bouton de soumission
        function updateSubmitButton() {
            const comment = document.getElementById('avisComment').value.trim();
            const submitBtn = document.getElementById('submitAvis');

            if (currentRating > 0 && comment.length >= 10) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Soumettre l'avis
        function submitAvis() {
            if (!currentTripData || currentRating === 0) {
                return;
            }

            const comment = document.getElementById('avisComment').value.trim();

            if (comment.length < 10) {
                alert('Le commentaire doit contenir au moins 10 caractères');
                return;
            }

            if (comment.length > 500) {
                alert('Le commentaire ne peut pas dépasser 500 caractères');
                return;
            }

            // Désactiver le bouton pendant la soumission
            const submitBtn = document.getElementById('submitAvis');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Publication...';

            const formData = new FormData();
            formData.append('evalue_id', currentTripData.evaluateId);
            formData.append('covoiturage_id', currentTripData.tripId);
            formData.append('note', currentRating);
            formData.append('commentaire', comment);
            formData.append('csrf_token', csrfToken);

            fetch('../api/create-avis.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    closeAvisModal();
                    // Recharger les listes
                    loadReceivedAvis();
                    loadTripsToRate();
                } else {
                    alert('❌ ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Publier l\'avis';
                }
            })
            .catch(error => {
                console.error('Erreur soumission avis:', error);
                alert('Erreur lors de la publication de l\'avis. Veuillez réessayer.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Publier l\'avis';
            });
        }

        // Escape HTML pour éviter les injections XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialiser le système d'avis quand on accède à la section
        document.addEventListener('DOMContentLoaded', function() {
            // Vérifier si on est sur la section avis
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('section') === 'avis') {
                loadReceivedAvis();
                loadTripsToRate();
            }
        });

        // Compteur de caractères pour le commentaire
        document.addEventListener('DOMContentLoaded', function() {
            const commentTextarea = document.getElementById('avisComment');
            if (commentTextarea) {
                commentTextarea.addEventListener('input', function() {
                    const count = this.value.length;
                    document.getElementById('charCount').textContent = count;
                    updateSubmitButton();
                });
            }
        });
    </script>

    <!-- Modal pour laisser un avis -->
    <div id="avisModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Laisser un avis</h3>
                <button class="modal-close" onclick="closeAvisModal()">×</button>
            </div>

            <div style="text-align: center; margin-bottom: 20px; color: #7f8c8d;">
                <p id="modalTripInfo"></p>
            </div>

            <div class="rating-input">
                <label>Votre note *</label>
                <div class="stars-input" id="starsInput">
                    <span class="star" onclick="handleStarClick(1)" onmouseover="handleStarHover(1)" onmouseout="resetStarHover()">★</span>
                    <span class="star" onclick="handleStarClick(2)" onmouseover="handleStarHover(2)" onmouseout="resetStarHover()">★</span>
                    <span class="star" onclick="handleStarClick(3)" onmouseover="handleStarHover(3)" onmouseout="resetStarHover()">★</span>
                    <span class="star" onclick="handleStarClick(4)" onmouseover="handleStarHover(4)" onmouseout="resetStarHover()">★</span>
                    <span class="star" onclick="handleStarClick(5)" onmouseover="handleStarHover(5)" onmouseout="resetStarHover()">★</span>
                </div>
            </div>

            <div class="comment-input">
                <label>Votre commentaire *</label>
                <textarea id="avisComment" maxlength="500" placeholder="Partagez votre expérience de trajet... (minimum 10 caractères)"></textarea>
                <div class="char-counter"><span id="charCount">0</span>/500</div>
            </div>

            <div class="modal-actions">
                <button class="btn-cancel-avis" onclick="closeAvisModal()">Annuler</button>
                <button class="btn-submit-avis" id="submitAvis" onclick="submitAvis()" disabled>Publier l'avis</button>
            </div>
        </div>
    </div>

</body>
</html>