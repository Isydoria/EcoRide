<?php
// ========== api/test-db.php ==========
// Fichier pour tester la connexion à la base de données

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=ecoride_db;charset=utf8mb4',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer le nom de la base
    $database = $pdo->query("SELECT DATABASE()")->fetchColumn();
    
    // Récupérer les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    // Compter les utilisateurs
    $userCount = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'database' => $database,
        'tables' => implode(', ', $tables),
        'user_count' => $userCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>