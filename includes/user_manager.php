<?php
/**
 * User Manager for E-Lib Digital Library
 * Handles user CRUD operations for administrators
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';

class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
    }
    
    /**
     * Get all users with optional filtering
     */
    public function getAllUsers(?string $role = null, ?string $search = null): array {
        $sql = "SELECT id, username, email, role, created_at, last_login, is_active FROM users WHERE 1=1";
        $params = [];
        
        if ($role && in_array($role, ['admin', 'librarian', 'user'])) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?array {
        $sql = "SELECT id, username, email, role, created_at, last_login, is_active FROM users WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Get user by username
     */
    public function getUserByUsername(string $username): ?array {
        $sql = "SELECT id, username, email, role, created_at, last_login, is_active FROM users WHERE username = ?";
        return $this->db->fetchOne($sql, [$username]);
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?array {
        $sql = "SELECT id, username, email, role, created_at, last_login, is_active FROM users WHERE email = ?";
        return $this->db->fetchOne($sql, [$email]);
    }
    
    /**
     * Create new user
     */
    public function createUser(array $data): array {
        $errors = $this->validateUserData($data, true);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            // Check if username already exists
            if ($this->getUserByUsername($data['username'])) {
                return ['success' => false, 'errors' => ['Ce nom d\'utilisateur existe déjà.']];
            }
            
            // Check if email already exists
            if ($this->getUserByEmail($data['email'])) {
                return ['success' => false, 'errors' => ['Cette adresse email existe déjà.']];
            }
            
            $passwordHash = AuthManager::hashPassword($data['password']);
            
            $sql = "INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)";
            $this->db->executeQuery($sql, [
                $data['username'],
                $data['email'],
                $passwordHash,
                $data['role'] ?? 'user',
                $data['is_active'] ?? 1
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Log the action
            log_action('user_created', "User {$data['username']} created with role {$data['role']}", $_SESSION['user_id'] ?? null);
            
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur lors de la création de l\'utilisateur.']];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser(int $id, array $data): array {
        $errors = $this->validateUserData($data, false);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $existingUser = $this->getUserById($id);
        if (!$existingUser) {
            return ['success' => false, 'errors' => ['Utilisateur non trouvé.']];
        }
        
        try {
            // Check if username is taken by another user
            $userWithUsername = $this->getUserByUsername($data['username']);
            if ($userWithUsername && $userWithUsername['id'] != $id) {
                return ['success' => false, 'errors' => ['Ce nom d\'utilisateur existe déjà.']];
            }
            
            // Check if email is taken by another user
            $userWithEmail = $this->getUserByEmail($data['email']);
            if ($userWithEmail && $userWithEmail['id'] != $id) {
                return ['success' => false, 'errors' => ['Cette adresse email existe déjà.']];
            }
            
            // Build update query
            $updates = ['username = ?', 'email = ?', 'role = ?', 'is_active = ?'];
            $params = [$data['username'], $data['email'], $data['role'], $data['is_active'] ?? 1];
            
            // Update password if provided
            if (!empty($data['password'])) {
                $updates[] = 'password_hash = ?';
                $params[] = AuthManager::hashPassword($data['password']);
            }
            
            $params[] = $id;
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $this->db->executeQuery($sql, $params);
            
            // Log the action
            log_action('user_updated', "User {$data['username']} (ID: {$id}) updated", $_SESSION['user_id'] ?? null);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur lors de la mise à jour de l\'utilisateur.']];
        }
    }
    
    /**
     * Update user role
     */
    public function updateUserRole(int $id, string $role): array {
        if (!in_array($role, ['admin', 'librarian', 'user'])) {
            return ['success' => false, 'errors' => ['Rôle invalide.']];
        }
        
        $user = $this->getUserById($id);
        if (!$user) {
            return ['success' => false, 'errors' => ['Utilisateur non trouvé.']];
        }
        
        try {
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $this->db->executeQuery($sql, [$role, $id]);
            
            log_action('role_changed', "User {$user['username']} role changed to {$role}", $_SESSION['user_id'] ?? null);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error updating user role: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur lors de la modification du rôle.']];
        }
    }
    
    /**
     * Toggle user active status
     */
    public function toggleUserStatus(int $id): array {
        $user = $this->getUserById($id);
        if (!$user) {
            return ['success' => false, 'errors' => ['Utilisateur non trouvé.']];
        }
        
        // Prevent deactivating own account
        if ($id == ($_SESSION['user_id'] ?? 0)) {
            return ['success' => false, 'errors' => ['Vous ne pouvez pas désactiver votre propre compte.']];
        }
        
        try {
            $newStatus = $user['is_active'] ? 0 : 1;
            $sql = "UPDATE users SET is_active = ? WHERE id = ?";
            $this->db->executeQuery($sql, [$newStatus, $id]);
            
            $action = $newStatus ? 'activated' : 'deactivated';
            log_action("user_{$action}", "User {$user['username']} {$action}", $_SESSION['user_id'] ?? null);
            
            return ['success' => true, 'is_active' => $newStatus];
        } catch (Exception $e) {
            error_log("Error toggling user status: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur lors de la modification du statut.']];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser(int $id): array {
        $user = $this->getUserById($id);
        if (!$user) {
            return ['success' => false, 'errors' => ['Utilisateur non trouvé.']];
        }
        
        // Prevent deleting own account
        if ($id == ($_SESSION['user_id'] ?? 0)) {
            return ['success' => false, 'errors' => ['Vous ne pouvez pas supprimer votre propre compte.']];
        }
        
        try {
            $sql = "DELETE FROM users WHERE id = ?";
            $this->db->executeQuery($sql, [$id]);
            
            log_action('user_deleted', "User {$user['username']} deleted", $_SESSION['user_id'] ?? null);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur lors de la suppression de l\'utilisateur.']];
        }
    }
    
    /**
     * Validate user data
     */
    private function validateUserData(array $data, bool $isNew): array {
        $errors = [];
        
        // Validate username
        $usernameErrors = InputValidator::validateUsername($data['username'] ?? '');
        $errors = array_merge($errors, $usernameErrors);
        
        // Validate email
        if (empty($data['email']) || !InputValidator::validateEmail($data['email'])) {
            $errors[] = 'Adresse email invalide.';
        }
        
        // Validate password (required for new users)
        if ($isNew && empty($data['password'])) {
            $errors[] = 'Le mot de passe est requis.';
        } elseif (!empty($data['password'])) {
            $passwordErrors = InputValidator::validatePassword($data['password']);
            $errors = array_merge($errors, $passwordErrors);
        }
        
        // Validate role
        if (!empty($data['role']) && !in_array($data['role'], ['admin', 'librarian', 'user'])) {
            $errors[] = 'Rôle invalide.';
        }
        
        return $errors;
    }
    
    /**
     * Update user profile (for users updating their own profile)
     */
    public function updateProfile(int $userId, array $data): array {
        $errors = [];
        
        $existingUser = $this->getUserById($userId);
        if (!$existingUser) {
            return ['success' => false, 'errors' => ['Utilisateur non trouvé.']];
        }
        
        // Validate email
        if (!empty($data['email'])) {
            if (!InputValidator::validateEmail($data['email'])) {
                $errors[] = 'Adresse email invalide.';
            } else {
                $userWithEmail = $this->getUserByEmail($data['email']);
                if ($userWithEmail && $userWithEmail['id'] != $userId) {
                    $errors[] = 'Cette adresse email est déjà utilisée.';
                }
            }
        }
        
        // Validate current password if changing password
        if (!empty($data['new_password'])) {
            if (empty($data['current_password'])) {
                $errors[] = 'Le mot de passe actuel est requis pour changer de mot de passe.';
            } else {
                // Verify current password
                $userWithPassword = $this->db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
                if (!$userWithPassword || !password_verify($data['current_password'], $userWithPassword['password_hash'])) {
                    $errors[] = 'Le mot de passe actuel est incorrect.';
                }
            }
            
            // Validate new password
            $passwordErrors = InputValidator::validatePassword($data['new_password']);
            $errors = array_merge($errors, $passwordErrors);
            
            // Confirm password match
            if ($data['new_password'] !== ($data['confirm_password'] ?? '')) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $updates = [];
            $params = [];
            
            // Update email if provided
            if (!empty($data['email'])) {
                $updates[] = 'email = ?';
                $params[] = $data['email'];
            }
            
            // Update password if provided
            if (!empty($data['new_password'])) {
                $updates[] = 'password_hash = ?';
                $params[] = AuthManager::hashPassword($data['new_password']);
            }
            
            if (empty($updates)) {
                return ['success' => false, 'errors' => ['Aucune modification à effectuer.']];
            }
            
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $this->db->executeQuery($sql, $params);
            
            // Update session email if changed
            if (!empty($data['email'])) {
                $_SESSION['email'] = $data['email'];
            }
            
            log_action('profile_updated', "User updated their profile", $userId);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur lors de la mise à jour du profil.']];
        }
    }
    
    /**
     * Get full user data including password hash for verification
     */
    public function getUserWithPassword(int $id): ?array {
        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Get user statistics with enhanced admin privileges information
     */
    public function getUserStatistics(): array {
        $stats = [];
        
        $stats['total'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
        $stats['active'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
        $stats['inactive'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 0")['count'];
        
        $byRole = $this->db->fetchAll("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $stats['by_role'] = [];
        foreach ($byRole as $row) {
            $stats['by_role'][$row['role']] = $row['count'];
        }
        
        $stats['recent_logins'] = $this->db->fetchAll("
            SELECT id, username, email, role, last_login 
            FROM users 
            WHERE last_login IS NOT NULL 
            ORDER BY last_login DESC 
            LIMIT 5
        ");
        
        // Enhanced statistics for admin privileges
        $stats['privilege_levels'] = [
            'super_admin' => $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE id = 1 AND is_active = 1")['count'],
            'admin' => $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1")['count'],
            'librarian' => $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'librarian' AND is_active = 1")['count'],
            'user' => $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND is_active = 1")['count']
        ];
        
        // Security statistics
        $stats['security'] = [
            'failed_logins_today' => $this->getFailedLoginsToday(),
            'active_sessions' => $this->getActiveSessionsCount(),
            'password_resets_this_week' => $this->getPasswordResetsThisWeek()
        ];
        
        return $stats;
    }
    
    /**
     * Get failed login attempts for today
     */
    private function getFailedLoginsToday(): int {
        try {
            $result = $this->db->fetchOne("
                SELECT COUNT(*) as count 
                FROM logs 
                WHERE action = 'login_failed' 
                AND DATE(created_at) = CURDATE()
            ");
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get active sessions count (placeholder - would need session tracking)
     */
    private function getActiveSessionsCount(): int {
        // This would require implementing session tracking in database
        // For now, return a placeholder
        return 0;
    }
    
    /**
     * Get password resets this week
     */
    private function getPasswordResetsThisWeek(): int {
        try {
            $result = $this->db->fetchOne("
                SELECT COUNT(*) as count 
                FROM logs 
                WHERE action LIKE '%password_reset%' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Check if user has enhanced admin privileges
     */
    public function hasEnhancedPrivileges(int $userId): array {
        $user = $this->getUserById($userId);
        if (!$user) {
            return ['level' => 'none', 'features' => []];
        }
        
        // Load privilege configuration
        $privilegeConfig = include __DIR__ . '/../config/admin_privileges.php';
        
        // Determine privilege level
        $level = 'user';
        if ($userId === 1 && $user['role'] === 'admin') {
            $level = 'super_admin';
        } elseif ($user['role'] === 'admin') {
            $level = 'admin';
        } elseif ($user['role'] === 'librarian') {
            $level = 'librarian';
        }
        
        return [
            'level' => $level,
            'level_info' => $privilegeConfig['privilege_levels'][$level] ?? [],
            'permissions' => $privilegeConfig['permissions'][$level] ?? [],
            'features' => $privilegeConfig['features'][$level] ?? []
        ];
    }
    
    /**
     * Get users with admin privileges
     */
    public function getAdminUsers(): array {
        $sql = "SELECT id, username, email, role, created_at, last_login, is_active 
                FROM users 
                WHERE role IN ('admin', 'librarian') 
                ORDER BY 
                    CASE 
                        WHEN id = 1 THEN 0 
                        WHEN role = 'admin' THEN 1 
                        WHEN role = 'librarian' THEN 2 
                    END, 
                    username";
        
        $users = $this->db->fetchAll($sql);
        
        // Add privilege information
        foreach ($users as &$user) {
            $user['privileges'] = $this->hasEnhancedPrivileges($user['id']);
        }
        
        return $users;
    }
}