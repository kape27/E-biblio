<?php
/**
 * E-Lib Digital Library - Setup & Database Updates
 * Script pour appliquer les mises à jour de la base de données
 */

// Vérifier que le script est exécuté depuis la ligne de commande ou par un admin
session_start();

// Configuration de sécurité
$isCommandLine = php_sapi_name() === 'cli';
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if (!$isCommandLine && !$isAdmin) {
    http_response_code(403);
    die('Accès refusé. Ce script ne peut être exécuté que par un administrateur ou en ligne de commande.');
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Configuration des mises à jour
$updates = [
    '1.0.0' => [
        'description' => 'Création des tables de base',
        'sql' => []
    ],
    '1.1.0' => [
        'description' => 'Ajout du système de favoris',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS favorites (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
                UNIQUE KEY unique_favorite (user_id, book_id),
                INDEX idx_user_favorites (user_id),
                INDEX idx_book_favorites (book_id)
            )"
        ]
    ],
    '1.2.0' => [
        'description' => 'Ajout du système de récupération de mot de passe',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS password_resets (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_expires (expires_at)
            )"
        ]
    ],
    '1.3.0' => [
        'description' => 'Ajout du système de notation',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS ratings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
                UNIQUE KEY unique_rating (user_id, book_id),
                INDEX idx_book_ratings (book_id)
            )"
        ]
    ],
    '1.4.0' => [
        'description' => 'Ajout du système de commentaires',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS reviews (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                content TEXT NOT NULL,
                is_approved BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
                INDEX idx_book_reviews (book_id),
                INDEX idx_approved (is_approved)
            )"
        ]
    ],
    '1.5.0' => [
        'description' => 'Ajout du système de notifications',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS notifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_unread (user_id, is_read),
                INDEX idx_type (type)
            )"
        ]
    ],
    '1.6.0' => [
        'description' => 'Amélioration des statistiques de lecture',
        'sql' => [
            "ALTER TABLE reading_progress 
             ADD COLUMN IF NOT EXISTS total_time_spent INT DEFAULT 0,
             ADD COLUMN IF NOT EXISTS pages_read INT DEFAULT 0,
             ADD COLUMN IF NOT EXISTS completion_percentage DECIMAL(5,2) DEFAULT 0.00"
        ]
    ]
];

/**
 * Classe pour gérer les mises à jour
 */
class DatabaseUpdater {
    private $db;
    private $currentVersion;
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
        $this->initVersionTable();
        $this->currentVersion = $this->getCurrentVersion();
    }
    
    /**
     * Initialise la table de versions si elle n'existe pas
     */
    private function initVersionTable() {
        $sql = "CREATE TABLE IF NOT EXISTS database_versions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            version VARCHAR(20) NOT NULL UNIQUE,
            description TEXT,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_version (version)
        )";
        
        try {
            $this->db->executeQuery($sql);
            
            // Ajouter la version initiale si la table est vide
            $count = $this->db->fetchOne("SELECT COUNT(*) as count FROM database_versions");
            if ($count['count'] == 0) {
                $this->db->executeQuery(
                    "INSERT INTO database_versions (version, description) VALUES (?, ?)",
                    ['1.0.0', 'Version initiale']
                );
            }
        } catch (Exception $e) {
            $this->logMessage("Erreur lors de l'initialisation de la table des versions: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Récupère la version actuelle de la base de données
     */
    private function getCurrentVersion() {
        try {
            $result = $this->db->fetchOne(
                "SELECT version FROM database_versions ORDER BY applied_at DESC LIMIT 1"
            );
            return $result ? $result['version'] : '1.0.0';
        } catch (Exception $e) {
            $this->logMessage("Erreur lors de la récupération de la version: " . $e->getMessage(), 'error');
            return '1.0.0';
        }
    }
    
    /**
     * Applique les mises à jour nécessaires
     */
    public function applyUpdates($updates) {
        $this->logMessage("Version actuelle de la base de données: {$this->currentVersion}");
        
        $updatesApplied = 0;
        $errors = [];
        
        foreach ($updates as $version => $update) {
            if (version_compare($version, $this->currentVersion, '>')) {
                $this->logMessage("Application de la mise à jour {$version}: {$update['description']}");
                
                try {
                    $this->applyUpdate($version, $update);
                    $updatesApplied++;
                    $this->logMessage("✓ Mise à jour {$version} appliquée avec succès", 'success');
                } catch (Exception $e) {
                    $error = "✗ Erreur lors de l'application de la mise à jour {$version}: " . $e->getMessage();
                    $this->logMessage($error, 'error');
                    $errors[] = $error;
                }
            }
        }
        
        if ($updatesApplied > 0) {
            $this->logMessage("\n{$updatesApplied} mise(s) à jour appliquée(s) avec succès.", 'success');
        } else {
            $this->logMessage("Aucune mise à jour nécessaire. Base de données à jour.", 'info');
        }
        
        if (!empty($errors)) {
            $this->logMessage("\nErreurs rencontrées:", 'error');
            foreach ($errors as $error) {
                $this->logMessage($error, 'error');
            }
        }
        
        return ['applied' => $updatesApplied, 'errors' => $errors];
    }
    
    /**
     * Applique une mise à jour spécifique
     */
    private function applyUpdate($version, $update) {
        // Commencer une transaction
        $this->db->executeQuery("START TRANSACTION");
        
        try {
            // Exécuter les requêtes SQL
            foreach ($update['sql'] as $sql) {
                if (!empty(trim($sql))) {
                    $this->db->executeQuery($sql);
                }
            }
            
            // Enregistrer la version appliquée
            $this->db->executeQuery(
                "INSERT INTO database_versions (version, description) VALUES (?, ?)",
                [$version, $update['description']]
            );
            
            // Valider la transaction
            $this->db->executeQuery("COMMIT");
            
            // Mettre à jour la version courante
            $this->currentVersion = $version;
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->executeQuery("ROLLBACK");
            throw $e;
        }
    }
    
    /**
     * Affiche un message avec couleur (CLI) ou HTML
     */
    private function logMessage($message, $type = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        
        if (php_sapi_name() === 'cli') {
            // Mode ligne de commande avec couleurs
            $colors = [
                'info' => "\033[0m",      // Normal
                'success' => "\033[32m",  // Vert
                'error' => "\033[31m",    // Rouge
                'warning' => "\033[33m"   // Jaune
            ];
            
            $color = $colors[$type] ?? $colors['info'];
            $reset = "\033[0m";
            
            echo "[{$timestamp}] {$color}{$message}{$reset}\n";
        } else {
            // Mode web avec HTML
            $classes = [
                'info' => 'color: #333;',
                'success' => 'color: #28a745;',
                'error' => 'color: #dc3545;',
                'warning' => 'color: #ffc107;'
            ];
            
            $style = $classes[$type] ?? $classes['info'];
            echo "<div style='{$style}'>[{$timestamp}] {$message}</div>\n";
        }
    }
    
    /**
     * Vérifie l'état de la base de données
     */
    public function checkDatabaseStatus() {
        $this->logMessage("=== État de la base de données ===");
        $this->logMessage("Version actuelle: {$this->currentVersion}");
        
        // Vérifier les tables existantes
        $tables = $this->db->fetchAll("SHOW TABLES");
        $this->logMessage("Tables présentes: " . count($tables));
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $count = $this->db->fetchOne("SELECT COUNT(*) as count FROM `{$tableName}`");
            $this->logMessage("  - {$tableName}: {$count['count']} enregistrement(s)");
        }
        
        // Vérifier les versions appliquées
        $versions = $this->db->fetchAll(
            "SELECT version, description, applied_at FROM database_versions ORDER BY applied_at"
        );
        
        $this->logMessage("\nHistorique des mises à jour:");
        foreach ($versions as $version) {
            $this->logMessage("  - {$version['version']}: {$version['description']} ({$version['applied_at']})");
        }
    }
}

