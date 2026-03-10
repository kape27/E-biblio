<?php
/**
 * Security Tables Setup Script
 * Creates the enhanced security tables for E-Lib Digital Library
 * 
 * Requirements: 7.1, 7.2, 9.4, 5.1
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Initialize authentication and require admin role
$auth = new AuthManager();
$auth->requireRole('admin');

$success_messages = [];
$error_messages = [];

try {
    $db = DatabaseManager::getInstance();
    $pdo = $db->getConnection();
    
    // Read the security schema SQL file
    $schemaFile = '../config/security_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception('Security schema file not found: ' . $schemaFile);
    }
    
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new Exception('Failed to read security schema file');
    }
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*USE\s+/', $stmt) &&
                   !preg_match('/^\s*CREATE\s+EVENT\s+/', $stmt); // Skip events for now
        }
    );
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            
            // Extract table/view/procedure name for success message
            if (preg_match('/CREATE\s+TABLE\s+(\w+)/i', $statement, $matches)) {
                $success_messages[] = "Created table: " . $matches[1];
            } elseif (preg_match('/CREATE\s+VIEW\s+(\w+)/i', $statement, $matches)) {
                $success_messages[] = "Created view: " . $matches[1];
            } elseif (preg_match('/CREATE\s+PROCEDURE\s+(\w+)/i', $statement, $matches)) {
                $success_messages[] = "Created procedure: " . $matches[1];
            } elseif (preg_match('/CREATE\s+INDEX\s+(\w+)/i', $statement, $matches)) {
                $success_messages[] = "Created index: " . $matches[1];
            } elseif (preg_match('/INSERT\s+INTO\s+(\w+)/i', $statement, $matches)) {
                $success_messages[] = "Inserted default data into: " . $matches[1];
            }
            
        } catch (PDOException $e) {
            // Check if error is due to table already existing
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/CREATE\s+TABLE\s+(\w+)/i', $statement, $matches)) {
                    $error_messages[] = "Table already exists: " . $matches[1];
                }
                continue; // Skip this error and continue
            } else {
                throw $e; // Re-throw other errors
            }
        }
    }
    
    $pdo->commit();
    $success_messages[] = "Security tables setup completed successfully!";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    $error_messages[] = "Error setting up security tables: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Tables Setup - E-Lib Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Security Tables Setup</h1>
            
            <?php if (!empty($success_messages)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <h3 class="font-bold mb-2">Success Messages:</h3>
                    <ul class="list-disc list-inside">
                        <?php foreach ($success_messages as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_messages)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <h3 class="font-bold mb-2">Error Messages:</h3>
                    <ul class="list-disc list-inside">
                        <?php foreach ($error_messages as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Security Tables Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border rounded p-4">
                        <h3 class="font-semibold text-lg mb-2">Created Tables</h3>
                        <ul class="text-sm space-y-1">
                            <li><strong>security_events</strong> - Audit logging</li>
                            <li><strong>rate_limits</strong> - Rate limiting data</li>
                            <li><strong>password_history</strong> - Password history</li>
                            <li><strong>secure_sessions</strong> - Session management</li>
                            <li><strong>csrf_tokens</strong> - CSRF protection</li>
                            <li><strong>failed_login_attempts</strong> - Login tracking</li>
                            <li><strong>security_config</strong> - Security settings</li>
                        </ul>
                    </div>
                    
                    <div class="border rounded p-4">
                        <h3 class="font-semibold text-lg mb-2">Created Views</h3>
                        <ul class="text-sm space-y-1">
                            <li><strong>recent_security_events</strong> - Last 24h events</li>
                            <li><strong>current_rate_limits</strong> - Active rate limits</li>
                        </ul>
                        
                        <h3 class="font-semibold text-lg mb-2 mt-4">Procedures</h3>
                        <ul class="text-sm space-y-1">
                            <li><strong>CleanupSecurityData</strong> - Data cleanup</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 rounded">
                    <h3 class="font-semibold text-lg mb-2">Next Steps</h3>
                    <ul class="text-sm space-y-1">
                        <li>• Security tables are now ready for use</li>
                        <li>• Configure security settings in the security_config table</li>
                        <li>• Implement security classes to use these tables</li>
                        <li>• Set up automated cleanup (run CleanupSecurityData procedure)</li>
                        <li>• Monitor security events through the admin dashboard</li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Return to Admin Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>