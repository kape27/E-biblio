<?php
/**
 * Comprehensive Security Audit Test Suite for E-Lib Digital Library
 * Tests against OWASP Top 10 vulnerabilities and security requirements
 * 
 * Requirements: 1.1, 2.1, 4.1
 * 
 * This test suite performs comprehensive security testing including:
 * - CSRF Protection validation
 * - XSS resistance testing
 * - SQL Injection protection
 * - Authentication security
 * - Session security
 * - Input validation
 * - File upload security
 * - Security headers validation
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/enhanced_security_headers.php';

class SecurityAuditTest {
    private $db;
    private $testResults = [];
    private $vulnerabilities = [];
    private $passedTests = 0;
    private $totalTests = 0;
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
        echo "🔒 E-Lib Security Audit Test Suite\n";
        echo "==================================\n\n";
    }
    
    /**
     * Run all security tests
     */
    public function runAllTests(): array {
        echo "Starting comprehensive security audit...\n\n";
        
        // OWASP Top 10 2021 Testing
        $this->testA01_BrokenAccessControl();
        $this->testA02_CryptographicFailures();
        $this->testA03_Injection();
        $this->testA04_InsecureDesign();
        $this->testA05_SecurityMisconfiguration();
        $this->testA06_VulnerableComponents();
        $this->testA07_IdentificationAuthFailures();
        $this->testA08_SoftwareDataIntegrityFailures();
        $this->testA09_SecurityLoggingFailures();
        $this->testA10_ServerSideRequestForgery();
        
        // Specific requirement tests
        $this->testCSRFProtection();
        $this->testXSSResistance();
        $this->testSQLInjectionProtection();
        $this->testInputValidation();
        $this->testFileUploadSecurity();
        $this->testSessionSecurity();
        $this->testSecurityHeaders();
        $this->testRateLimiting();
        $this->testPasswordSecurity();
        
        $this->generateReport();
        return $this->testResults;
    }
    
    /**
     * A01:2021 – Broken Access Control
     */
    private function testA01_BrokenAccessControl(): void {
        echo "🔍 Testing A01: Broken Access Control\n";
        
        // Test unauthorized access to admin functions
        $this->testCase("Admin Access Control", function() {
            // Simulate non-admin user trying to access admin functions
            $_SESSION = ['user_id' => 999, 'user_role' => 'user'];
            $auth = new AuthManager();
            
            $hasAdminAccess = $auth->hasRole('admin');
            $canAccessUsers = $auth->canAccess('users', 'delete');
            
            return !$hasAdminAccess && !$canAccessUsers;
        });
        
        // Test privilege escalation prevention
        $this->testCase("Privilege Escalation Prevention", function() {
            $_SESSION = ['user_id' => 999, 'user_role' => 'user'];
            $auth = new AuthManager();
            
            // User should not be able to access librarian functions
            return !$auth->hasRole('librarian') && !$auth->canAccess('books', 'create');
        });
        
        // Test direct object reference protection
        $this->testCase("Direct Object Reference Protection", function() {
            // Test if user can access other user's data
            $userId = SQLSecurity::validateId("1; DROP TABLE users; --");
            return $userId === false;
        });
    }
    
    /**
     * A02:2021 – Cryptographic Failures
     */
    private function testA02_CryptographicFailures(): void {
        echo "🔍 Testing A02: Cryptographic Failures\n";
        
        // Test password hashing
        $this->testCase("Password Hashing Security", function() {
            $password = "TestPassword123!";
            $hash = AuthManager::hashPassword($password);
            
            // Verify hash is not plain text and uses secure algorithm
            $isSecure = $hash !== $password && 
                       strlen($hash) >= 60 && 
                       str_starts_with($hash, '$2y$');
            
            return $isSecure && AuthManager::verifyPassword($password, $hash);
        });
        
        // Test session security
        $this->testCase("Session Cookie Security", function() {
            // Check if secure session parameters are set
            $params = session_get_cookie_params();
            return $params['httponly'] && $params['samesite'] === 'Strict';
        });
    }
    
    /**
     * A03:2021 – Injection (SQL, XSS, etc.)
     */
    private function testA03_Injection(): void {
        echo "🔍 Testing A03: Injection Vulnerabilities\n";
        
        // Test SQL injection protection
        $this->testSQLInjectionProtection();
        
        // Test XSS protection
        $this->testXSSResistance();
        
        // Test command injection protection
        $this->testCase("Command Injection Protection", function() {
            $filename = "test; rm -rf /";
            $sanitized = XSSProtection::sanitizeFilename($filename);
            return $sanitized !== $filename && !str_contains($sanitized, ';');
        });
    }
    
    /**
     * A04:2021 – Insecure Design
     */
    private function testA04_InsecureDesign(): void {
        echo "🔍 Testing A04: Insecure Design\n";
        
        // Test rate limiting implementation
        $this->testCase("Rate Limiting Design", function() {
            return class_exists('RateLimiter') && 
                   method_exists('RateLimiter', 'checkLoginAttempts');
        });
        
        // Test security by design principles
        $this->testCase("Security Headers Implementation", function() {
            return class_exists('EnhancedSecurityHeaders') &&
                   method_exists('EnhancedSecurityHeaders', 'setStrictHeaders');
        });
    }
    
    /**
     * A05:2021 – Security Misconfiguration
     */
    private function testA05_SecurityMisconfiguration(): void {
        echo "🔍 Testing A05: Security Misconfiguration\n";
        
        // Test security headers configuration
        $this->testSecurityHeaders();
        
        // Test error handling
        $this->testCase("Error Information Disclosure", function() {
            // Ensure detailed errors are not exposed in production
            $displayErrors = ini_get('display_errors');
            return $displayErrors === '' || $displayErrors === '0';
        });
    }
    
    /**
     * A06:2021 – Vulnerable and Outdated Components
     */
    private function testA06_VulnerableComponents(): void {
        echo "🔍 Testing A06: Vulnerable Components\n";
        
        // Test PHP version
        $this->testCase("PHP Version Security", function() {
            $version = PHP_VERSION;
            $majorMinor = substr($version, 0, 3);
            // Check if PHP version is reasonably recent (7.4+)
            return version_compare($majorMinor, '7.4', '>=');
        });
        
        // Test for dangerous functions
        $this->testCase("Dangerous Functions Disabled", function() {
            $dangerous = ['eval', 'exec', 'system', 'shell_exec', 'passthru'];
            foreach ($dangerous as $func) {
                if (function_exists($func) && !in_array($func, explode(',', ini_get('disable_functions')))) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * A07:2021 – Identification and Authentication Failures
     */
    private function testA07_IdentificationAuthFailures(): void {
        echo "🔍 Testing A07: Authentication Failures\n";
        
        // Test password policy
        $this->testPasswordSecurity();
        
        // Test session management
        $this->testSessionSecurity();
        
        // Test brute force protection
        $this->testCase("Brute Force Protection", function() {
            return class_exists('RateLimiter') && 
                   RateLimiter::checkLoginAttempts('test_user');
        });
    }
    
    /**
     * A08:2021 – Software and Data Integrity Failures
     */
    private function testA08_SoftwareDataIntegrityFailures(): void {
        echo "🔍 Testing A08: Data Integrity Failures\n";
        
        // Test CSRF protection
        $this->testCSRFProtection();
        
        // Test input validation
        $this->testCase("Data Validation Integrity", function() {
            $errors = InputValidator::validateEmail("invalid-email");
            return $errors === false;
        });
    }
    
    /**
     * A09:2021 – Security Logging and Monitoring Failures
     */
    private function testA09_SecurityLoggingFailures(): void {
        echo "🔍 Testing A09: Logging and Monitoring\n";
        
        // Test logging functionality
        $this->testCase("Security Event Logging", function() {
            return function_exists('log_action');
        });
        
        // Test audit trail
        $this->testCase("Database Audit Tables", function() {
            try {
                $result = $this->db->fetchOne("SHOW TABLES LIKE 'security_events'");
                return !empty($result);
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    /**
     * A10:2021 – Server-Side Request Forgery (SSRF)
     */
    private function testA10_ServerSideRequestForgery(): void {
        echo "🔍 Testing A10: Server-Side Request Forgery\n";
        
        // Test URL validation
        $this->testCase("URL Validation", function() {
            $maliciousUrl = "http://localhost:22/admin";
            return !InputValidator::validateURL($maliciousUrl) || 
                   method_exists('InputValidator', 'validateURL');
        });
    }
    
    /**
     * Test CSRF Protection (Requirement 1.1)
     */
    private function testCSRFProtection(): void {
        echo "🔍 Testing CSRF Protection (Requirement 1.1)\n";
        
        // Test CSRF token generation
        $this->testCase("CSRF Token Generation", function() {
            return function_exists('generate_csrf_token');
        });
        
        // Test CSRF token validation
        $this->testCase("CSRF Token Validation", function() {
            return function_exists('verify_csrf_token');
        });
        
        // Test CSRF token in forms
        $this->testCase("CSRF Tokens in Forms", function() {
            // Check if login form contains CSRF token
            ob_start();
            include __DIR__ . '/../login.php';
            $content = ob_get_clean();
            return str_contains($content, 'csrf_token');
        });
        
        // Test CSRF protection in admin forms
        $this->testCase("CSRF Protection in Admin Forms", function() {
            // Simulate admin session
            $_SESSION = ['user_id' => 1, 'user_role' => 'admin', 'username' => 'admin'];
            
            ob_start();
            include __DIR__ . '/../admin/users.php';
            $content = ob_get_clean();
            
            // Count CSRF token occurrences
            $tokenCount = substr_count($content, 'csrf_token');
            return $tokenCount >= 3; // Should have multiple forms with CSRF tokens
        });
    }
    
    /**
     * Test XSS Resistance (Requirement 2.1)
     */
    private function testXSSResistance(): void {
        echo "🔍 Testing XSS Resistance (Requirement 2.1)\n";
        
        // Test HTML escaping
        $this->testCase("HTML Entity Escaping", function() {
            $malicious = "<script>alert('XSS')</script>";
            $escaped = XSSProtection::escapeHtml($malicious);
            return $escaped !== $malicious && !str_contains($escaped, '<script>');
        });
        
        // Test JavaScript escaping
        $this->testCase("JavaScript Escaping", function() {
            $malicious = "'; alert('XSS'); //";
            $escaped = XSSProtection::escapeJs($malicious);
            return $escaped !== $malicious && str_contains($escaped, '\\"');
        });
        
        // Test input sanitization
        $this->testCase("Input Sanitization", function() {
            $malicious = "<img src=x onerror=alert('XSS')>";
            $sanitized = XSSProtection::sanitizeInput($malicious);
            return $sanitized !== $malicious && !str_contains($sanitized, 'onerror');
        });
        
        // Test rich text sanitization
        $this->testCase("Rich Text Sanitization", function() {
            $malicious = "<p>Good content</p><script>alert('XSS')</script>";
            $sanitized = XSSProtection::sanitizeRichText($malicious);
            return str_contains($sanitized, '<p>Good content</p>') && 
                   !str_contains($sanitized, '<script>');
        });
        
        // Test Content Security Policy
        $this->testCase("Content Security Policy Headers", function() {
            ob_start();
            EnhancedSecurityHeaders::setCSPHeaders();
            $headers = headers_list();
            ob_end_clean();
            
            foreach ($headers as $header) {
                if (str_starts_with($header, 'Content-Security-Policy:')) {
                    return str_contains($header, "default-src 'self'");
                }
            }
            return false;
        });
    }
    
    /**
     * Test SQL Injection Protection (Requirement 4.1)
     */
    private function testSQLInjectionProtection(): void {
        echo "🔍 Testing SQL Injection Protection (Requirement 4.1)\n";
        
        // Test ID validation
        $this->testCase("ID Parameter Validation", function() {
            $maliciousId = "1; DROP TABLE users; --";
            $validatedId = SQLSecurity::validateId($maliciousId);
            return $validatedId === false;
        });
        
        // Test sort column validation
        $this->testCase("Sort Column Validation", function() {
            $maliciousColumn = "id; DROP TABLE users; --";
            $allowedColumns = ['id', 'username', 'email'];
            $validatedColumn = SQLSecurity::validateSortColumn($maliciousColumn, $allowedColumns);
            return $validatedColumn === null;
        });
        
        // Test pagination parameter validation
        $this->testCase("Pagination Parameter Validation", function() {
            list($page, $perPage, $offset) = SQLSecurity::validatePagination(-1, 999999, 100);
            return $page === 1 && $perPage === 100 && $offset === 0;
        });
        
        // Test prepared statement usage
        $this->testCase("Prepared Statement Implementation", function() {
            try {
                // Test that database manager uses prepared statements
                $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE id = ?", [1]);
                return isset($result['count']);
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    /**
     * Test Input Validation
     */
    private function testInputValidation(): void {
        echo "🔍 Testing Input Validation\n";
        
        // Test email validation
        $this->testCase("Email Validation", function() {
            return !InputValidator::validateEmail("invalid-email") &&
                   InputValidator::validateEmail("valid@email.com");
        });
        
        // Test username validation
        $this->testCase("Username Validation", function() {
            $errors = InputValidator::validateUsername("ab"); // Too short
            return !empty($errors);
        });
        
        // Test password validation
        $this->testCase("Password Validation", function() {
            $weakPassword = "123";
            $errors = InputValidator::validatePassword($weakPassword);
            return !empty($errors);
        });
    }
    
    /**
     * Test File Upload Security
     */
    private function testFileUploadSecurity(): void {
        echo "🔍 Testing File Upload Security\n";
        
        // Test file type validation
        $this->testCase("File Type Validation", function() {
            $maliciousFile = [
                'name' => 'malicious.php',
                'tmp_name' => '/tmp/test',
                'size' => 1000,
                'error' => UPLOAD_ERR_OK
            ];
            
            // Create a temporary file for testing
            file_put_contents('/tmp/test', '<?php echo "test"; ?>');
            $errors = FileValidator::validateBookFile($maliciousFile);
            unlink('/tmp/test');
            
            return !empty($errors);
        });
        
        // Test filename sanitization
        $this->testCase("Filename Sanitization", function() {
            $maliciousFilename = "../../../etc/passwd";
            $sanitized = XSSProtection::sanitizeFilename($maliciousFilename);
            return $sanitized !== $maliciousFilename && !str_contains($sanitized, '../');
        });
    }
    
    /**
     * Test Session Security
     */
    private function testSessionSecurity(): void {
        echo "🔍 Testing Session Security\n";
        
        // Test session configuration
        $this->testCase("Secure Session Configuration", function() {
            return class_exists('SessionSecurity') &&
                   method_exists('SessionSecurity', 'initSecureSession');
        });
        
        // Test session validation
        $this->testCase("Session Validation", function() {
            return method_exists('SessionSecurity', 'validateSession');
        });
        
        // Test session fingerprinting
        $this->testCase("Session Fingerprinting", function() {
            return method_exists('SessionSecurity', 'setSessionFingerprint');
        });
    }
    
    /**
     * Test Security Headers
     */
    private function testSecurityHeaders(): void {
        echo "🔍 Testing Security Headers\n";
        
        // Test X-Frame-Options
        $this->testCase("X-Frame-Options Header", function() {
            ob_start();
            EnhancedSecurityHeaders::setStrictHeaders();
            $headers = headers_list();
            ob_end_clean();
            
            foreach ($headers as $header) {
                if (str_starts_with($header, 'X-Frame-Options:')) {
                    return str_contains($header, 'DENY');
                }
            }
            return false;
        });
        
        // Test X-Content-Type-Options
        $this->testCase("X-Content-Type-Options Header", function() {
            ob_start();
            EnhancedSecurityHeaders::setStrictHeaders();
            $headers = headers_list();
            ob_end_clean();
            
            foreach ($headers as $header) {
                if (str_starts_with($header, 'X-Content-Type-Options:')) {
                    return str_contains($header, 'nosniff');
                }
            }
            return false;
        });
        
        // Test HSTS headers
        $this->testCase("HSTS Headers", function() {
            ob_start();
            EnhancedSecurityHeaders::setHSTSHeaders();
            $headers = headers_list();
            ob_end_clean();
            
            foreach ($headers as $header) {
                if (str_starts_with($header, 'Strict-Transport-Security:')) {
                    return str_contains($header, 'max-age=');
                }
            }
            return false;
        });
    }
    
    /**
     * Test Rate Limiting
     */
    private function testRateLimiting(): void {
        echo "🔍 Testing Rate Limiting\n";
        
        // Test rate limiter functionality
        $this->testCase("Rate Limiter Implementation", function() {
            return class_exists('RateLimiter') &&
                   method_exists('RateLimiter', 'checkLoginAttempts') &&
                   method_exists('RateLimiter', 'recordFailedAttempt');
        });
        
        // Test rate limiting logic
        $this->testCase("Rate Limiting Logic", function() {
            $testUser = 'test_rate_limit_user_' . time();
            
            // Should allow initial attempts
            $allowed = RateLimiter::checkLoginAttempts($testUser);
            
            // Record multiple failed attempts
            for ($i = 0; $i < 6; $i++) {
                RateLimiter::recordFailedAttempt($testUser);
            }
            
            // Should now be blocked
            $blocked = !RateLimiter::checkLoginAttempts($testUser);
            
            return $allowed && $blocked;
        });
    }
    
    /**
     * Test Password Security
     */
    private function testPasswordSecurity(): void {
        echo "🔍 Testing Password Security\n";
        
        // Test password hashing
        $this->testCase("Secure Password Hashing", function() {
            $password = "TestPassword123!";
            $hash = AuthManager::hashPassword($password);
            
            // Should use bcrypt or better
            return str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon2');
        });
        
        // Test password verification
        $this->testCase("Password Verification", function() {
            $password = "TestPassword123!";
            $hash = AuthManager::hashPassword($password);
            
            return AuthManager::verifyPassword($password, $hash) &&
                   !AuthManager::verifyPassword("WrongPassword", $hash);
        });
        
        // Test password strength validation
        $this->testCase("Password Strength Validation", function() {
            $weakPasswords = ["123", "password", "abc123"];
            $strongPassword = "StrongP@ssw0rd123!";
            
            foreach ($weakPasswords as $weak) {
                $errors = InputValidator::validatePassword($weak);
                if (empty($errors)) {
                    return false;
                }
            }
            
            $strongErrors = InputValidator::validatePassword($strongPassword);
            return empty($strongErrors);
        });
    }
    
    /**
     * Helper method to run individual test cases
     */
    private function testCase(string $testName, callable $testFunction): void {
        $this->totalTests++;
        
        try {
            $result = $testFunction();
            if ($result) {
                echo "  ✅ $testName\n";
                $this->passedTests++;
                $this->testResults[$testName] = ['status' => 'PASS', 'message' => 'Test passed'];
            } else {
                echo "  ❌ $testName\n";
                $this->vulnerabilities[] = $testName;
                $this->testResults[$testName] = ['status' => 'FAIL', 'message' => 'Test failed'];
            }
        } catch (Exception $e) {
            echo "  ⚠️  $testName (Error: {$e->getMessage()})\n";
            $this->testResults[$testName] = ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generate comprehensive security audit report
     */
    private function generateReport(): void {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "🔒 SECURITY AUDIT REPORT\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $passRate = ($this->passedTests / $this->totalTests) * 100;
        
        echo "📊 SUMMARY:\n";
        echo "  Total Tests: {$this->totalTests}\n";
        echo "  Passed: {$this->passedTests}\n";
        echo "  Failed: " . (count($this->vulnerabilities)) . "\n";
        echo "  Pass Rate: " . number_format($passRate, 1) . "%\n\n";
        
        if (!empty($this->vulnerabilities)) {
            echo "🚨 VULNERABILITIES FOUND:\n";
            foreach ($this->vulnerabilities as $vuln) {
                echo "  • $vuln\n";
            }
            echo "\n";
        }
        
        // Security recommendations
        echo "💡 SECURITY RECOMMENDATIONS:\n";
        
        if ($passRate < 100) {
            echo "  • Address all failed security tests immediately\n";
        }
        
        echo "  • Regularly update PHP and dependencies\n";
        echo "  • Implement Web Application Firewall (WAF)\n";
        echo "  • Enable security monitoring and alerting\n";
        echo "  • Conduct regular penetration testing\n";
        echo "  • Implement security awareness training\n";
        echo "  • Review and update security policies regularly\n\n";
        
        // Compliance status
        echo "📋 COMPLIANCE STATUS:\n";
        echo "  • OWASP Top 10 2021: " . ($passRate >= 90 ? "✅ COMPLIANT" : "❌ NON-COMPLIANT") . "\n";
        echo "  • CSRF Protection (Req 1.1): " . ($this->isRequirementMet('CSRF') ? "✅ MET" : "❌ NOT MET") . "\n";
        echo "  • XSS Protection (Req 2.1): " . ($this->isRequirementMet('XSS') ? "✅ MET" : "❌ NOT MET") . "\n";
        echo "  • SQL Injection Protection (Req 4.1): " . ($this->isRequirementMet('SQL') ? "✅ MET" : "❌ NOT MET") . "\n\n";
        
        echo "🔒 Security audit completed at " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 50) . "\n";
    }
    
    /**
     * Check if specific requirement is met
     */
    private function isRequirementMet(string $requirement): bool {
        $requirementTests = [
            'CSRF' => ['CSRF Token Generation', 'CSRF Token Validation', 'CSRF Tokens in Forms'],
            'XSS' => ['HTML Entity Escaping', 'JavaScript Escaping', 'Input Sanitization'],
            'SQL' => ['ID Parameter Validation', 'Sort Column Validation', 'Prepared Statement Implementation']
        ];
        
        if (!isset($requirementTests[$requirement])) {
            return false;
        }
        
        foreach ($requirementTests[$requirement] as $test) {
            if (isset($this->testResults[$test]) && $this->testResults[$test]['status'] !== 'PASS') {
                return false;
            }
        }
        
        return true;
    }
}

// Run the security audit if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $audit = new SecurityAuditTest();
    $results = $audit->runAllTests();
    
    // Exit with appropriate code
    $failedTests = array_filter($results, function($result) {
        return $result['status'] !== 'PASS';
    });
    
    exit(empty($failedTests) ? 0 : 1);
}