<?php
/**
 * Script pour vérifier les rôles des utilisateurs
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== VERIFICATION DES ROLES UTILISATEURS ===\n\n";

require_once 'config/init.php';

try {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    echo "Driver: $driver\n\n";

    // Compter les utilisateurs par rôle
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM utilisateur GROUP BY role");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== REPARTITION DES ROLES ===\n";
    foreach ($roles as $r) {
        echo "  - {$r['role']}: {$r['count']} utilisateur(s)\n";
    }

    echo "\n=== LISTE DE TOUS LES UTILISATEURS ===\n";
    $stmt = $pdo->query("SELECT utilisateur_id, pseudo, email, role, statut FROM utilisateur ORDER BY role, pseudo");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $u) {
        echo sprintf(
            "ID %d | %-20s | %-30s | %-15s | %s\n",
            $u['utilisateur_id'],
            $u['pseudo'],
            $u['email'],
            $u['role'],
            $u['statut']
        );
    }

    echo "\n=== RECHERCHE SPECIFIQUE 'EMPLOYE' ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utilisateur WHERE role = 'employe'");
    $count = $stmt->fetchColumn();
    echo "Utilisateurs avec role = 'employe' : $count\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utilisateur WHERE role = 'employee'");
    $count = $stmt->fetchColumn();
    echo "Utilisateurs avec role = 'employee' : $count\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utilisateur WHERE role LIKE '%employ%'");
    $count = $stmt->fetchColumn();
    echo "Utilisateurs avec role contenant 'employ' : $count\n";

    echo "\n=== FIN ===\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
?>
