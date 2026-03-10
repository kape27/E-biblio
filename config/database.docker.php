<?php
/**
 * Database Configuration and Connection Manager - Docker Version
 * Provides secure PDO connection with prepared statements
 * Uses environment variables for Docker deployment
 */

class DatabaseManager {
    private static $instance = null;
    private $connection;
    
    // Database configuration from environment variables
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    private const DB_CHARSET = 'utf8mb4';
    
    private function __construct() {
        // Load configuration from environment variables or use defaults
        $this->dbHost = getenv('DB_HOST') ?: 'db';
        $this->dbName = getenv('DB_NAME') ?: 'elib_database';
        $this->dbUser = getenv('DB_USER') ?: 'elib_user';
        $this->dbPass = getenv('DB_PASS') ?: 'elib_password';
        
        $this->connect();
    }
    
    public static function getInstance(): DatabaseManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect(): void {
        $dsn = "mysql:host=" . $this->dbHost . ";dbname=" . $this->dbName . ";charset=" . self::DB_CHARSET;
        
        // Vérifier que l'extension pdo_mysql est chargée
        if (!extension_loaded('pdo_mysql')) {
            throw new Exception("L'extension PDO MySQL n'est pas activée.");
        }
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        // Retry logic for Docker container startup
        $maxRetries = 10;
        $retryDelay = 2; // seconds
        
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $this->connection = new PDO($dsn, $this->dbUser, $this->dbPass, $options);
                return; // Connection successful
            } catch (PDOException $e) {
                if ($i === $maxRetries - 1) {
                    throw new Exception("Database connection failed after {$maxRetries} attempts: " . $e->getMessage());
                }
                sleep($retryDelay);
            }
        }
    }
    
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    public function executeQuery(string $sql, array $params = []): PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    public function rollback(): bool {
        return $this->connection->rollback();
    }
}
