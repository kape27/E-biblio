<?php
/**
 * Security Functions for E-Lib Digital Library
 * Security-related utilities and validation functions
 * 
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
 */

/**
 * XSS Protection and Output Escaping
 */
class XSSProtection {
    /**
     * Escape HTML output to prevent XSS attacks
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public static function escapeHtml(string $string): string {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Escape for use in JavaScript strings
     * 
     * @param string $string String to escape
     * @return string Escaped string safe for JS
     */
    public static function escapeJs(string $string): string {
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    /**
     * Escape for use in URL parameters
     * 
     * @param string $string String to escape
     * @return string URL-encoded string
     */
    public static function escapeUrl(string $string): string {
        return urlencode($string);
    }
    
    /**
     * Escape for use in HTML attributes
     * 
     * @param string $string String to escape
     * @return string Escaped string safe for attributes
     */
    public static function escapeAttribute(string $string): string {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize input by removing potentially dangerous content
     * 
     * @param string $input Input string
     * @return string Sanitized string
     */
    public static function sanitizeInput(string $input): string {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Strip HTML tags
        $input = strip_tags($input);
        
        return $input;
    }
    
    /**
     * Sanitize rich text input (allows some HTML)
     * 
     * @param string $input Input string
     * @param array $allowedTags Allowed HTML tags
     * @return string Sanitized string
     */
    public static function sanitizeRichText(string $input, array $allowedTags = ['p', 'br', 'b', 'i', 'u', 'strong', 'em']): string {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Build allowed tags string
        $allowedTagsStr = '<' . implode('><', $allowedTags) . '>';
        
        // Strip disallowed tags
        $input = strip_tags($input, $allowedTagsStr);
        
        // Remove event handlers from remaining tags
        $input = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        $input = preg_replace('/\s*on\w+\s*=\s*[^\s>]*/i', '', $input);
        
        return trim($input);
    }
    
    /**
     * Validate and sanitize a filename
     * 
     * @param string $filename Original filename
     * @return string Safe filename
     */
    public static function sanitizeFilename(string $filename): string {
        // Remove path components
        $filename = basename($filename);
        
        // Remove null bytes
        $filename = str_replace(chr(0), '', $filename);
        
        // Replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($ext)) . '.' . $ext;
        }
        
        return $filename;
    }
}

/**
 * Security Headers Manager
 */
class SecurityHeaders {
    /**
     * Set security headers for the response
     */
    public static function setHeaders(): void {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS filter in browsers
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (basic)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; img-src 'self' data: blob:; font-src 'self' data:;");
    }
    
    /**
     * Set headers for file downloads
     * 
     * @param string $filename Filename for download
     * @param string $mimeType MIME type of file
     */
    public static function setDownloadHeaders(string $filename, string $mimeType): void {
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . XSSProtection::sanitizeFilename($filename) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

/**
 * Validate and sanitize file uploads
 */
class FileValidator {
    private const ALLOWED_BOOK_TYPES = ['pdf', 'epub'];
    private const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
    
    public static function validateBookFile(array $file): array {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed with error code: ' . $file['error'];
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds maximum allowed size of ' . format_file_size(self::MAX_FILE_SIZE);
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_BOOK_TYPES)) {
            $errors[] = 'Invalid file type. Only PDF and EPUB files are allowed.';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'application/pdf',
            'application/epub+zip'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = 'Invalid file format detected.';
        }
        
        return $errors;
    }
    
    public static function validateImageFile(array $file): array {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed with error code: ' . $file['error'];
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > self::MAX_IMAGE_SIZE) {
            $errors[] = 'Image size exceeds maximum allowed size of ' . format_file_size(self::MAX_IMAGE_SIZE);
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Invalid image type. Only JPG, PNG, GIF, and WebP images are allowed.';
        }
        
        // Check if it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'File is not a valid image.';
        }
        
        return $errors;
    }
}

/**
 * Input validation and sanitization
 */
