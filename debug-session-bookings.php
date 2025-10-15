<?php
session_start();
require_once 'config/init.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("<h1>❌ Non connecté</h1><p>Vous devez être connecté pour accéder à cette page.</p><p><a href='connexion.php'>Se connecter</a></p>");
}

header('Content-Type: text/html; charset=utf-8');

$user_id = $_SESSION['user_id'];

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isPostgreSQL = ($driver === 'pgsql');

    echo "<h1>🔍 Debug Session - Mes Réservations</h1>";
    echo "<p><strong>Driver:</strong> $driver</p>";
    echo "<p><strong>Session user_id:</strong> $user_id</p>";
    echo "<hr>";

    // Infos utilisateur
    echo "<h2>👤 Utilisateur connecté</h2>";
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
    }

    // Requête EXACTE du dashboard.php
    echo "<h2>🎫 Requête SQL exacte de dashboard.php</h2>";

    if ($isPostgreSQL) {
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
    } else {
        $sql = "
            SELECT p.*, c.ville_depart, c.ville_arrivee, c.date_depart, c.prix_par_place, u.pseudo as conducteur
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            WHERE p.passager_id = :user_id
            ORDER BY c.date_depart DESC
            LIMIT 10
        ";
    }

    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($sql);
    echo "\nParamètre: user_id = $user_id";
    echo "</pre>";

    echo "<h3>📊 Résultats de la requête</h3>";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Nombre de résultats:</strong> " . count($my_bookings) . "</p>";
    echo "<p><strong>empty(\$my_bookings):</strong> " . (empty($my_bookings) ? 'TRUE (tableau vide)' : 'FALSE (contient des données)') . "</p>";

    if (count($my_bookings) > 0) {
        echo "<h3>✅ Réservations trouvées:</h3>";
        echo "<table border='1' cellpadding='5' style='width: 100%;'>";
        echo "<tr><th>ID</th><th>Trajet</th><th>Date</th><th>Conducteur</th><th>Places</th><th>Crédits</th><th>Statut</th></tr>";
        foreach ($my_bookings as $booking) {
            echo "<tr>";
            echo "<td>{$booking['participation_id']}</td>";
            echo "<td>{$booking['ville_depart']} → {$booking['ville_arrivee']}</td>";
            echo "<td>" . date('d/m/Y à H:i', strtotime($booking['date_depart'])) . "</td>";
            echo "<td>{$booking['conducteur']}</td>";
            echo "<td>{$booking['nombre_places']}</td>";
            echo "<td>{$booking['credit_utilise']}€</td>";
            echo "<td>" . ucfirst($booking['statut']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<h3>🔍 Données brutes (JSON)</h3>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
        echo json_encode($my_bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
    } else {
        echo "<h3>❌ Aucune réservation trouvée</h3>";

        // Vérifier directement dans la table
        echo "<h3>🔍 Vérification directe table participation</h3>";
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("SELECT * FROM participation WHERE id_passager = :user_id");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM participation WHERE passager_id = :user_id");
        }
        $stmt->execute(['user_id' => $user_id]);
        $direct = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<p><strong>Lignes dans participation:</strong> " . count($direct) . "</p>";

        if (count($direct) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>id_trajet</th><th>id_passager</th><th>nombre_places</th><th>statut</th></tr>";
            foreach ($direct as $d) {
                echo "<tr>";
                echo "<td>{$d['participation_id']}</td>";
                echo "<td>{$d['id_trajet']}</td>";
                echo "<td>{$d['id_passager']}</td>";
                echo "<td>{$d['nombre_places']}</td>";
                echo "<td>{$d['statut']}</td>";
                echo "</tr>";
            }
            echo "</table>";

            echo "<p style='color: orange;'><strong>⚠️ PROBLÈME DÉTECTÉ:</strong> Il y a des participations dans la table, mais le JOIN ne les trouve pas !</p>";

            // Vérifier pourquoi le JOIN échoue
            echo "<h3>🔍 Vérification des trajets correspondants</h3>";
            foreach ($direct as $d) {
                $trajet_id = $d['id_trajet'];
                $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE covoiturage_id = :id");
                $stmt->execute(['id' => $trajet_id]);
                $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($trajet) {
                    echo "<p>✅ Trajet ID $trajet_id existe: {$trajet['ville_depart']} → {$trajet['ville_arrivee']}</p>";
                } else {
                    echo "<p style='color: red;'>❌ Trajet ID $trajet_id N'EXISTE PAS dans la table covoiturage !</p>";
                }
            }
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erreur:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<hr>
<p><a href="user/dashboard.php">← Retour au dashboard</a></p>
