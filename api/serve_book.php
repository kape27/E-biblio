<?php
/**
 * Secure Book File Server
 * Serves book files (PDF/EPUB) to authenticated users
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$bookId = (int)($_GET['id'] ?? 0);
if ($bookId <= 0) {
    http_response_code(400);
    exit('Invalid book ID');
}

$db = DatabaseManager::getInstance();
$book = $db->fetchOne("SELECT file_path, file_type, title FROM books WHERE id = ?", [$bookId]);

if (!$book) {
    http_response_code(404);
    exit('Book not found');
}

$filePath = dirname(__DIR__) . '/uploads/books/' . $book['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found');
}

// Set appropriate headers
$mimeTypes = [
    'pdf' => 'application/pdf',
    'epub' => 'application/epub+zip'
];

$mimeType = $mimeTypes[strtolower($book['file_type'])] ?? 'application/octet-stream';

// Prevent caching issues
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="' . basename($book['file_path']) . '"');

// Allow CORS for same-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Output file
readfile($filePath);
exit;
