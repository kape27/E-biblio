<?php
/**
 * E-Lib Digital Library - Admin Dashboard
 * Modern Dark Mode Glassmorphism UI
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/csrf_protection.php';

// Initialize CSRF protection
CSRFProtectionManager::initialize();

$auth = new AuthManager();
$auth->requireRole('admin');

$db = DatabaseManager::getInstance();

// Vérifier le statut des mises à jour
$updateStatus = ['current_version' => '1.0.0', 'updates_available' => false];
try {
    $versionResult = $db->fetchOne("SELECT version FROM database_versions ORDER BY applied_at DESC LIMIT 1");
    if ($versionResult) {
        $updateStatus['current_version'] = $versionResult['version'];
        // Vérifier s'il y a des mises à jour disponibles (versions > version actuelle)
        $availableVersions = ['1.1.0', '1.2.0', '1.3.0', '1.4.0', '1.5.0', '1.6.0'];
        foreach ($availableVersions as $version) {
            if (version_compare($version, $updateStatus['current_version'], '>')) {
                $updateStatus['updates_available'] = true;
                break;
            }
        }
    }
} catch (Exception $e) {
    // Table database_versions n'existe pas encore
    $updateStatus['updates_available'] = true;
}

$userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$bookCount = $db->fetchOne("SELECT COUNT(*) as count FROM books")['count'];
$categoryCount = $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'];
$usersByRole = $db->fetchAll("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$recentLogs = $db->fetchAll("SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 10");
$recentUsers = $db->fetchAll("SELECT id, username, email, role, created_at, last_login, is_active FROM users ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' },
                        accent: { 500: '#6366f1', 600: '#4f46e5', 400: '#818cf8' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); }
        .sidebar-link:hover { background: linear-gradient(90deg, rgba(239,68,68,0.2) 0%, transparent 100%); }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(239,68,68,0.3) 0%, transparent 100%); border-left: 3px solid #ef4444; }
    </style>
</head>
<body class="bg-dark-900 text-gray-100 min-h-screen">
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-dark-800 border-r border-white/5 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="text-xl font-bold text-white">Admin</span>
            </div>
        </div>
        
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="font-medium">Dashboard</span>
            </a>
            <a href="users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span class="font-medium">Utilisateurs</span>
            </a>
            <a href="logs.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="font-medium">Journaux</span>
            </a>
            <a href="books.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span class="font-medium">Livres</span>
            </a>
            <a href="categories.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span class="font-medium">Catégories</span>
            </a>
            <a href="advanced_admin.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span class="font-medium">Admin Avancé</span>
            </a>
        </nav>
        
        <!-- Admin Tools Section -->
        <div class="px-4 mt-4 border-t border-white/5 pt-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-4">Outils</h3>
            <a href="setup.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="font-medium">Setup</span>
            </a>
            <a href="diagnostic.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="font-medium">Diagnostic</span>
            </a>
        </div>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                    <span class="text-sm font-bold"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate"><?= escape_html($_SESSION['username']) ?></p>
                    <p class="text-xs text-gray-500">Administrateur</p>
                </div>
                <a href="../logout.php" class="p-2 text-gray-400 hover:text-red-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen">
        <header class="sticky top-0 z-30 glass border-b border-white/5">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-xl font-bold">Dashboard Administrateur</h1>
                <div></div>
            </div>
        </header>
        
        <div class="p-4 lg:p-8">
            <!-- Update Notification -->
            <?php if ($updateStatus['updates_available']): ?>
            <div class="mb-6 glass-card rounded-xl p-4 border-l-4 border-yellow-400">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-yellow-400/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-yellow-400">Mises à jour disponibles</h3>
                            <p class="text-sm text-gray-400">Version actuelle: <?= $updateStatus['current_version'] ?> - Des nouvelles fonctionnalités sont disponibles</p>
                        </div>
                    </div>
                    <a href="setup.php" class="bg-yellow-400 text-dark-900 px-4 py-2 rounded-lg font-medium hover:bg-yellow-300 transition-colors">
                        Mettre à jour
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="glass-card rounded-2xl p-6 hover:border-blue-500/30 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500/20 to-blue-600/20 flex items-center justify-center">
                            <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">Actif</span>
                    </div>
                    <p class="text-3xl font-bold"><?= $userCount ?></p>
                    <p class="text-gray-400 mt-1">Utilisateurs</p>
                    <a href="users.php" class="text-blue-400 text-sm mt-3 inline-block hover:underline">Gérer →</a>
                </div>
                
                <div class="glass-card rounded-2xl p-6 hover:border-green-500/30 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500/20 to-green-600/20 flex items-center justify-center">
                            <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold"><?= $bookCount ?></p>
                    <p class="text-gray-400 mt-1">Livres</p>
                    <a href="books.php" class="text-green-400 text-sm mt-3 inline-block hover:underline">Gérer →</a>
                </div>
                
                <div class="glass-card rounded-2xl p-6 hover:border-purple-500/30 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-600/20 flex items-center justify-center">
                            <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold"><?= $categoryCount ?></p>
                    <p class="text-gray-400 mt-1">Catégories</p>
                    <a href="categories.php" class="text-purple-400 text-sm mt-3 inline-block hover:underline">Gérer →</a>
                </div>
                
                <!-- Setup Card -->
                <div class="glass-card rounded-2xl p-6 hover:border-orange-500/30 transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500/20 to-orange-600/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <?php if ($updateStatus['updates_available']): ?>
                        <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full animate-pulse">Mises à jour</span>
                        <?php else: ?>
                        <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">À jour</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-lg font-bold text-orange-400">Setup</p>
                    <p class="text-gray-400 mt-1">v<?= $updateStatus['current_version'] ?></p>
                    <a href="setup.php" class="text-orange-400 text-sm mt-3 inline-flex items-center gap-1 hover:underline group-hover:gap-2 transition-all">
                        <span><?= $updateStatus['updates_available'] ? 'Mettre à jour' : 'Configurer' ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                </div>
            </div>

            <!-- Users by Role & Recent Users -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-white/5">
                        <h2 class="font-semibold">Utilisateurs par rôle</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <?php foreach ($usersByRole as $roleData): ?>
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?= match($roleData['role']) {
                                'admin' => 'bg-red-500/20 text-red-400',
                                'librarian' => 'bg-yellow-500/20 text-yellow-400',
                                default => 'bg-blue-500/20 text-blue-400'
                            } ?>"><?= ucfirst(escape_html($roleData['role'])) ?></span>
                            <span class="text-2xl font-bold"><?= $roleData['count'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-white/5">
                        <h2 class="font-semibold">Utilisateurs récents</h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <?php foreach ($recentUsers as $user): ?>
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 flex items-center justify-center text-xs font-bold">
                                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-sm"><?= escape_html($user['username']) ?></p>
                                    <p class="text-xs text-gray-500"><?= escape_html($user['email']) ?></p>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full <?= match($user['role']) {
                                'admin' => 'bg-red-500/20 text-red-400',
                                'librarian' => 'bg-yellow-500/20 text-yellow-400',
                                default => 'bg-blue-500/20 text-blue-400'
                            } ?>"><?= ucfirst($user['role']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Admin Tools Section -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Outils d'administration
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Setup & Updates -->
                    <a href="setup.php" class="glass-card rounded-xl p-4 hover:border-orange-500/30 transition-all duration-300 group">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500/20 to-orange-600/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-sm">Setup & Mises à jour</h3>
                                <p class="text-xs text-gray-400">Base de données</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Appliquer les mises à jour système et gérer la base de données</p>
                    </a>
                    
                    <!-- Diagnostic -->
                    <a href="diagnostic.php" class="glass-card rounded-xl p-4 hover:border-blue-500/30 transition-all duration-300 group">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500/20 to-blue-600/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-sm">Diagnostic</h3>
                                <p class="text-xs text-gray-400">Environnement</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Vérifier l'état du système et des extensions PHP</p>
                    </a>
                    
                    <!-- Extensions Check -->
                    <a href="check_extensions.php" class="glass-card rounded-xl p-4 hover:border-green-500/30 transition-all duration-300 group">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500/20 to-green-600/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-sm">Extensions PHP</h3>
                                <p class="text-xs text-gray-400">Configuration</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Vérifier et configurer les extensions PHP requises</p>
                    </a>
                    
                    <!-- System Info -->
                    <div class="glass-card rounded-xl p-4 border-gray-500/20">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-500/20 to-gray-600/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-sm">Système</h3>
                                <p class="text-xs text-gray-400">Informations</p>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-gray-500">PHP: <span class="text-gray-300"><?= PHP_VERSION ?></span></p>
                            <p class="text-xs text-gray-500">OS: <span class="text-gray-300"><?= PHP_OS ?></span></p>
                            <p class="text-xs text-gray-500">Serveur: <span class="text-gray-300"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
                    <h2 class="font-semibold">Activité récente</h2>
                    <a href="logs.php" class="text-accent-400 text-sm hover:underline">Voir tout →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-dark-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Utilisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Détails</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($recentLogs as $log): ?>
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 text-sm"><?= escape_html($log['username'] ?? 'Système') ?></td>
                                <td class="px-6 py-4"><span class="text-xs px-2 py-1 rounded-full <?= str_contains($log['action'], 'success') ? 'bg-green-500/20 text-green-400' : (str_contains($log['action'], 'failed') ? 'bg-red-500/20 text-red-400' : 'bg-gray-500/20 text-gray-400') ?>"><?= escape_html($log['action']) ?></span></td>
                                <td class="px-6 py-4 text-sm text-gray-400 max-w-xs truncate"><?= escape_html(substr($log['details'] ?? '', 0, 50)) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m H:i', strtotime($log['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('mobile-overlay').classList.toggle('hidden');
        }
    </script>
</body>
</html>
