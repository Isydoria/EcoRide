<?php
/**
 * Rate Limiter - Protection contre les attaques par force brute
 * Limite le nombre de tentatives de connexion/inscription par IP
 */

class RateLimiter {
    private $pdo;
    private $tableName = 'rate_limit';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTableIfNeeded();
    }

    /**
     * Créer la table de rate limiting si elle n'existe pas
     */
    private function createTableIfNeeded() {
        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $isPostgreSQL = ($driver === 'pgsql');

            if ($isPostgreSQL) {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    id SERIAL PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    attempts INT DEFAULT 1,
                    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    locked_until TIMESTAMP NULL
                )";
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    attempts INT DEFAULT 1,
                    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    locked_until TIMESTAMP NULL,
                    INDEX idx_ip_action (ip_address, action_type)
                )";
            }

            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Rate Limiter: Erreur création table - " . $e->getMessage());
        }
    }

    /**
     * Vérifier si l'IP est bloquée
     * @param string $ipAddress
     * @param string $actionType (login, register, etc.)
     * @param int $maxAttempts (défaut: 5)
     * @param int $lockDuration (en secondes, défaut: 900 = 15 minutes)
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int|null]
     */
    public function check($ipAddress, $actionType, $maxAttempts = 5, $lockDuration = 900) {
        $this->cleanExpiredLocks();

        $stmt = $this->pdo->prepare("
            SELECT attempts, locked_until
            FROM {$this->tableName}
            WHERE ip_address = :ip AND action_type = :action
        ");
        $stmt->execute([
            'ip' => $ipAddress,
            'action' => $actionType
        ]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            return [
                'allowed' => true,
                'remaining' => $maxAttempts,
                'retry_after' => null
            ];
        }

        // Vérifier si l'IP est actuellement bloquée
        if ($record['locked_until']) {
            $lockedUntil = strtotime($record['locked_until']);
            $now = time();

            if ($lockedUntil > $now) {
                $retryAfter = $lockedUntil - $now;
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'retry_after' => $retryAfter,
                    'message' => 'Trop de tentatives. Veuillez réessayer dans ' . ceil($retryAfter / 60) . ' minute(s).'
                ];
            } else {
                // Le blocage a expiré, réinitialiser
                $this->reset($ipAddress, $actionType);
                return [
                    'allowed' => true,
                    'remaining' => $maxAttempts,
                    'retry_after' => null
                ];
            }
        }

        // Calculer les tentatives restantes
        $remaining = $maxAttempts - $record['attempts'];

        if ($remaining <= 0) {
            // Bloquer l'IP
            $this->lock($ipAddress, $actionType, $lockDuration);
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $lockDuration,
                'message' => 'Trop de tentatives. Compte temporairement bloqué pour 15 minutes.'
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'retry_after' => null
        ];
    }

    /**
     * Enregistrer une tentative échouée
     */
    public function recordAttempt($ipAddress, $actionType) {
        $stmt = $this->pdo->prepare("
            SELECT id, attempts
            FROM {$this->tableName}
            WHERE ip_address = :ip AND action_type = :action
        ");
        $stmt->execute([
            'ip' => $ipAddress,
            'action' => $actionType
        ]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isPostgreSQL = ($driver === 'pgsql');

        if ($record) {
            // Incrémenter le compteur
            if ($isPostgreSQL) {
                $stmt = $this->pdo->prepare("
                    UPDATE {$this->tableName}
                    SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE {$this->tableName}
                    SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
            }
            $stmt->execute(['id' => $record['id']]);
        } else {
            // Créer un nouvel enregistrement
            if ($isPostgreSQL) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO {$this->tableName} (ip_address, action_type, attempts, last_attempt)
                    VALUES (:ip, :action, 1, CURRENT_TIMESTAMP)
                ");
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO {$this->tableName} (ip_address, action_type, attempts, last_attempt)
                    VALUES (:ip, :action, 1, CURRENT_TIMESTAMP)
                ");
            }
            $stmt->execute([
                'ip' => $ipAddress,
                'action' => $actionType
            ]);
        }
    }

    /**
     * Bloquer une IP
     */
    private function lock($ipAddress, $actionType, $duration) {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isPostgreSQL = ($driver === 'pgsql');

        if ($isPostgreSQL) {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->tableName}
                SET locked_until = CURRENT_TIMESTAMP + INTERVAL '{$duration} seconds'
                WHERE ip_address = :ip AND action_type = :action
            ");
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->tableName}
                SET locked_until = DATE_ADD(NOW(), INTERVAL {$duration} SECOND)
                WHERE ip_address = :ip AND action_type = :action
            ");
        }

        $stmt->execute([
            'ip' => $ipAddress,
            'action' => $actionType
        ]);
    }

    /**
     * Réinitialiser le compteur après succès
     */
    public function reset($ipAddress, $actionType) {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->tableName}
            WHERE ip_address = :ip AND action_type = :action
        ");
        $stmt->execute([
            'ip' => $ipAddress,
            'action' => $actionType
        ]);
    }

    /**
     * Nettoyer les locks expirés (plus de 24h)
     */
    private function cleanExpiredLocks() {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isPostgreSQL = ($driver === 'pgsql');

        if ($isPostgreSQL) {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->tableName}
                WHERE last_attempt < CURRENT_TIMESTAMP - INTERVAL '24 hours'
            ");
        } else {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->tableName}
                WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
        }

        $stmt->execute();
    }

    /**
     * Obtenir l'IP du client (même derrière un proxy)
     */
    public static function getClientIP() {
        $ipAddress = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Prendre la première IP si plusieurs
            $ipAddress = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }

        // Valider l'IP
        $ipAddress = filter_var(trim($ipAddress), FILTER_VALIDATE_IP);

        return $ipAddress ?: '0.0.0.0';
    }
}
?>
