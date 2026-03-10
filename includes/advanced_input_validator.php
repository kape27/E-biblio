<?php
/**
 * Advanced Input Validator for E-Lib Digital Library
 * Enhanced input validation and sanitization with flexible rules
 * 
 * Requirements: 2.1, 2.2, 6.1, 6.3, 6.4
 */

class AdvancedInputValidator {
    
    // Default validation rules
    private const DEFAULT_RULES = [
        'required' => false,
        'type' => 'string',
        'min_length' => null,
        'max_length' => null,
        'pattern' => null,
        'sanitize' => true,
        'allow_html' => false,
        'allowed_tags' => ['p', 'br', 'b', 'i', 'u', 'strong', 'em'],
        'custom_validator' => null
    ];
    
    // File upload configuration
    private const FILE_UPLOAD_CONFIG = [
        'max_size' => 50 * 1024 * 1024, // 50MB default
        'allowed_types' => [],
        'allowed_extensions' => [],
        'check_mime' => true,
        'sanitize_filename' => true
    ];
    
    /**
     * Validate and sanitize data according to flexible rules
     * 
     * @param array $data Input data to validate
     * @param array $rules Validation rules for each field
     * @return array ['valid' => bool, 'data' => array, 'errors' => array]
     */
    public static function validateAndSanitize(array $data, array $rules): array {
        $result = [
            'valid' => true,
            'data' => [],
            'errors' => []
        ];
        
        foreach ($rules as $field => $fieldRules) {
            // Merge with default rules
            $fieldRules = array_merge(self::DEFAULT_RULES, $fieldRules);
            
            // Get field value
            $value = $data[$field] ?? null;
            
            // Check required fields
            if ($fieldRules['required'] && (is_null($value) || $value === '')) {
                $result['errors'][$field] = "Field '{$field}' is required";
                $result['valid'] = false;
                continue;
            }
            
            // Skip validation for empty non-required fields
            if (!$fieldRules['required'] && (is_null($value) || $value === '')) {
                $result['data'][$field] = $value;
                continue;
            }
            
            // Type validation and conversion
            $typeValidation = self::validateType($value, $fieldRules['type']);
            if (!$typeValidation['valid']) {
                $result['errors'][$field] = $typeValidation['error'];
                $result['valid'] = false;
                continue;
            }
            $value = $typeValidation['value'];
            
            // String-specific validations
            if ($fieldRules['type'] === 'string' && is_string($value)) {
                // Length validation
                if ($fieldRules['min_length'] !== null && strlen($value) < $fieldRules['min_length']) {
                    $result['errors'][$field] = "Field '{$field}' must be at least {$fieldRules['min_length']} characters";
                    $result['valid'] = false;
                    continue;
                }
                
                if ($fieldRules['max_length'] !== null && strlen($value) > $fieldRules['max_length']) {
                    $result['errors'][$field] = "Field '{$field}' must not exceed {$fieldRules['max_length']} characters";
                    $result['valid'] = false;
                    continue;
                }
                
                // Pattern validation
                if ($fieldRules['pattern'] !== null && !preg_match($fieldRules['pattern'], $value)) {
                    $result['errors'][$field] = "Field '{$field}' format is invalid";
                    $result['valid'] = false;
                    continue;
                }
                
                // Sanitization
                if ($fieldRules['sanitize']) {
                    if ($fieldRules['allow_html']) {
                        $value = self::sanitizeHTML($value, $fieldRules['allowed_tags']);
                    } else {
                        $value = self::sanitizeString($value);
                    }
                }
            }
            
            // Custom validator
            if ($fieldRules['custom_validator'] !== null && is_callable($fieldRules['custom_validator'])) {
                $customResult = call_user_func($fieldRules['custom_validator'], $value);
                if ($customResult !== true) {
                    $result['errors'][$field] = is_string($customResult) ? $customResult : "Field '{$field}' validation failed";
                    $result['valid'] = false;
                    continue;
                }
            }
            
            $result['data'][$field] = $value;
        }
        
        return $result;
    }
    
    /**
     * Sanitize HTML content with whitelist of allowed tags
     * 
     * @param string $input HTML input to sanitize
     * @param array $allowedTags Array of allowed HTML tags
     * @return string Sanitized HTML
     */
    public static function sanitizeHTML(string $input, array $allowedTags = ['p', 'br', 'b', 'i', 'u', 'strong', 'em']): string {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Build allowed tags string for strip_tags
        $allowedTagsStr = empty($allowedTags) ? '' : '<' . implode('><', $allowedTags) . '>';
        
        // Strip disallowed tags
        $input = strip_tags($input, $allowedTagsStr);
        
        // Remove dangerous attributes and event handlers
        $input = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        $input = preg_replace('/\s*on\w+\s*=\s*[^\s>]*/i', '', $input);
        
        // Remove javascript: and data: URLs
        $input = preg_replace('/\s*href\s*=\s*["\']?\s*javascript:/i', ' href="#"', $input);
        $input = preg_replace('/\s*src\s*=\s*["\']?\s*data:/i', ' src="#"', $input);
        
        // Remove style attributes that could contain malicious CSS
        $input = preg_replace('/\s*style\s*=\s*["\'][^"\']*["\']/i', '', $input);
        
        return trim($input);
    }
    
