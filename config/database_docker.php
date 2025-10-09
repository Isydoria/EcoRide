<?php
// config/database_docker.php
// Configuration MySQL pour environnement Docker

class Database {
    // ==========================================
    // 🐳 PARAMÈTRES DOCKER
    // ==========================================
    private $host = 'mysql';              // Nom du service dans docker-compose.yml
    private $db_name = 'ecoride_db';         // Nom de la base de données
    private $username = 'root';           // Utilisateur MySQL
    private $password = '';   // Mot de passe MySQL 
    private $port = '3306';               // Port interne Docker
    private $charset = 'utf8mb4';         // Encodage UTF-8
    
    public $conn;
    
    // ==========================================
    // 🔌 CONNEXION À LA BASE DE DONNÉES
    // ==========================================
    public function getConnection() {
        $this->conn = null;
        
        try {
            // DSN (Data Source Name) avec encodage UTF-8
            $dsn = "mysql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->db_name . 
                   ";charset=" . $this->charset;
            
            // Options PDO pour améliorer la sécurité et les performances
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,     // Exceptions pour les erreurs
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           // Tableau associatif par défaut
                PDO::ATTR_EMULATE_PREPARES   => false,                      // Vraies requêtes préparées
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"         // Force UTF-8
            ];
            
            // Création de la connexion PDO
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Log succès (en développement uniquement)
            if (getenv('DOCKER_ENV') === 'true') {
                error_log("✅ Connexion MySQL Docker réussie ! (Base: {$this->db_name})");
            }
            
        } catch(PDOException $exception) {
            // Log erreur détaillée
            error_log("❌ Erreur connexion MySQL Docker: " . $exception->getMessage());
            
            // En production, ne jamais afficher les détails de l'erreur
            // throw new Exception("Erreur de connexion à la base de données");
            
            // En développement, afficher l'erreur
            echo "Erreur de connexion : " . $exception->getMessage();
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
                $stmt = $conn->query("SELECT 1");
                
                return [
                    'status' => 'success',
                    'message' => 'Connexion MySQL Docker active',
                    'host' => $this->host,
                    'database' => $this->db_name,
                    'charset' => $this->charset
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Impossible de se connecter à MySQL'
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
    // 🔧 GETTERS (si besoin)
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
// 📝 NOTES IMPORTANTES
// ==========================================
/*
 * DIFFÉRENCES AVEC database.php (WampServer) :
 * 
 * 1. HOST : 'mysql' au lieu de 'localhost'
 *    → 'mysql' est le nom du service dans docker-compose.yml
 *    → Docker utilise son réseau interne pour la communication
 * 
 * 2. PORT : 3306 (interne Docker)
 *    → Sur votre PC c'est le port 3307 (évite conflit avec WAMP)
 *    → Mais DANS Docker, c'est toujours 3306
 * 
 * 3. CHARGEMENT AUTOMATIQUE :
 *    → init.php détecte si on est dans Docker
 *    → Charge database_docker.php au lieu de database.php
 * 
 * 4. UTF-8 FORCÉ :
 *    → charset=utf8mb4 dans le DSN
 *    → SET NAMES utf8mb4 dans les options
 *    → Résout les problèmes d'accents !
 */
?>