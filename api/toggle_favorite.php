<?php
/**
 * E-Lib Digital Library - Toggle Favorite API
 * Adds or removes a book from user's favorites
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/favorites_manager.php';
require_once '../includes/csrf_protection.php';
require_once '../includes/advanced_input_validator.php';

// Initialize CSRF protection
CSRFProtectionManager::initialize();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Get JSON input or POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
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
    'csrf_token' => [
        'required' => true,
        'type' => 'string',
        'sanitize' => false
    ]
];

$validationResult = AdvancedInputValidator::validateAndSanitize($input, $validationRules);

if (!$validationResult['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données de requête invalides', 'details' => $validationResult['errors']]);
    exit;
}

$validatedData = $validationResult['data'];

// Validate CSRF token
if (!CSRFProtectionManager::validateTokenWithLogging($validatedData['csrf_token'], 'toggle_favorite_api')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
    exit;
}

$bookId = $validatedData['book_id'];

$userId = $_SESSION['user_id'];
$favoritesManager = new FavoritesManager();

$result = $favoritesManager->toggleFavorite($userId, $bookId);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'is_favorite' => $result['is_favorite'],
        'action' => $result['action'],
        'message' => $result['action'] === 'added' ? 'Ajouté aux favoris' : 'Retiré des favoris'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $result['errors'][0] ?? 'Erreur inconnue'
    ]);
}