    /**
     * Validate file upload with comprehensive security checks
     * 
     * @param array $file $_FILES array element
     * @param array $config Upload configuration
     * @return array ['valid' => bool, 'errors' => array, 'sanitized_name' => string]
     */
    public static function validateFileUpload(array $file, array $config = []): array {
        $config = array_merge(self::FILE_UPLOAD_CONFIG, $config);
        $result = [
            'valid' => true,
            'errors' => [],
            'sanitized_name' => ''
        ];
        
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            
            $error = $errorMessages[$file['error']] ?? 'Unknown upload error';
            $result['errors'][] = $error;
            $result['valid'] = false;
            return $result;
        }
        
        // Check file size
        if ($file['size'] > $config['max_size']) {
            $result['errors'][] = 'File size exceeds maximum allowed size of ' . self::formatFileSize($config['max_size']);
            $result['valid'] = false;
        }
        
        // Check file extension
        if (!empty($config['allowed_extensions'])) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $config['allowed_extensions'])) {
                $result['errors'][] = 'File type not allowed. Allowed types: ' . implode(', ', $config['allowed_extensions']);
                $result['valid'] = false;
            }
        }
        
        // Check MIME type
        if ($config['check_mime'] && !empty($config['allowed_types'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $config['allowed_types'])) {
                $result['errors'][] = 'Invalid file format detected';
                $result['valid'] = false;
            }
        }
        
        // Additional security checks for images
        if (in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $result['errors'][] = 'File is not a valid image';
                $result['valid'] = false;
            }
        }
        
        // Sanitize filename
        if ($config['sanitize_filename']) {
            $result['sanitized_name'] = self::sanitizeFilename($file['name']);
        } else {
            $result['sanitized_name'] = $file['name'];
        }
        
        return $result;
    }
    
    /**
     * Sanitize filename to prevent directory traversal and other attacks
     * 
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    public static function sanitizeFilename(string $filename): string {
        // Remove path components (directory traversal protection)
        $filename = basename($filename);
        
        // Remove null bytes
        $filename = str_replace(chr(0), '', $filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[<>:"|?*]/', '_', $filename);
        
        // Replace multiple dots with single dot (prevent double extensions)
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Remove leading/trailing dots and spaces
        $filename = trim($filename, '. ');
        
        // Replace spaces and special characters with underscores
        $filename = preg_replace('/[^\w\-.]/', '_', $filename);
        
        // Prevent reserved Windows filenames
        $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        if (in_array(strtoupper($nameWithoutExt), $reservedNames)) {
            $filename = 'file_' . $filename;
        }
        
        // Limit filename length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($ext)) . '.' . $ext;
        }
        
        // Ensure filename is not empty
        if (empty($filename) || $filename === '.') {
            $filename = 'unnamed_file_' . time();
        }
        
        return $filename;
    }
    
    /**
     * Validate type and convert value
     * 
     * @param mixed $value Value to validate
     * @param string $type Expected type
     * @return array ['valid' => bool, 'value' => mixed, 'error' => string]
     */
    private static function validateType($value, string $type): array {
        switch ($type) {
            case 'string':
                if (!is_string($value) && !is_numeric($value)) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be a string'];
                }
                return ['valid' => true, 'value' => (string)$value, 'error' => ''];
                
            case 'int':
            case 'integer':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be an integer'];
                }
                $intValue = filter_var($value, FILTER_VALIDATE_INT);
                if ($intValue === false) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be a valid integer'];
                }
                return ['valid' => true, 'value' => $intValue, 'error' => ''];
                
            case 'float':
            case 'double':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be a number'];
                }
                $floatValue = filter_var($value, FILTER_VALIDATE_FLOAT);
                if ($floatValue === false) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be a valid number'];
                }
                return ['valid' => true, 'value' => $floatValue, 'error' => ''];
                
            case 'bool':
            case 'boolean':
                $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($boolValue === null) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be a boolean'];
                }
                return ['valid' => true, 'value' => $boolValue, 'error' => ''];
                
            case 'email':
                $emailValue = filter_var($value, FILTER_VALIDATE_EMAIL);
                if ($emailValue === false) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be a valid email address'];
                }
                return ['valid' => true, 'value' => $emailValue, 'error' => ''];
                
            case 'url':
                $urlValue = filter_var($value, FILTER_VALIDATE_URL);
                if ($urlValue === false) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be a valid URL'];
                }
                return ['valid' => true, 'value' => $urlValue, 'error' => ''];
                
            case 'array':
                if (!is_array($value)) {
                    return ['valid' => false, 'value' => null, 'error' => 'Value must be an array'];
                }
                return ['valid' => true, 'value' => $value, 'error' => ''];
                
            default:
                return ['valid' => true, 'value' => $value, 'error' => ''];
        }
    }
    
    /**
     * Sanitize string input
     * 
     * @param string $input Input string
     * @return string Sanitized string
     */
    private static function sanitizeString(string $input): string {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Strip HTML tags
        $input = strip_tags($input);
        
        return $input;
    }
    
    /**
     * Format file size for display
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private static function formatFileSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Validate URL and sanitize it
     * 
     * @param string $url URL to validate
     * @return bool True if URL is valid
     */
    public static function validateURL(string $url): bool {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check for dangerous protocols
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme'])) {
            return false;
        }
        
        $allowedSchemes = ['http', 'https', 'ftp', 'ftps'];
        if (!in_array(strtolower($parsed['scheme']), $allowedSchemes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate CSRF token (delegates to CSRFProtectionManager)
     * 
     * @param string $token CSRF token to validate
     * @return bool True if token is valid
     */
    public static function validateCSRFToken(string $token): bool {
        if (!class_exists('CSRFProtectionManager')) {
            require_once __DIR__ . '/csrf_protection.php';
        }
        
        return CSRFProtectionManager::validateToken($token);
    }
    
    /**
     * Predefined validation rules for common use cases
     */
    
    /**
     * Get validation rules for user registration
     * 
     * @return array Validation rules
     */
    public static function getUserRegistrationRules(): array {
        return [
            'username' => [
                'required' => true,
                'type' => 'string',
                'min_length' => 3,
                'max_length' => 50,
                'pattern' => '/^[a-zA-Z0-9_]+$/',
                'sanitize' => true
            ],
            'email' => [
                'required' => true,
                'type' => 'email',
                'sanitize' => true
            ],
            'password' => [
                'required' => true,
                'type' => 'string',
                'min_length' => 8,
                'sanitize' => false,
                'custom_validator' => function($password) {
                    $errors = [];
                    if (!preg_match('/[A-Z]/', $password)) {
                        $errors[] = 'Password must contain at least one uppercase letter';
                    }
                    if (!preg_match('/[a-z]/', $password)) {
                        $errors[] = 'Password must contain at least one lowercase letter';
                    }
                    if (!preg_match('/[0-9]/', $password)) {
                        $errors[] = 'Password must contain at least one number';
                    }
                    return empty($errors) ? true : implode(', ', $errors);
                }
            ],
            'full_name' => [
                'required' => true,
                'type' => 'string',
                'min_length' => 2,
                'max_length' => 100,
                'sanitize' => true
            ]
        ];
    }
    
    /**
     * Get validation rules for book metadata
     * 
     * @return array Validation rules
     */
    public static function getBookMetadataRules(): array {
        return [
            'title' => [
                'required' => true,
                'type' => 'string',
                'min_length' => 1,
                'max_length' => 255,
                'sanitize' => true
            ],
            'author' => [
                'required' => true,
                'type' => 'string',
                'min_length' => 1,
                'max_length' => 255,
                'sanitize' => true
            ],
            'description' => [
                'required' => false,
                'type' => 'string',
                'max_length' => 2000,
                'allow_html' => true,
                'allowed_tags' => ['p', 'br', 'b', 'i', 'u', 'strong', 'em'],
                'sanitize' => true
            ],
            'category_id' => [
                'required' => true,
                'type' => 'integer',
                'custom_validator' => function($value) {
                    return $value > 0 ? true : 'Category must be selected';
                }
            ],
            'isbn' => [
                'required' => false,
                'type' => 'string',
                'pattern' => '/^(?:\d{10}|\d{13})$/',
                'sanitize' => true
            ]
        ];
    }
    
    /**
     * Get file upload configuration for books
     * 
     * @return array Upload configuration
     */
    public static function getBookUploadConfig(): array {
        return [
            'max_size' => 50 * 1024 * 1024, // 50MB
            'allowed_extensions' => ['pdf', 'epub'],
            'allowed_types' => ['application/pdf', 'application/epub+zip'],
            'check_mime' => true,
            'sanitize_filename' => true
        ];
    }
    
    /**
     * Get file upload configuration for images
     * 
     * @return array Upload configuration
     */
    public static function getImageUploadConfig(): array {
        return [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'check_mime' => true,
            'sanitize_filename' => true
        ];
    }
}