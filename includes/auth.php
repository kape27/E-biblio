<?php
/**
 * Authentication Manager for E-Lib Digital Library
 * Handles user authentication, session management, and access control
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';

class AuthManager {
    private $db;
    private const SESSION_TIMEOUT = 3600; // 1 hour session timeout
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
        $this->configureSession();
    }
    
    /**
     * Configure secure session settings
     */
    private function configureSession(): void {
        // Only configure if session not already started
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            
            // Set secure cookie if HTTPS
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
        }
        
        // Check session timeout
        $this->checkSessionTimeout();
    }
    
    /**
     * Check and handle session timeout
     */
    private function checkSessionTimeout(): void {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
                $this->logout();
                return;
            }
        }
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Authenticate user with username and password
     */
    public function login(string $username, string $password): bool {
        // Check rate limiting
        if (!RateLimiter::checkLoginAttempts($username)) {
            log_action('login_blocked', "Login blocked due to rate limiting for username: {$username}");
            return false;
        }
        
        try {
            $sql = "SELECT id, username, email, password_hash, role, is_active FROM users WHERE username = ? AND is_active = 1";
            $user = $this->db->fetchOne($sql, [$username]);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Reset rate limiter on successful login
                RateLimiter::resetAttempts($username);
                
                // Create session
                $this->createSession($user);
                
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Log successful login
                log_action('login_success', "User {$username} logged in successfully", $user['id']);
                
                return true;
            }
            
            // Record failed attempt for rate limiting
            RateLimiter::recordFailedAttempt($username);
            
            // Log failed login attempt
            log_action('login_failed', "Failed login attempt for username: {$username}");
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Authenticate user by email
     */
    public function loginByEmail(string $email, string $password): bool {
        // Check rate limiting
        if (!RateLimiter::checkLoginAttempts($email)) {
            log_action('login_blocked', "Login blocked due to rate limiting for email: {$email}");
            return false;
        }
        
        try {
            $sql = "SELECT id, username, email, password_hash, role, is_active FROM users WHERE email = ? AND is_active = 1";
            $user = $this->db->fetchOne($sql, [$email]);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                RateLimiter::resetAttempts($email);
                $this->createSession($user);
                $this->updateLastLogin($user['id']);
                log_action('login_success', "User {$user['username']} logged in via email", $user['id']);
                return true;
            }
            
            RateLimiter::recordFailedAttempt($email);
            log_action('login_failed', "Failed login attempt for email: {$email}");
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create user session
     */
    private function createSession(array $user): void {
        // Regenerate session ID first for security (prevents session fixation)
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Initialize CSRF protection for this session
        require_once __DIR__ . '/csrf_protection.php';
        CSRFProtectionManager::initialize();
    }
    
    /**
     * Update user's last login timestamp
     */
    private function updateLastLogin(int $userId): void {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $this->db->executeQuery($sql, [$userId]);
    }
    
    /**
     * Logout user and destroy session
     */
    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            log_action('logout', "User {$_SESSION['username']} logged out", $_SESSION['user_id']);
        }
        
        // Clear CSRF tokens
        require_once __DIR__ . '/csrf_protection.php';
        CSRFProtectionManager::clearAllTokens();
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Admin has access to all roles
        if ($userRole === 'admin') {
            return true;
        }
        
        // Librarian has access to librarian and user roles
        if ($userRole === 'librarian' && in_array($role, ['librarian', 'user'])) {
            return true;
        }
        
        // User only has access to user role
        return $userRole === $role;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser(): ?array {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $sql = "SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?";
            return $this->db->fetchOne($sql, [$_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Error getting current user: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public function getCurrentUserRole(): ?string {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    public function requireAuth(): void {
        if (!$this->isLoggedIn()) {
            // Store the requested URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
            redirect_with_message('../login.php', 'Veuillez vous connecter pour accéder à cette page.', 'warning');
        }
    }
    
    /**
     * Require specific role - redirect if user doesn't have required role
     */
    public function requireRole(string $role): void {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            log_action('access_denied', "User {$_SESSION['username']} attempted to access {$role} area", $_SESSION['user_id']);
            redirect_with_message('../index.php', 'Vous n\'avez pas la permission d\'accéder à cette page.', 'error');
        }
    }
    
    /**
     * Require one of multiple roles
     */
    public function requireAnyRole(array $roles): void {
        $this->requireAuth();
        
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return;
            }
        }
        
        log_action('access_denied', "User {$_SESSION['username']} attempted to access restricted area", $_SESSION['user_id']);
        redirect_with_message('../index.php', 'Vous n\'avez pas la permission d\'accéder à cette page.', 'error');
    }
    
    /**
     * Check if current user can perform action on resource
     */
    public function canAccess(string $resource, string $action = 'view'): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $this->getCurrentUserRole();
        
        // Define enhanced permissions matrix with extended admin privileges
        $permissions = [
            'admin' => [
                // User management - full control
                'users' => ['view', 'create', 'edit', 'delete', 'impersonate', 'reset_password', 'bulk_actions'],
                
                // Book management - full control
                'books' => ['view', 'create', 'edit', 'delete', 'bulk_upload', 'bulk_edit', 'bulk_delete', 'export'],
                
                // Category management - full control
                'categories' => ['view', 'create', 'edit', 'delete', 'merge', 'bulk_actions'],
                
                // System administration
                'logs' => ['view', 'export', 'clear', 'analyze'],
                'settings' => ['view', 'edit', 'backup', 'restore'],
                'system' => ['maintenance', 'diagnostics', 'performance', 'security'],
                
                // Database operations
                'database' => ['backup', 'restore', 'optimize', 'repair', 'export', 'import'],
                
                // File management
                'files' => ['view', 'upload', 'delete', 'organize', 'cleanup', 'bulk_operations'],
                
                // Statistics and reports
                'reports' => ['view', 'generate', 'export', 'schedule'],
                'analytics' => ['view', 'detailed', 'export'],
                
                // Security management
                'security' => ['view_logs', 'manage_sessions', 'ip_blocking', 'rate_limiting'],
                
                // Advanced features
                'api' => ['manage', 'keys', 'monitoring'],
                'integrations' => ['configure', 'manage'],
                'themes' => ['install', 'customize', 'manage'],
                'plugins' => ['install', 'configure', 'manage'],
                
                // Emergency actions
                'emergency' => ['lockdown', 'reset_system', 'force_logout_all']
            ],
            'librarian' => [
                'books' => ['view', 'create', 'edit', 'delete', 'bulk_upload'],
                'categories' => ['view', 'create', 'edit', 'delete'],
                'catalog' => ['view', 'manage'],
                'users' => ['view'], // Can view user list but not modify
                'reports' => ['view', 'generate'], // Limited reporting
            ],
            'user' => [
                'catalog' => ['view'],
                'books' => ['view', 'read', 'favorite'],
                'profile' => ['view', 'edit'],
                'history' => ['view', 'manage'],
            ],
        ];
        
        // Super admin check - if user ID is 1 (first admin), grant all permissions
        if ($this->getCurrentUserId() === 1) {
            return true;
        }
        
        // Admin has all permissions by default
        if ($role === 'admin') {
            return true;
        }
        
        // Check specific permission
        if (isset($permissions[$role][$resource])) {
            return in_array($action, $permissions[$role][$resource]);
        }
        
        return false;
    }
    
    /**
     * Check if user is super admin (first admin user)
     */
    public function isSuperAdmin(): bool {
        return $this->isLoggedIn() && $this->getCurrentUserId() === 1;
    }
    
    /**
     * Get available actions for a resource based on user role
     */
    public function getAvailableActions(string $resource): array {
        if (!$this->isLoggedIn()) {
            return [];
        }
        
        $role = $this->getCurrentUserRole();
        
        // Super admin gets all actions
        if ($this->isSuperAdmin()) {
            return $this->getAllPossibleActions($resource);
        }
        
        // Admin gets all actions except super admin exclusive ones
        if ($role === 'admin') {
            $actions = $this->getAllPossibleActions($resource);
            // Remove super admin exclusive actions
            $superAdminOnly = ['reset_system', 'force_logout_all', 'lockdown'];
            return array_diff($actions, $superAdminOnly);
        }
        
        // Define permissions matrix (same as above)
        $permissions = [
            'librarian' => [
                'books' => ['view', 'create', 'edit', 'delete', 'bulk_upload'],
                'categories' => ['view', 'create', 'edit', 'delete'],
                'catalog' => ['view', 'manage'],
                'users' => ['view'],
                'reports' => ['view', 'generate'],
            ],
            'user' => [
                'catalog' => ['view'],
                'books' => ['view', 'read', 'favorite'],
                'profile' => ['view', 'edit'],
                'history' => ['view', 'manage'],
            ],
        ];
        
        return $permissions[$role][$resource] ?? [];
    }
    
    /**
     * Get all possible actions for a resource
     */
    private function getAllPossibleActions(string $resource): array {
        $allActions = [
            'users' => ['view', 'create', 'edit', 'delete', 'impersonate', 'reset_password', 'bulk_actions'],
            'books' => ['view', 'create', 'edit', 'delete', 'bulk_upload', 'bulk_edit', 'bulk_delete', 'export'],
            'categories' => ['view', 'create', 'edit', 'delete', 'merge', 'bulk_actions'],
            'logs' => ['view', 'export', 'clear', 'analyze'],
            'settings' => ['view', 'edit', 'backup', 'restore'],
            'system' => ['maintenance', 'diagnostics', 'performance', 'security'],
            'database' => ['backup', 'restore', 'optimize', 'repair', 'export', 'import'],
            'files' => ['view', 'upload', 'delete', 'organize', 'cleanup', 'bulk_operations'],
            'reports' => ['view', 'generate', 'export', 'schedule'],
            'analytics' => ['view', 'detailed', 'export'],
            'security' => ['view_logs', 'manage_sessions', 'ip_blocking', 'rate_limiting'],
            'api' => ['manage', 'keys', 'monitoring'],
            'integrations' => ['configure', 'manage'],
            'themes' => ['install', 'customize', 'manage'],
            'plugins' => ['install', 'configure', 'manage'],
            'emergency' => ['lockdown', 'reset_system', 'force_logout_all']
        ];
        
        return $allActions[$resource] ?? [];
    }
    
    /**
     * Check if user can perform bulk operations
     */
    public function canPerformBulkOperations(string $resource): bool {
        return $this->canAccess($resource, 'bulk_actions') || 
               $this->canAccess($resource, 'bulk_operations');
    }
    
    /**
     * Check if user can access system administration features
     */
    public function canAccessSystemAdmin(): bool {
        return $this->hasRole('admin') && (
            $this->canAccess('system', 'maintenance') ||
            $this->canAccess('database', 'backup') ||
            $this->canAccess('security', 'view_logs')
        );
    }
    
    /**
     * Check if user can perform emergency actions
     */
    public function canPerformEmergencyActions(): bool {
        return $this->isSuperAdmin() || $this->canAccess('emergency', 'lockdown');
    }
    
    /**
     * Get redirect URL after login
     */
    public function getRedirectAfterLogin(): string {
        $redirect = $_SESSION['redirect_after_login'] ?? '';
        unset($_SESSION['redirect_after_login']);
        
        // Default redirects based on role
        if (empty($redirect)) {
            $role = $this->getCurrentUserRole();
            return match($role) {
                'admin' => 'admin/dashboard.php',
                'librarian' => 'librarian/dashboard.php',
                default => 'user/dashboard.php',
            };
        }
        
        return $redirect;
    }
    
    /**
     * Hash password using PHP's password_hash
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}