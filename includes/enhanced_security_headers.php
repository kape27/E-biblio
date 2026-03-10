<?php
/**
 * Enhanced Security Headers Manager for E-Lib Digital Library
 * Provides advanced HTTP security headers configuration
 * 
 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
 */

class EnhancedSecurityHeaders {
    
    /**
     * Set strict security headers for maximum protection
     * Implements comprehensive security headers including CSP, HSTS, and anti-clickjacking
     * 
     * @return void
     */
    public static function setStrictHeaders(): void {
        // Prevent clickjacking attacks
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS filter in browsers with blocking mode
        header('X-XSS-Protection: 1; mode=block');
        
        // Strict referrer policy for privacy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Prevent caching of sensitive content
        header('Cache-Control: no-cache, no-store, must-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Feature policy to restrict dangerous features
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
        
        // Set default CSP if none specified
        self::setCSPHeaders();
        
        // Set HSTS if HTTPS is available
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            self::setHSTSHeaders();
        }
    }
    
    /**
     * Set Content Security Policy headers with flexible configuration
     * Provides granular control over resource loading and script execution
     * 
     * @param array $directives Custom CSP directives to override defaults
     * @return void
     */
    public static function setCSPHeaders(array $directives = []): void {
        // Default secure CSP directives
        $defaultDirectives = [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            'style-src' => "'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com",
            'img-src' => "'self' data: blob: https:",
            'font-src' => "'self' data: https://fonts.gstatic.com",
            'connect-src' => "'self'",
            'media-src' => "'self'",
            'object-src' => "'none'",
            'child-src' => "'self'",
            'frame-ancestors' => "'none'",
            'form-action' => "'self'",
            'base-uri' => "'self'",
            'manifest-src' => "'self'"
        ];
        
        // Merge with custom directives
        $cspDirectives = array_merge($defaultDirectives, $directives);
        
        // Build CSP header string
        $cspParts = [];
        foreach ($cspDirectives as $directive => $value) {
            $cspParts[] = $directive . ' ' . $value;
        }
        
        $cspHeader = implode('; ', $cspParts);
        
        // Set both CSP headers for maximum compatibility
        header('Content-Security-Policy: ' . $cspHeader);
        header('X-Content-Security-Policy: ' . $cspHeader); // Legacy support
    }
    
    /**
     * Set HTTP Strict Transport Security headers to force HTTPS
     * Ensures all future connections use HTTPS for enhanced security
     * 
     * @param int $maxAge Maximum age in seconds (default: 1 year)
     * @param bool $includeSubDomains Include subdomains in HSTS policy
     * @param bool $preload Allow inclusion in HSTS preload lists
     * @return void
     */
    public static function setHSTSHeaders(int $maxAge = 31536000, bool $includeSubDomains = true, bool $preload = false): void {
        $hstsValue = 'max-age=' . $maxAge;
        
        if ($includeSubDomains) {
            $hstsValue .= '; includeSubDomains';
        }
        
        if ($preload) {
            $hstsValue .= '; preload';
        }
        
        header('Strict-Transport-Security: ' . $hstsValue);
    }
    
    /**
     * Set secure headers for file downloads
     * Provides additional security for file serving operations
     * 
     * @param string $filename Filename for download
     * @param string $mimeType MIME type of the file
     * @return void
     */
    public static function setDownloadHeaders(string $filename, string $mimeType): void {
        // Sanitize filename to prevent header injection
        $safeFilename = preg_replace('/[^\w\-_\.]/', '_', basename($filename));
        
        // Set content type and disposition
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        
        // Security headers for downloads
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent caching of sensitive files
        header('Cache-Control: no-cache, no-store, must-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Additional download security
        header('X-Download-Options: noopen'); // IE specific
        header('X-Permitted-Cross-Domain-Policies: none');
    }
    
    /**
     * Prevent caching of sensitive content
     * Ensures sensitive pages are not cached by browsers or proxies
     * 
     * @return void
     */
    public static function preventCaching(): void {
        header('Cache-Control: no-cache, no-store, must-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    }
    
    /**
     * Set headers for API responses
     * Provides security headers optimized for API endpoints
     * 
     * @return void
     */
    public static function setAPIHeaders(): void {
        // JSON content type
        header('Content-Type: application/json; charset=utf-8');
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Prevent caching of API responses
        self::preventCaching();
        
        // CORS headers (restrictive by default)
        header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400'); // 24 hours
    }
    
    /**
     * Set security headers for admin pages
     * Provides enhanced security for administrative interfaces
     * 
     * @return void
     */
    public static function setAdminHeaders(): void {
        // Extra strict headers for admin pages
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: no-referrer');
        
        // Prevent caching of admin pages
        self::preventCaching();
        
        // Strict CSP for admin pages
        $adminCSP = [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline'", // More restrictive for admin
            'style-src' => "'self' 'unsafe-inline'",
            'img-src' => "'self' data:",
            'font-src' => "'self'",
            'connect-src' => "'self'",
            'object-src' => "'none'",
            'frame-ancestors' => "'none'",
            'form-action' => "'self'",
            'base-uri' => "'self'"
        ];
        
        self::setCSPHeaders($adminCSP);
        
        // HSTS for admin if HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            self::setHSTSHeaders();
        }
    }
}