<?php
/**
 * Database Configuration and Connection Manager
 * Provides secure PDO connection with prepared statements
 */

// Inclure le gestionnaire d'erreurs pour masquer les avertissements VCRUNTIME140.dll
require_once __DIR__ . '/error_handler.php';

class DatabaseManager {
    private static $instance = null;
    private $connection;
    
    // Database configuration
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'elib_database';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance(): DatabaseManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect(): void {
        $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
        
        // Vérifier que l'extension pdo_mysql est chargée
        if (!extension_loaded('pdo_mysql')) {
            // Si on est dans un contexte web, rediriger vers la page d'erreur
            if (!php_sapi_name() === 'cli' && !headers_sent()) {
                header('Location: admin/error_extensions.php');
                exit;
            }
            throw new Exception("L'extension PDO MySQL n'est pas activée. Veuillez l'activer dans php.ini");
        }
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        // Ajouter l'option MySQL seulement si elle est disponible
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
        }
        
        try {
            $this->connection = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            
            // Configurer l'encodage UTF-8 si l'option MySQL n'était pas disponible
            if (!defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $this->connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
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