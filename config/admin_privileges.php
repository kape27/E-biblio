<?php
/**
 * Admin Privileges Configuration for E-Lib Digital Library
 * Defines privilege levels and permissions for different admin roles
 */

return [
    // Privilege levels
    'privilege_levels' => [
        'super_admin' => [
            'name' => 'Super Administrateur',
            'description' => 'Accès complet au système avec privilèges d\'urgence',
            'color' => 'danger',
            'requirements' => ['user_id' => 1] // First admin user
        ],
        'admin' => [
            'name' => 'Administrateur',
            'description' => 'Accès administratif complet sauf actions d\'urgence',
            'color' => 'primary',
            'requirements' => ['role' => 'admin']
        ],
        'librarian' => [
            'name' => 'Bibliothécaire',
            'description' => 'Gestion des livres et catégories',
            'color' => 'warning',
            'requirements' => ['role' => 'librarian']
        ],
        'user' => [
            'name' => 'Utilisateur',
            'description' => 'Accès de base à la bibliothèque',
            'color' => 'info',
            'requirements' => ['role' => 'user']
        ]
    ],

    // Enhanced permissions matrix
    'permissions' => [
        'super_admin' => [
            // User management - full control
            'users' => [
                'view', 'create', 'edit', 'delete', 'impersonate', 
                'reset_password', 'bulk_actions', 'force_logout'
            ],
            
            // System administration - full control
            'system' => [
                'maintenance', 'diagnostics', 'performance', 'security',
                'backup', 'restore', 'reset', 'lockdown'
            ],
            
            // Database operations - full control
            'database' => [
                'backup', 'restore', 'optimize', 'repair', 'export', 
                'import', 'migrate', 'rollback'
            ],
            
            // Emergency actions - super admin only
            'emergency' => [
                'lockdown', 'reset_system', 'force_logout_all',
                'emergency_backup', 'system_recovery'
            ],
            
            // Logs and audit - full control
            'logs' => [
                'view', 'export', 'clear', 'analyze', 'archive'
            ],
            
            // All other resources - full access
            'books' => ['*'],
            'categories' => ['*'],
            'files' => ['*'],
            'reports' => ['*'],
            'analytics' => ['*'],
            'security' => ['*'],
            'api' => ['*'],
            'integrations' => ['*'],
            'themes' => ['*'],
            'plugins' => ['*']
        ],

        'admin' => [
            // User management - almost full control
            'users' => [
                'view', 'create', 'edit', 'delete', 'reset_password', 'bulk_actions'
                // No impersonate or force_logout
            ],
            
            // System administration - limited
            'system' => [
                'maintenance', 'diagnostics', 'performance', 'security'
                // No backup, restore, reset, lockdown
            ],
            
            // Database operations - limited
            'database' => [
                'backup', 'optimize', 'export'
                // No restore, repair, import, migrate, rollback
            ],
            
            // Logs and audit - limited
            'logs' => [
                'view', 'export', 'analyze'
                // No clear, archive
            ],
            
            // Content management - full control
            'books' => [
                'view', 'create', 'edit', 'delete', 'bulk_upload', 
                'bulk_edit', 'bulk_delete', 'export'
            ],
            'categories' => [
                'view', 'create', 'edit', 'delete', 'merge', 'bulk_actions'
            ],
            
            // File management - full control
            'files' => [
                'view', 'upload', 'delete', 'organize', 'cleanup', 'bulk_operations'
            ],
            
            // Reports and analytics - full access
            'reports' => ['view', 'generate', 'export', 'schedule'],
            'analytics' => ['view', 'detailed', 'export'],
            
            // Security - view only
            'security' => ['view_logs', 'manage_sessions'],
            
            // API and integrations - manage
            'api' => ['manage', 'keys', 'monitoring'],
            'integrations' => ['configure', 'manage']
        ],

        'librarian' => [
            // Limited user viewing
            'users' => ['view'],
            
            // Content management - full control
            'books' => [
                'view', 'create', 'edit', 'delete', 'bulk_upload'
            ],
            'categories' => [
                'view', 'create', 'edit', 'delete'
            ],
            
            // File management - limited
            'files' => ['view', 'upload', 'organize'],
            
            // Reports - basic access
            'reports' => ['view', 'generate'],
            
            // Catalog management
            'catalog' => ['view', 'manage']
        ],

        'user' => [
            // Basic access only
            'catalog' => ['view'],
            'books' => ['view', 'read', 'favorite'],
            'profile' => ['view', 'edit'],
            'history' => ['view', 'manage']
        ]
    ],

    // Feature flags for different privilege levels
    'features' => [
        'super_admin' => [
            'impersonation' => true,
            'emergency_actions' => true,
            'system_reset' => true,
            'force_logout_all' => true,
            'database_operations' => true,
            'log_management' => true,
            'security_override' => true
        ],
        'admin' => [
            'impersonation' => false,
            'emergency_actions' => false,
            'system_reset' => false,
            'force_logout_all' => false,
            'database_operations' => 'limited',
            'log_management' => 'limited',
            'security_override' => false
        ],
        'librarian' => [
            'impersonation' => false,
            'emergency_actions' => false,
            'system_reset' => false,
            'force_logout_all' => false,
            'database_operations' => false,
            'log_management' => false,
            'security_override' => false
        ],
        'user' => [
            'impersonation' => false,
            'emergency_actions' => false,
            'system_reset' => false,
            'force_logout_all' => false,
            'database_operations' => false,
            'log_management' => false,
            'security_override' => false
        ]
    ],

    // Security settings
    'security' => [
        'require_2fa_for_super_admin' => false, // Future feature
        'session_timeout_admin' => 7200, // 2 hours for admins
        'session_timeout_user' => 3600,  // 1 hour for users
        'max_failed_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'password_complexity' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false
        ]
    ],

    // Audit settings
    'audit' => [
        'log_all_admin_actions' => true,
        'log_user_actions' => false,
        'retain_logs_days' => 365,
        'sensitive_actions' => [
            'user_delete',
            'password_reset',
            'role_change',
            'system_backup',
            'database_restore',
            'emergency_action'
        ]
    ]
];