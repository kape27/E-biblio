<?php
/**
 * Test MySQL Connection - Diagnostic simple
 */

echo "🔧 E-Lib - Test de connexion MySQL\n";
echo "================================\n\n";

// 1. Vérifier les extensions PDO
echo "1. Vérification des extensions PDO:\n";
echo "   - PDO: " . (extension_loaded('pdo') ? '✅ OK' : '❌ NON') . "\n";
echo "   - PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ OK' : '❌ NON') . "\n\n";

if (!extension_loaded('pdo_mysql')) {
    echo "❌ Erreur fatale: L'extension PDO MySQL n'est pas activée.\n";
    echo "   Veuillez l'activer dans php.ini\n\n";
    
    echo "📋 Informations PHP:\n";
    echo "   - Version: " . PHP_VERSION . "\n";
    echo "   - SAPI: " . php_sapi_name() . "\n";
    echo "   - php.ini: " . php_ini_loaded_file() . "\n\n";
    
    echo "🔧 Pour corriger:\n";
    echo "   1. Ouvrez le fichier php.ini: " . php_ini_loaded_file() . "\n";
    echo "   2. Cherchez la ligne ';extension=pdo_mysql' ou ';extension=php_pdo_mysql.dll'\n";
    echo "   3. Supprimez le ';' au début de la ligne\n";
    echo "   4. Sauvegardez le fichier\n";
    echo "   5. Redémarrez Apache dans XAMPP\n\n";
    
    exit(1);
}

// 2. Test de connexion basique
echo "2. Test de connexion à MySQL:\n";

$host = 'localhost';
$port = 3306;
$user = 'root';
$pass = '';

try {
    // Test de connexion sans base de données spécifique
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "   ✅ Connexion MySQL réussie\n";
    
    // 3. Vérifier si la base de données existe
    echo "\n3. Vérification de la base de données 'elib_database':\n";
    
    $stmt = $pdo->query("SHOW DATABASES LIKE 'elib_database'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "   ✅ Base de données 'elib_database' trouvée\n";
        
        // Test de connexion à la base spécifique
        $dsn = "mysql:host=$host;port=$port;dbname=elib_database;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo "   ✅ Connexion à 'elib_database' réussie\n";
        
        // Vérifier les tables
        echo "\n4. Vérification des tables:\n";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "   ⚠️  Base de données vide - Aucune table trouvée\n";
            echo "   💡 Exécutez le script setup.php pour créer les tables\n";
        } else {
            echo "   ✅ Tables trouvées: " . implode(', ', $tables) . "\n";
        }
        
    } else {
        echo "   ❌ Base de données 'elib_database' non trouvée\n";
        echo "   💡 Création de la base de données...\n";
        
        try {
            $pdo->exec("CREATE DATABASE elib_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "   ✅ Base de données 'elib_database' créée avec succès\n";
            echo "   💡 Exécutez maintenant le script setup.php pour créer les tables\n";
        } catch (PDOException $e) {
            echo "   ❌ Erreur lors de la création: " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "   ❌ Erreur de connexion: " . $e->getMessage() . "\n\n";
    
    echo "🔧 Vérifications à faire:\n";
    echo "   1. XAMPP est-il démarré ?\n";
    echo "   2. Le service MySQL est-il actif dans XAMPP ?\n";
    echo "   3. Le port 3306 est-il libre ?\n";
    echo "   4. Les paramètres de connexion sont-ils corrects ?\n\n";
    
    echo "📋 Paramètres utilisés:\n";
    echo "   - Host: $host\n";
    echo "   - Port: $port\n";
    echo "   - User: $user\n";
    echo "   - Pass: " . (empty($pass) ? '(vide)' : '***') . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test terminé - " . date('Y-m-d H:i:s') . "\n";
?>