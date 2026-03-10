<?php
/**
 * CSRF Protection Manager for E-Lib Digital Library
 * Advanced CSRF protection with token generation, validation, and rotation
 * 
 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5
 */

class CSRFProtectionManager {
    private const TOKEN_LENGTH = 32;
    private const TOKEN_LIFETIME = 3600; // 1 hour
    private const SESSION_KEY = 'csrf_tokens';
    
    /**
     * Generate a secure CSRF token
     * 
     * @return string Generated CSRF token
     */
    public static function generateToken(): string {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate cryptographically secure random token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        // Initialize token storage if not exists
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        
        // Store token with timestamp for expiration
        $_SESSION[self::SESSION_KEY][$token] = [
            'created_at' => time(),
            'used' => false
        ];
        
        // Clean up expired tokens
        self::cleanupExpiredTokens();
        
        return $token;
    }
    
    /**
     * Validate CSRF token with temporal validation
     * 
     * @param string $token Token to validate
     * @param bool $singleUse Whether token should be single-use (default: true)
     * @return bool True if token is valid
     */
    public static function validateToken(string $token, bool $singleUse = true): bool {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token storage exists
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        
        // Check if token exists
        if (!isset($_SESSION[self::SESSION_KEY][$token])) {
            return false;
        }
        
        $tokenData = $_SESSION[self::SESSION_KEY][$token];
        
        // Check if token has already been used (for single-use tokens)
        if ($singleUse && $tokenData['used']) {
            return false;
        }
        
        // Check token expiration
        if (time() - $tokenData['created_at'] > self::TOKEN_LIFETIME) {
            // Remove expired token
            unset($_SESSION[self::SESSION_KEY][$token]);
            return false;
        }
        
        // Mark token as used if single-use
        if ($singleUse) {
            $_SESSION[self::SESSION_KEY][$token]['used'] = true;
        }
        
        return true;
    }
    
    /**
     * Inject CSRF token into HTML forms automatically
     * 
     * @param string $html HTML content containing forms
     * @return string HTML with CSRF tokens injected
     */
    public static function injectTokenInForms(string $html): string {
        // Generate a new token for this request
        $token = self::generateToken();
        
        // Create hidden input field
        $hiddenField = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        
        // Pattern to match form opening tags
        $pattern = '/(<form[^>]*>)/i';
        
        // Inject hidden field after each form opening tag
        $html = preg_replace($pattern, '$1' . $hiddenField, $html);
        
        return $html;
    }
    
    /**
     * Get CSRF token for AJAX requests
     * 
     * @return string CSRF token for use in AJAX
     */
    public static function getTokenForAjax(): string {
        return self::generateToken();
    }
    
    /**
     * Rotate CSRF token (generate new and invalidate old ones)
     * 
     * @return string New CSRF token
     */
    public static function rotateToken(): string {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear all existing tokens
        $_SESSION[self::SESSION_KEY] = [];
        
        // Generate and return new token
        return self::generateToken();
    }
    
    /**
     * Clean up expired tokens from session
     */
    private static function cleanupExpiredTokens(): void {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            return;
        }
        
        $currentTime = time();
        
        foreach ($_SESSION[self::SESSION_KEY] as $token => $data) {
            if ($currentTime - $data['created_at'] > self::TOKEN_LIFETIME) {
                unset($_SESSION[self::SESSION_KEY][$token]);
            }
        }
    }
    
    /**
     * Validate CSRF token from request data
     * 
     * @param array $requestData Request data ($_POST, $_GET, etc.)
     * @param bool $singleUse Whether token should be single-use
     * @return bool True if valid token found and validated
     */
    public static function validateRequestToken(array $requestData, bool $singleUse = true): bool {
        // Check for token in request data
        $token = $requestData['csrf_token'] ?? '';
        
        if (empty($token)) {
            return false;
        }
        
        return self::validateToken($token, $singleUse);
    }
    
    /**
     * Get current active tokens count (for debugging/monitoring)
     * 
     * @return int Number of active tokens
     */
    public static function getActiveTokensCount(): int {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            return 0;
        }
        
        // Clean up expired tokens first
        self::cleanupExpiredTokens();
        
        return count($_SESSION[self::SESSION_KEY]);
    }
    
    /**
     * Clear all CSRF tokens (useful for logout)
     */
    public static function clearAllTokens(): void {
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION[self::SESSION_KEY] = [];
        }
    }
    
    /**
     * Check if CSRF protection is properly initialized
     * 
     * @return bool True if CSRF protection is ready
     */
    public static function isInitialized(): bool {
        return session_status() !== PHP_SESSION_NONE && isset($_SESSION[self::SESSION_KEY]);
    }
    
    /**
     * Initialize CSRF protection (call this early in request lifecycle)
     */
    public static function initialize(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        
        // Clean up expired tokens on initialization
        self::cleanupExpiredTokens();
    }
    
    /**
     * Generate CSRF token HTML input field
     * 
     * @param string $fieldName Name attribute for the input field
     * @return string HTML input field with CSRF token
     */
    public static function generateTokenField(string $fieldName = 'csrf_token'): string {
        $token = self::generateToken();
        return '<input type="hidden" name="' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Generate CSRF token meta tag for AJAX requests
     * 
     * @return string HTML meta tag with CSRF token
     */
    public static function generateTokenMeta(): string {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Validate CSRF token and log security events
     * 
     * @param string $token Token to validate
     * @param string $action Action being performed (for logging)
     * @param bool $singleUse Whether token should be single-use
     * @return bool True if token is valid
     */
    public static function validateTokenWithLogging(string $token, string $action = 'unknown', bool $singleUse = true): bool {
        $isValid = self::validateToken($token, $singleUse);
        
        if (!$isValid) {
            // Log CSRF validation failure
            $logData = [
                'action' => $action,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s'),
                'token_provided' => !empty($token),
                'session_id' => session_id()
            ];
            
            error_log('CSRF validation failed: ' . json_encode($logData));
            
            // If logging function exists, use it
            if (function_exists('log_action')) {
                log_action('csrf_validation_failed', "CSRF token validation failed for action: {$action}");
            }
        }
        
        return $isValid;
    }
}