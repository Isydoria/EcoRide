<?php
// config/database.php
// Configuration de la base de données

class Database {
    private static $instance = null;
    private $pdo;
    
    // Configuration adaptative (local WampServer + Docker + Render.com)
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset = 'utf8mb4';

    private function __construct() {
        // Configuration multi-environnements
        $this->host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?? 'localhost';
        $this->dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecoride_db';
        $this->username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?? 'root';
        $this->password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?? '';

        // Fallback pour environnement local WampServer
        if (empty($this->host) || empty($this->dbname) || empty($this->username)) {
            $this->host = 'localhost';
            $this->dbname = 'ecoride_db';
            $this->username = 'root';
            $this->password = '';
        }

        // Debug pour environnements cloud (Docker/Render)
        if ($_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST')) {
            error_log("Cloud DB Config: Host=" . $this->host . ", DB=" . $this->dbname);
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getPDO() {
        return $this->pdo;
    }
}

// Fonction helper
// ✅ NOUVEAU
if (!function_exists('db')) {
    function db() {
        return Database::getInstance()->getPDO();
    }
}
?>
