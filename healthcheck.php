<?php
/**
 * Health Check Endpoint for Docker
 * Returns JSON status of application components
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Check PHP
$health['checks']['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION
];

// Check required extensions
$requiredExtensions = ['pdo_mysql', 'zip', 'gd'];
foreach ($requiredExtensions as $ext) {
    $health['checks']['extension_' . $ext] = [
        'status' => extension_loaded($ext) ? 'ok' : 'error',
        'loaded' => extension_loaded($ext)
    ];
    if (!extension_loaded($ext)) {
        $health['status'] = 'unhealthy';
    }
}

// Check database connection
try {
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbName = getenv('DB_NAME') ?: 'elib_database';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: '';
    
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName}",
        $dbUser,
        $dbPass,
        [PDO::ATTR_TIMEOUT => 3]
    );
    
    $health['checks']['database'] = [
        'status' => 'ok',
        'connected' => true
    ];
} catch (PDOException $e) {
    $health['checks']['database'] = [
        'status' => 'error',
        'connected' => false,
        'error' => $e->getMessage()
    ];
    $health['status'] = 'unhealthy';
}

// Check writable directories
$writableDirs = ['uploads/books', 'uploads/covers', 'logs', 'backups'];
foreach ($writableDirs as $dir) {
    $isWritable = is_writable(__DIR__ . '/' . $dir);
    $health['checks']['writable_' . str_replace('/', '_', $dir)] = [
        'status' => $isWritable ? 'ok' : 'error',
        'writable' => $isWritable
    ];
    if (!$isWritable) {
        $health['status'] = 'degraded';
    }
}

// Set HTTP status code
http_response_code($health['status'] === 'healthy' ? 200 : 503);

echo json_encode($health, JSON_PRETTY_PRINT);
