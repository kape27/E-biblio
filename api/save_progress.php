<?php
/**
 * E-Lib Digital Library - Save Reading Progress API
 * Saves user's reading position for a book
 * 
 * Requirements: 5.5
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/csrf_protection.php';
require_once '../includes/advanced_input_validator.php';

// Initialize CSRF protection
CSRFProtectionManager::initialize();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Use AdvancedInputValidator for input validation
$validationRules = [
    'book_id' => [
        'required' => true,
        'type' => 'integer',
        'custom_validator' => function($value) {
            return $value > 0 ? true : 'Book ID must be positive';
        }
    ],
    'position' => [
        'required' => true,
        'type' => 'string',
        'sanitize' => true
    ],
    'csrf_token' => [
        'required' => true,
        'type' => 'string',
        'sanitize' => false
    ]
];

$validationResult = AdvancedInputValidator::validateAndSanitize($input, $validationRules);

if (!$validationResult['valid']) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data', 'details' => $validationResult['errors']]);
    exit;
}

$validatedData = $validationResult['data'];

// Validate CSRF token
if (!CSRFProtectionManager::validateTokenWithLogging($validatedData['csrf_token'], 'save_progress_api')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$userId = $_SESSION['user_id'];
$bookId = $validatedData['book_id'];
$position = $validatedData['position'];

try {
    $db = DatabaseManager::getInstance();
    
    // Check if progress record exists
    $existing = $db->fetchOne(
        "SELECT id FROM reading_progress WHERE user_id = ? AND book_id = ?",
        [$userId, $bookId]
    );
    
    if ($existing) {
        // Update existing progress
        $db->executeQuery(
            "UPDATE reading_progress SET last_position = ?, updated_at = NOW() WHERE id = ?",
            [$position, $existing['id']]
        );
    } else {
        // Create new progress record
        $db->executeQuery(
            "INSERT INTO reading_progress (user_id, book_id, last_position, progress_data) VALUES (?, ?, ?, '{}')",
            [$userId, $bookId, $position]
        );
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Save progress error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save progress']);
}