// Interface web simple
if (!$isCommandLine) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>E-Lib - Setup & Mises à jour</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
            .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; border-radius: 5px; font-weight: bold; }
            .btn-primary { background: #007bff; color: white; }
            .btn-success { background: #28a745; color: white; }
            .btn-info { background: #17a2b8; color: white; }
            .output { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 20px 0; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px; color: #856404; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 E-Lib - Setup & Mises à jour</h1>
            
            <div class="warning">
                <strong>⚠️ Attention:</strong> Assurez-vous de faire une sauvegarde de votre base de données avant d'appliquer les mises à jour.
            </div>
            
            <p>Utilisez les boutons ci-dessous pour gérer les mises à jour de la base de données:</p>
            
            <a href="?action=check" class="btn btn-info">📊 Vérifier l'état</a>
            <a href="?action=update" class="btn btn-primary">🚀 Appliquer les mises à jour</a>
            <a href="?action=force" class="btn btn-success">🔄 Forcer toutes les mises à jour</a>
            
            <?php
            if (isset($_GET['action'])) {
                echo '<div class="output">';
                
                try {
                    $updater = new DatabaseUpdater();
                    
                    switch ($_GET['action']) {
                        case 'check':
                            $updater->checkDatabaseStatus();
                            break;
                            
                        case 'update':
                            $result = $updater->applyUpdates($updates);
                            break;
                            
                        case 'force':
                            // Réinitialiser la version pour forcer toutes les mises à jour
                            echo "⚠️ Forçage de toutes les mises à jour...\n";
                            $updater = new DatabaseUpdater();
                            $result = $updater->applyUpdates($updates);
                            break;
                    }
                } catch (Exception $e) {
                    echo "❌ Erreur: " . $e->getMessage();
                }
                
                echo '</div>';
            }
            ?>
            
            <hr>
            <p><small>Version du script: 1.0 | <a href="dashboard.php">← Retour à l'administration</a></small></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Mode ligne de commande
try {
    echo "🔧 E-Lib Database Setup & Updates\n";
    echo "================================\n\n";
    
    $updater = new DatabaseUpdater();
    
    // Vérifier les arguments de ligne de commande
    $action = $argv[1] ?? 'update';
    
    switch ($action) {
        case 'check':
        case 'status':
            $updater->checkDatabaseStatus();
            break;
            
        case 'update':
        default:
            $result = $updater->applyUpdates($updates);
            
            if ($result['applied'] > 0) {
                exit(0); // Succès
            } elseif (!empty($result['errors'])) {
                exit(1); // Erreur
            } else {
                exit(0); // Aucune mise à jour nécessaire
            }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
    exit(1);
}
?>