<?php
/**
 * Utility Functions for E-Lib Digital Library
 * Common functions used throughout the application
 */

/**
 * Sanitize and escape output to prevent XSS attacks
 */
function escape_html(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize input data
 */
function sanitize_input(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Generate CSRF token (using enhanced CSRF protection)
 */
function generate_csrf_token(): string {
    require_once __DIR__ . '/csrf_protection.php';
    return CSRFProtectionManager::generateToken();
}

/**
 * Verify CSRF token (using enhanced CSRF protection)
 */
function verify_csrf_token(string $token): bool {
    require_once __DIR__ . '/csrf_protection.php';
    return CSRFProtectionManager::validateTokenWithLogging($token, 'form_submission');
}

/**
 * Format file size for display
 */
function format_file_size(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Format file size for display (alias for compatibility)
 */
function formatFileSize(int $bytes): string {
    return format_file_size($bytes);
}

/**
 * Generate secure random filename
 */
function generate_secure_filename(string $originalName): string {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $randomName = bin2hex(random_bytes(16));
    return $randomName . '.' . strtolower($extension);
}

/**
 * Validate file extension
 */
function validate_file_extension(string $filename, array $allowedExtensions): bool {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}

/**
 * Log system action
 */
function log_action(string $action, string $details = '', ?int $userId = null): void {
    try {
        $db = DatabaseManager::getInstance();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // If userId is provided, verify it exists
        if ($userId !== null) {
            $userExists = $db->fetchOne("SELECT id FROM users WHERE id = ?", [$userId]);
            if (!$userExists) {
                // Log without user_id if user doesn't exist
                $userId = null;
            }
        }
        
        $sql = "INSERT INTO logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
        $db->executeQuery($sql, [$userId, $action, $details, $ipAddress, $userAgent]);
    } catch (Exception $e) {
        error_log("Failed to log action: " . $e->getMessage());
    }
}

/**
 * Redirect with message
 */
function redirect_with_message(string $url, string $message, string $type = 'info'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

/**
 * Display flash message
 */
function display_flash_message(): string {
    if (isset($_SESSION['flash_message'])) {
        $message = escape_html($_SESSION['flash_message']);
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        $alertClass = match($type) {
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            default => 'bg-blue-100 border-blue-400 text-blue-700'
        };
        
        return "<div class='border px-4 py-3 rounded mb-4 $alertClass'>$message</div>";
    }
    return '';
}