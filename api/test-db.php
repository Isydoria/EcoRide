<?php
// ========== api/test-db.php ==========
// Fichier pour tester la connexion à la base de données

header('Content-Type: application/json; charset=utf-8');

try {
    // Connexion Railway adaptative
    $host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? 'localhost';
    $dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecoride_db';
    $username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? 'root';
    $password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer le nom de la base
    $database = $pdo->query("SELECT DATABASE()")->fetchColumn();
    
    // Récupérer les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    // Compter les utilisateurs
    $userCount = $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
    
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