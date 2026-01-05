<?php
/**
 * Script de diagnostic pour vérifier les données de Sophie Martin sur Render PostgreSQL
 */

// Connexion directe PostgreSQL Render
$db_host = getenv('PGHOST') ?: 'dpg-cu1dllm8ii6s73bvj6j0-a.frankfurt-postgres.render.com';
$db_name = getenv('PGDATABASE') ?: 'ecoride_db_g15s';
$db_user = getenv('PGUSER') ?: 'ecoride_user';
$db_password = getenv('PGPASSWORD') ?: 'EtD7sX8r9RWXVmkXpAxPAXZrh8NkA4jw';
$db_port = getenv('PGPORT') ?: '5432';

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== DIAGNOSTIC SOPHIE MARTIN (Render PostgreSQL) ===\n\n";
    
    // 1. Trouver Sophie Martin
    $stmt = $pdo->prepare("SELECT utilisateur_id, pseudo, email, credit, created_at FROM utilisateur WHERE pseudo LIKE '%Sophie%'");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ AUCUN utilisateur Sophie trouvé\n";
        echo "\nUtilisateurs existants:\n";
        $stmt = $pdo->query("SELECT utilisateur_id, pseudo, email FROM utilisateur ORDER BY created_at DESC LIMIT 10");
        foreach ($stmt->fetchAll() as $u) {
            echo "  - ID {$u['utilisateur_id']}: {$u['pseudo']} ({$u['email']})\n";
        }
        exit;
    }
    
    echo "✅ Utilisateurs Sophie trouvés:\n";
    foreach ($users as $u) {
        echo "  - ID {$u['utilisateur_id']}: {$u['pseudo']} ({$u['email']}) - {$u['credit']} crédits\n";
    }
    
    $sophie = $users[0];
    $user_id = $sophie['utilisateur_id'];
    
    echo "\n=== DONNÉES POUR {$sophie['pseudo']} (ID: $user_id) ===\n\n";
    
    // 2. Véhicules
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voiture WHERE utilisateur_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $vehicle_count = $stmt->fetch()['count'];
    echo "🚗 Véhicules: $vehicle_count\n";
    
    if ($vehicle_count > 0) {
        $stmt = $pdo->prepare("SELECT voiture_id, marque, modele, immatriculation FROM voiture WHERE utilisateur_id = :uid");
        $stmt->execute(['uid' => $user_id]);
        foreach ($stmt->fetchAll() as $v) {
            echo "   - ID {$v['voiture_id']}: {$v['marque']} {$v['modele']} ({$v['immatriculation']})\n";
        }
    }
    echo "\n";
    
    // 3. Trajets créés (conducteur)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM covoiturage WHERE conducteur_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $trips_count = $stmt->fetch()['count'];
    echo "🚙 Trajets créés (conducteur): $trips_count\n";
    
    if ($trips_count > 0) {
        $stmt = $pdo->prepare("
            SELECT covoiturage_id, ville_depart, ville_arrivee, date_depart, statut, prix_par_place, places_disponibles 
            FROM covoiturage 
            WHERE conducteur_id = :uid 
            ORDER BY date_depart DESC 
            LIMIT 5
        ");
        $stmt->execute(['uid' => $user_id]);
        foreach ($stmt->fetchAll() as $t) {
            echo "   - ID {$t['covoiturage_id']}: {$t['ville_depart']} → {$t['ville_arrivee']} ";
            echo "({$t['statut']}) - {$t['date_depart']} - {$t['prix_par_place']}€/place - {$t['places_disponibles']} places\n";
        }
    }
    echo "\n";
    
    // 4. Participations (passager)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participation WHERE passager_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $bookings_count = $stmt->fetch()['count'];
    echo "🎫 Participations (passager): $bookings_count\n";
    
    if ($bookings_count > 0) {
        $stmt = $pdo->prepare("
            SELECT p.participation_id, p.nombre_places, p.statut, p.credit_utilise,
                   c.ville_depart, c.ville_arrivee, c.date_depart
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            WHERE p.passager_id = :uid
            ORDER BY c.date_depart DESC
            LIMIT 5
        ");
        $stmt->execute(['uid' => $user_id]);
        foreach ($stmt->fetchAll() as $b) {
            echo "   - ID {$b['participation_id']}: {$b['ville_depart']} → {$b['ville_arrivee']} ";
            echo "({$b['statut']}) - {$b['nombre_places']} place(s) - {$b['credit_utilise']} crédits - {$b['date_depart']}\n";
        }
    }
    echo "\n";
    
    // 5. Avis
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM avis WHERE destinataire_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $avis_count = $stmt->fetch()['count'];
    echo "⭐ Avis reçus: $avis_count\n";
    
    if ($avis_count > 0) {
        $stmt = $pdo->prepare("
            SELECT avis_id, note, statut, created_at
            FROM avis
            WHERE destinataire_id = :uid
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute(['uid' => $user_id]);
        foreach ($stmt->fetchAll() as $a) {
            echo "   - ID {$a['avis_id']}: {$a['note']}/5 étoiles ({$a['statut']}) - {$a['created_at']}\n";
        }
    }
    
    echo "\n=== FIN DU DIAGNOSTIC ===\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
