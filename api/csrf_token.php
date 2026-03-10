<?php
/**
 * E-Lib Digital Library - CSRF Token API
 * Provides fresh CSRF tokens for AJAX requests
 */

session_start();

require_once '../includes/csrf_protection.php';

// Initialize CSRF protection
CSRFProtectionManager::initialize();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only accept GET requests for token retrieval
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Generate new CSRF token
    $token = CSRFProtectionManager::generateToken();
    
    echo json_encode([
        'success' => true,
        'token' => $token
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate token'
    ]);
}
?>