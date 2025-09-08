<?php
// Test de connexion à la base de données
require_once '../config/database.php';

try {
    $db = Database::getInstance()->getPDO();
    
    // Tester la connexion
    echo "<h1>✅ Connexion réussie !</h1>";
    
    // Compter les utilisateurs
    $stmt = $db->query("SELECT COUNT(*) as total FROM utilisateur");
    $count = $stmt->fetch();
    echo "<p>Nombre d'utilisateurs : " . $count['total'] . "</p>";
    
    // Afficher les utilisateurs
    echo "<h2>Liste des utilisateurs :</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Pseudo</th><th>Email</th><th>Rôle</th><th>Crédits</th></tr>";
    
    $stmt = $db->query("SELECT * FROM utilisateur ORDER BY utilisateur_id");
    $users = $stmt->fetchAll();
    
    foreach($users as $user) {
        echo "<tr>";
        echo "<td>{$user['utilisateur_id']}</td>";
        echo "<td>{$user['pseudo']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['credit']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Afficher les covoiturages
    echo "<h2>Covoiturages disponibles :</h2>";
    $stmt = $db->query("
        SELECT c.*, u.pseudo as conducteur, v.marque, v.modele, v.energie 
        FROM covoiturage c
        JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
        JOIN voiture v ON c.voiture_id = v.voiture_id
        WHERE c.statut = 'planifie'
    ");
    $rides = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Trajet</th><th>Conducteur</th><th>Véhicule</th><th>Date départ</th><th>Places</th><th>Prix</th></tr>";
    
    foreach($rides as $ride) {
        $ecolo = ($ride['energie'] == 'electrique') ? '🔋' : '';
        echo "<tr>";
        echo "<td>{$ride['ville_depart']} → {$ride['ville_arrivee']}</td>";
        echo "<td>{$ride['conducteur']}</td>";
        echo "<td>{$ride['marque']} {$ride['modele']} {$ecolo}</td>";
        echo "<td>{$ride['date_depart']}</td>";
        echo "<td>{$ride['places_disponibles']}</td>";
        echo "<td>{$ride['prix_par_place']}€</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ Tout fonctionne parfaitement !</h2>";
    echo "<p>Mot de passe pour tous les comptes : <strong>Test123!</strong></p>";
    
} catch (Exception $e) {
    echo "<h1>❌ Erreur :</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
