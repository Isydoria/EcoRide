<?php
require_once 'config/init.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    echo "<h1>🔍 Debug - Réservations Utilisateur</h1>";
    echo "<p><strong>Driver détecté:</strong> $driver</p>";
    echo "<hr>";

    // 1. Lister tous les utilisateurs avec leurs crédits
    echo "<h2>👥 Utilisateurs dans la base</h2>";
    $stmt = $pdo->query("SELECT utilisateur_id, pseudo, email, " .
                        ($isPostgreSQL ? "credits" : "credit") . " as credits, role " .
                        "FROM utilisateur ORDER BY utilisateur_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Pseudo</th><th>Email</th><th>Crédits</th><th>Rôle</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['utilisateur_id']}</td>";
        echo "<td>{$user['pseudo']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['credits']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 2. Lister tous les trajets disponibles
    echo "<h2>🚗 Trajets disponibles</h2>";
    if ($isPostgreSQL) {
        $stmt = $pdo->query("
            SELECT c.covoiturage_id, c.ville_depart, c.ville_arrivee, c.date_depart,
                   c.prix, c.places_disponibles, c.statut, u.pseudo as conducteur
            FROM covoiturage c
            JOIN utilisateur u ON c.id_conducteur = u.utilisateur_id
            WHERE c.statut IN ('planifie', 'en_cours')
            AND c.date_depart >= CURRENT_DATE
            ORDER BY c.date_depart
        ");
    } else {
        $stmt = $pdo->query("
            SELECT c.covoiturage_id, c.ville_depart, c.ville_arrivee, c.date_depart,
                   c.prix_par_place as prix, c.places_disponibles, c.statut, u.pseudo as conducteur
            FROM covoiturage c
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            WHERE c.statut IN ('planifie', 'en_cours')
            AND c.date_depart >= CURDATE()
            ORDER BY c.date_depart
        ");
    }
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Trajet</th><th>Date</th><th>Prix</th><th>Places</th><th>Statut</th><th>Conducteur</th></tr>";
    foreach ($trajets as $trajet) {
        echo "<tr>";
        echo "<td>{$trajet['covoiturage_id']}</td>";
        echo "<td>{$trajet['ville_depart']} → {$trajet['ville_arrivee']}</td>";
        echo "<td>{$trajet['date_depart']}</td>";
        echo "<td>{$trajet['prix']}€</td>";
        echo "<td>{$trajet['places_disponibles']}</td>";
        echo "<td>{$trajet['statut']}</td>";
        echo "<td>{$trajet['conducteur']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 3. Lister toutes les participations existantes
    echo "<h2>🎫 Participations existantes (toutes)</h2>";
    if ($isPostgreSQL) {
        $stmt = $pdo->query("
            SELECT p.participation_id, p.nombre_places, p.statut, p.date_reservation,
                   (c.prix * p.nombre_places) as credit_utilise,
                   c.ville_depart, c.ville_arrivee, c.date_depart,
                   u_passager.pseudo as passager, u_conducteur.pseudo as conducteur
            FROM participation p
            JOIN covoiturage c ON p.id_trajet = c.covoiturage_id
            JOIN utilisateur u_passager ON p.id_passager = u_passager.utilisateur_id
            JOIN utilisateur u_conducteur ON c.id_conducteur = u_conducteur.utilisateur_id
            ORDER BY p.date_reservation DESC
        ");
    } else {
        $stmt = $pdo->query("
            SELECT p.participation_id, p.nombre_places, p.statut, p.credit_utilise,
                   c.ville_depart, c.ville_arrivee, c.date_depart,
                   u_passager.pseudo as passager, u_conducteur.pseudo as conducteur
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u_passager ON p.passager_id = u_passager.utilisateur_id
            JOIN utilisateur u_conducteur ON c.conducteur_id = u_conducteur.utilisateur_id
            ORDER BY p.participation_id DESC
        ");
    }
    $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($participations) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Passager</th><th>Conducteur</th><th>Trajet</th><th>Date trajet</th><th>Places</th><th>Crédits</th><th>Statut</th><th>Date résa</th></tr>";
        foreach ($participations as $p) {
            echo "<tr>";
            echo "<td>{$p['participation_id']}</td>";
            echo "<td>{$p['passager']}</td>";
            echo "<td>{$p['conducteur']}</td>";
            echo "<td>{$p['ville_depart']} → {$p['ville_arrivee']}</td>";
            echo "<td>{$p['date_depart']}</td>";
            echo "<td>{$p['nombre_places']}</td>";
            echo "<td>{$p['credit_utilise']}€</td>";
            echo "<td>{$p['statut']}</td>";
            echo "<td>" . ($p['date_reservation'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><strong>❌ Aucune participation trouvée dans la base !</strong></p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erreur:</strong> " . $e->getMessage() . "</p>";
}
?>
