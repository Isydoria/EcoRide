<?php
session_start();
require_once 'config/init.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    // Sophie Martin a l'ID 7
    $user_id = 7;

    echo "<h1>🔍 Test Réservations Sophie Martin (ID: 7)</h1>";
    echo "<p><strong>Driver:</strong> $driver</p>";
    echo "<hr>";

    // Test 1: Vérifier les infos utilisateur
    echo "<h2>👤 Infos utilisateur</h2>";
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE utilisateur_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Pseudo</th><th>Email</th><th>Crédits</th><th>Rôle</th></tr>";
        echo "<tr>";
        echo "<td>{$user_data['utilisateur_id']}</td>";
        echo "<td>{$user_data['pseudo']}</td>";
        echo "<td>{$user_data['email']}</td>";
        echo "<td>" . ($isPostgreSQL ? $user_data['credits'] : $user_data['credit']) . "</td>";
        echo "<td>{$user_data['role']}</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>❌ Utilisateur non trouvé!</strong></p>";
    }

    // Test 2: Requête EXACTE utilisée dans dashboard.php pour PostgreSQL
    echo "<h2>🎫 Requête SQL de dashboard.php (PostgreSQL)</h2>";

    $sql = "
        SELECT p.*, c.ville_depart, c.ville_arrivee, c.date_depart, c.prix,
               (c.prix * p.nombre_places) as credit_utilise, u.pseudo as conducteur
        FROM participation p
        JOIN covoiturage c ON p.id_trajet = c.covoiturage_id
        JOIN utilisateur u ON c.id_conducteur = u.utilisateur_id
        WHERE p.id_passager = :user_id
        ORDER BY c.date_depart DESC
        LIMIT 10
    ";

    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($sql);
    echo "</pre>";

    echo "<h3>Exécution de la requête...</h3>";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Nombre de résultats:</strong> " . count($my_bookings) . "</p>";

    if (count($my_bookings) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID Participation</th><th>Trajet</th><th>Date départ</th><th>Conducteur</th><th>Places</th><th>Crédits</th><th>Statut</th></tr>";
        foreach ($my_bookings as $booking) {
            echo "<tr>";
            echo "<td>{$booking['participation_id']}</td>";
            echo "<td>{$booking['ville_depart']} → {$booking['ville_arrivee']}</td>";
            echo "<td>{$booking['date_depart']}</td>";
            echo "<td>{$booking['conducteur']}</td>";
            echo "<td>{$booking['nombre_places']}</td>";
            echo "<td>{$booking['credit_utilise']}€</td>";
            echo "<td>{$booking['statut']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>❌ Aucune réservation trouvée pour Sophie Martin!</strong></p>";
    }

    // Test 3: Vérifier directement dans la table participation
    echo "<h2>🔍 Vérification directe table participation</h2>";
    $stmt = $pdo->prepare("SELECT * FROM participation WHERE id_passager = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $direct_participations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Participations dans la table (WHERE id_passager = 7):</strong> " . count($direct_participations) . "</p>";

    if (count($direct_participations) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>id_trajet</th><th>id_passager</th><th>nombre_places</th><th>statut</th><th>date_reservation</th></tr>";
        foreach ($direct_participations as $p) {
            echo "<tr>";
            echo "<td>{$p['participation_id']}</td>";
            echo "<td>{$p['id_trajet']}</td>";
            echo "<td>{$p['id_passager']}</td>";
            echo "<td>{$p['nombre_places']}</td>";
            echo "<td>{$p['statut']}</td>";
            echo "<td>" . ($p['date_reservation'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>❌ Aucune ligne dans participation avec id_passager = 7!</strong></p>";
    }

    // Test 4: Vérifier la structure des colonnes de la table participation
    echo "<h2>📋 Structure de la table participation</h2>";
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_name = 'participation'
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Nullable</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['column_name']}</td>";
        echo "<td>{$col['data_type']}</td>";
        echo "<td>{$col['is_nullable']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erreur:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