class InputValidator {
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validateUsername(string $username): array {
        $errors = [];
        
        if (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        }
        
        if (strlen($username) > 50) {
            $errors[] = 'Username must not exceed 50 characters.';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        
        return $errors;
    }
    
    public static function validatePassword(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
        return $errors;
    }
    
    public static function validateBookMetadata(array $data): array {
        $errors = [];
        
        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = 'Book title is required.';
        }
        
        if (empty(trim($data['author'] ?? ''))) {
            $errors[] = 'Book author is required.';
        }
        
        if (empty(trim($data['description'] ?? ''))) {
            $errors[] = 'Book description is required.';
        }
        
        if (empty($data['category_id']) || !is_numeric($data['category_id'])) {
            $errors[] = 'Valid category selection is required.';
        }
        
        return $errors;
    }
}

/**
 * Rate limiting for login attempts
 */
class RateLimiter {
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes
    
    public static function checkLoginAttempts(string $identifier): bool {
        $key = 'login_attempts_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset if lockout time has passed
        if (time() - $attempts['last_attempt'] > self::LOCKOUT_TIME) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
            return true;
        }
        
        return $attempts['count'] < self::MAX_ATTEMPTS;
    }
    
    public static function recordFailedAttempt(string $identifier): void {
        $key = 'login_attempts_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        }
        
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();
    }
    
    public static function resetAttempts(string $identifier): void {
        $key = 'login_attempts_' . md5($identifier);
        unset($_SESSION[$key]);
    }
}


/**
 * Session Security Manager
 */
class SessionSecurity {
    /**
     * Initialize secure session settings
     */
    public static function initSecureSession(): void {
        // Set secure session cookie parameters
        $cookieParams = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        session_set_cookie_params($cookieParams);
        
        // Use only cookies for session ID
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Validate session integrity
     * 
     * @return bool True if session is valid
     */
    public static function validateSession(): bool {
        // Check if session has user agent fingerprint
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== md5($_SERVER['HTTP_USER_AGENT'] ?? '')) {
                return false;
            }
        }
        
        // Check if session has IP fingerprint (optional, can cause issues with mobile)
        // Uncomment if strict IP binding is needed
        // if (isset($_SESSION['ip_address'])) {
        //     if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        //         return false;
        //     }
        // }
        
        return true;
    }
    
    /**
     * Set session fingerprint
     */
    public static function setSessionFingerprint(): void {
        $_SESSION['user_agent'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check for session timeout
     * 
     * @param int $timeout Timeout in seconds (default 30 minutes)
     * @return bool True if session is still valid
     */
    public static function checkSessionTimeout(int $timeout = 1800): bool {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $timeout) {
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Destroy session securely
     */
    public static function destroySession(): void {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }
}

/**
 * SQL Injection Prevention Helper
 * Note: Always use prepared statements. This is for additional validation.
 */
class SQLSecurity {
    /**
     * Validate that a value is a positive integer (for IDs)
     * 
     * @param mixed $value Value to validate
     * @return int|false Integer value or false if invalid
     */
    public static function validateId($value): int|false {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id !== false ? $id : false;
    }
    
    /**
     * Validate sort column name against whitelist
     * 
     * @param string $column Column name
     * @param array $allowedColumns Whitelist of allowed columns
     * @return string|null Safe column name or null
     */
    public static function validateSortColumn(string $column, array $allowedColumns): ?string {
        return in_array($column, $allowedColumns, true) ? $column : null;
    }
    
    /**
     * Validate sort direction
     * 
     * @param string $direction Sort direction
     * @return string Safe sort direction (ASC or DESC)
     */
    public static function validateSortDirection(string $direction): string {
        return strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
    }
    
    /**
     * Validate pagination parameters
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param int $maxPerPage Maximum items per page
     * @return array [page, perPage, offset]
     */
    public static function validatePagination(int $page, int $perPage, int $maxPerPage = 100): array {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, $maxPerPage));
        $offset = ($page - 1) * $perPage;
        
        return [$page, $perPage, $offset];
    }
}