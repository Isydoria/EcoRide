<?php
session_start();
header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST DASHBOARD RENDER ===\n\n";

// Test 1: Session
echo "1. Session user_id: " . ($_SESSION['user_id'] ?? 'NON DEFINI') . "\n";

if (!isset($_SESSION['user_id'])) {
    die("STOP: Pas de user_id dans la session\n");
}

$user_id = $_SESSION['user_id'];
echo "2. user_id récupéré: $user_id\n";

// Test 2: Connexion DB
require_once 'config/init.php';
echo "3. config/init.php chargé\n";

try {
    $pdo = db();
    echo "4. Connexion DB OK\n";

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "5. Driver: $driver\n";

    $isPostgreSQL = ($driver === 'pgsql');
    echo "6. isPostgreSQL: " . ($isPostgreSQL ? 'YES' : 'NO') . "\n";

    // Test 3: Requête véhicules
    echo "\n=== TEST REQUETE VEHICULES ===\n";
    $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id' => $user_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "7. Nombre de véhicules: " . count($vehicles) . "\n";

    if (count($vehicles) > 0) {
        echo "   Premier véhicule: " . json_encode($vehicles[0]) . "\n";
    }

    // Test 4: Requête trajets
    echo "\n=== TEST REQUETE TRAJETS ===\n";
    $stmt = $pdo->prepare("
        SELECT c.*, c.covoiturage_id AS trip_id, v.marque, v.modele
        FROM covoiturage c
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        WHERE c.conducteur_id = :user_id
        ORDER BY c.date_depart DESC
        LIMIT 10
    ");
    $stmt->execute(['user_id' => $user_id]);
    $my_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "8. Nombre de trajets: " . count($my_trips) . "\n";

    if (count($my_trips) > 0) {
        echo "   Premier trajet: " . json_encode($my_trips[0]) . "\n";
    }

    echo "\n=== TEST TERMINE ===\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
