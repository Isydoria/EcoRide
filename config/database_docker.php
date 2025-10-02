<?php
// config/database_docker.php
// Configuration pour Docker SANS mot de passe (comme WampServer)

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            // Configuration pour Docker (identique à WampServer)
            $host = 'mysql';  // Nom du service dans docker-compose.yml
            $dbname = 'ecoride_db';
            $username = 'root';
            $password = '';  // Pas de mot de passe, comme dans WampServer
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Message de succès (vous pouvez le retirer après)
            error_log("✅ Connexion MySQL Docker réussie !");
            
        } catch (PDOException $e) {
            die("❌ Erreur de connexion : " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}
?>