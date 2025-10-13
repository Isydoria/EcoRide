<?php
// config/database_render.php
// Configuration PostgreSQL pour Render.com

class Database {
    // ==========================================
    // 🎨 PARAMÈTRES RENDER.COM (PostgreSQL)
    // ==========================================
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port = '5432';
    private $charset = 'utf8';
    
    public $conn;
    
    public function __construct() {
        // Render fournit DATABASE_URL automatiquement
        // Format: postgres://user:pass@host:5432/dbname
        $database_url = getenv('DATABASE_URL');
        
        if ($database_url) {
            // Parser l'URL PostgreSQL de Render
            $db = parse_url($database_url);
            
            $this->host = $db['host'];
            $this->port = $db['port'] ?? '5432';
            $this->db_name = ltrim($db['path'], '/');
            $this->username = $db['user'];
            $this->password = $db['pass'];
        } else {
            // Valeurs par défaut (ne devrait pas arriver sur Render)
            $this->host = getenv('DB_HOST') ?: 'localhost';
            $this->db_name = getenv('DB_NAME') ?: 'ecoride';
            $this->username = getenv('DB_USER') ?: 'postgres';
            $this->password = getenv('DB_PASSWORD') ?: '';
        }
    }
    
    // ==========================================
    // 🔌 CONNEXION À LA BASE DE DONNÉES
    // ==========================================
    public function getConnection() {
        $this->conn = null;
        
        try {
            // DSN PostgreSQL avec SSL (requis par Render)
            $dsn = "pgsql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->db_name . 
                   ";sslmode=require" .  // Important pour Render !
                   ";options='--client_encoding=" . $this->charset . "'";
            
            // Options PDO
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false
            ];
            
            // Création de la connexion PDO
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Log succès (seulement en dev)
            if (getenv('APP_ENV') !== 'production') {
                error_log("✅ Connexion PostgreSQL Render réussie ! (Base: {$this->db_name})");
            }
            
        } catch(PDOException $exception) {
            // Log erreur détaillée
            error_log("❌ Erreur connexion PostgreSQL Render: " . $exception->getMessage());
            error_log("❌ DSN utilisé: pgsql:host=" . $this->host . ";dbname=" . $this->db_name);
            
            // En production, message générique
            if (getenv('APP_ENV') === 'production') {
                throw new Exception("Erreur de connexion à la base de données");
            } else {
                // En dev, afficher les détails
                echo "<div style='background: #fee; padding: 20px; margin: 20px; border: 2px solid #c00;'>";
                echo "<h3>❌ Erreur de connexion PostgreSQL</h3>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
                echo "<p><strong>Host:</strong> " . htmlspecialchars($this->host) . "</p>";
                echo "<p><strong>Database:</strong> " . htmlspecialchars($this->db_name) . "</p>";
                echo "<p><strong>User:</strong> " . htmlspecialchars($this->username) . "</p>";
                echo "</div>";
            }
        }
        
        return $this->conn;
    }
    
    // ==========================================
    // 🧪 TEST DE CONNEXION
    // ==========================================
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            
            if ($conn) {
                // Tester une requête simple
                $stmt = $conn->query("SELECT version()");
                $version = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return [
                    'status' => 'success',
                    'message' => 'Connexion PostgreSQL Render active',
                    'host' => $this->host,
                    'database' => $this->db_name,
                    'version' => $version['version'] ?? 'Unknown'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Impossible de se connecter à PostgreSQL'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    // ==========================================
    // 🔧 GETTERS
    // ==========================================
    public function getHost() {
        return $this->host;
    }
    
    public function getDatabaseName() {
        return $this->db_name;
    }
    
    public function getCharset() {
        return $this->charset;
    }
}

// ==========================================
// 📝 NOTES DE MIGRATION MySQL → PostgreSQL
// ==========================================
/*
 * PRINCIPALES DIFFÉRENCES À ADAPTER DANS VOS REQUÊTES :
 * 
 * 1. AUTO_INCREMENT → SERIAL
 *    MySQL: id INT AUTO_INCREMENT PRIMARY KEY
 *    PostgreSQL: id SERIAL PRIMARY KEY
 * 
 * 2. DATETIME → TIMESTAMP
 *    MySQL: created_at DATETIME DEFAULT CURRENT_TIMESTAMP
 *    PostgreSQL: created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * 
 * 3. ENUM → CHECK constraint
 *    MySQL: role ENUM('admin', 'user')
 *    PostgreSQL: role VARCHAR(50) CHECK (role IN ('admin', 'user'))
 * 
 * 4. TINYINT(1) → BOOLEAN
 *    MySQL: is_active TINYINT(1) DEFAULT 0
 *    PostgreSQL: is_active BOOLEAN DEFAULT FALSE
 * 
 * 5. Backticks → Aucun ou guillemets doubles
 *    MySQL: `table_name`
 *    PostgreSQL: table_name ou "table_name"
 * 
 * 6. LIMIT/OFFSET (identique)
 *    MySQL & PostgreSQL: SELECT * FROM table LIMIT 10 OFFSET 20
 * 
 * 7. Fonctions de dates
 *    MySQL: NOW(), DATE_FORMAT(date, '%d/%m/%Y')
 *    PostgreSQL: NOW(), TO_CHAR(date, 'DD/MM/YYYY')
 * 
 * 8. Concaténation
 *    MySQL: CONCAT(a, b) ou a || b
 *    PostgreSQL: a || b ou CONCAT(a, b)
 * 
 * 9. REPLACE INTO n'existe pas
 *    Utiliser INSERT ... ON CONFLICT DO UPDATE
 * 
 * 10. AUTO_INCREMENT sur UPDATE (trigger nécessaire)
 *     MySQL: ON UPDATE CURRENT_TIMESTAMP
 *     PostgreSQL: Créer un trigger (voir exemple dans le code)
 */
?>