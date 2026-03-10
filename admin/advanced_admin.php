<?php
/**
 * Advanced Admin Panel for E-Lib Digital Library
 * Provides enhanced administrative tools and system management
 * Modern Dark Mode Glassmorphism UI - Consistent with site design
 */

require_once '../includes/auth.php';
require_once '../includes/admin_privileges.php';
require_once '../includes/user_manager.php';
require_once '../includes/functions.php';

$auth = new AuthManager();
$auth->requireRole('admin');

$adminPrivileges = new AdminPrivileges();
$currentUser = $auth->getCurrentUser();
$isSuperAdmin = $auth->isSuperAdmin();
$isImpersonating = $adminPrivileges->isImpersonating();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'reset_password':
                $userId = (int)$_POST['user_id'];
                $result = $adminPrivileges->resetUserPassword($userId);
                if ($result['success']) {
                    $message = "Mot de passe réinitialisé. Nouveau mot de passe : " . $result['new_password'];
                    $messageType = 'success';
                } else {
                    $message = implode(', ', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'impersonate':
                if ($isSuperAdmin) {
                    $userId = (int)$_POST['user_id'];
                    $result = $adminPrivileges->impersonateUser($userId);
                    if ($result['success']) {
                        header('Location: ../user/dashboard.php');
                        exit;
                    } else {
                        $message = implode(', ', $result['errors']);
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'stop_impersonation':
                $result = $adminPrivileges->stopImpersonation();
                if ($result['success']) {
                    $message = $result['message'];
                    $messageType = 'success';
                } else {
                    $message = implode(', ', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'create_backup':
                $options = [
                    'include_data' => isset($_POST['include_data'])
                ];
                $result = $adminPrivileges->createSystemBackup($options);
                if ($result['success']) {
                    $message = "Sauvegarde créée : " . basename($result['backup_file']) . " (" . formatFileSize($result['size']) . ")";
                    $messageType = 'success';
                } else {
                    $message = implode(', ', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'clear_logs':
                if ($isSuperAdmin) {
                    $options = [];
                    if (!empty($_POST['older_than_days'])) {
                        $options['older_than_days'] = (int)$_POST['older_than_days'];
                    }
                    $result = $adminPrivileges->clearSystemLogs($options);
                    if ($result['success']) {
                        $message = $result['message'];
                        $messageType = 'success';
                    } else {
                        $message = implode(', ', $result['errors']);
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'force_logout_all':
                if ($isSuperAdmin) {
                    $result = $adminPrivileges->forceLogoutAllUsers();
                    if ($result['success']) {
                        $message = $result['message'];
                        $messageType = 'success';
                    } else {
                        $message = implode(', ', $result['errors']);
                        $messageType = 'error';
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get system diagnostics
$diagnostics = $adminPrivileges->getSystemDiagnostics();

// Get users for management
$userManager = new UserManager();
$users = $userManager->getAllUsers();

// Get impersonation info
$impersonationInfo = $adminPrivileges->getImpersonationInfo();
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration Avancée - E-Lib</title>
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
            <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
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
            <a href="advanced_admin.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all duration-300">
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
                    <p class="text-xs text-gray-500">
                        <?php if ($isSuperAdmin): ?>
                            Super Administrateur
                        <?php else: ?>
                            Administrateur
                        <?php endif; ?>
                    </p>
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
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-bold">Administration Avancée</h1>
                    <?php if ($isSuperAdmin): ?>
                        <span class="px-3 py-1 text-xs font-medium bg-red-500/20 text-red-400 rounded-full">Super Admin</span>
                    <?php else: ?>
                        <span class="px-3 py-1 text-xs font-medium bg-accent-500/20 text-accent-400 rounded-full">Admin</span>
                    <?php endif; ?>
                </div>
                <div></div>
            </div>
        </header>
        
        <div class="p-4 lg:p-8">
            <?php if ($message): ?>
                <div class="mb-6 glass-card rounded-xl p-4 border-l-4 <?= $messageType === 'error' ? 'border-red-400' : 'border-green-400' ?>">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg <?= $messageType === 'error' ? 'bg-red-500/20' : 'bg-green-500/20' ?> flex items-center justify-center">
                            <?php if ($messageType === 'error'): ?>
                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <?php endif; ?>
                        </div>
                        <p class="<?= $messageType === 'error' ? 'text-red-400' : 'text-green-400' ?>"><?= htmlspecialchars($message) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isImpersonating): ?>
                <div class="mb-6 glass-card rounded-xl p-4 border-l-4 border-yellow-400">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-yellow-400/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-yellow-400">Mode Impersonation Actif</h3>
                                <p class="text-sm text-gray-400">Connecté en tant que <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> depuis <?= date('H:i:s', $impersonationInfo['started_at']) ?></p>
                            </div>
                        </div>
                        <form method="post" class="inline">
                            <input type="hidden" name="action" value="stop_impersonation">
                            <button type="submit" class="bg-yellow-400 text-dark-900 px-4 py-2 rounded-lg font-medium hover:bg-yellow-300 transition-colors">
                                Arrêter l'Impersonation
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Management Section -->
            <section class="mb-8">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Gestion Avancée des Utilisateurs
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-white/5">
                            <h3 class="font-semibold">Réinitialisation de Mot de Passe</h3>
                        </div>
                        <div class="p-6">
                            <form method="post" class="space-y-4">
                                <input type="hidden" name="action" value="reset_password">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Utilisateur</label>
                                    <select name="user_id" class="w-full bg-dark-700 border border-white/10 rounded-lg px-3 py-2 text-white focus:border-accent-400 focus:outline-none" required>
                                        <option value="">Sélectionner un utilisateur</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>">
                                                <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-dark-900 font-medium py-2 px-4 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    Réinitialiser le Mot de Passe
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($isSuperAdmin): ?>
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-white/5">
                            <h3 class="font-semibold">Impersonation d'Utilisateur</h3>
                        </div>
                        <div class="p-6">
                            <form method="post" class="space-y-4">
                                <input type="hidden" name="action" value="impersonate">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Utilisateur à Impersonner</label>
                                    <select name="user_id" class="w-full bg-dark-700 border border-white/10 rounded-lg px-3 py-2 text-white focus:border-accent-400 focus:outline-none" required>
                                        <option value="">Sélectionner un utilisateur</option>
                                        <?php foreach ($users as $user): ?>
                                            <?php if ($user['id'] != $currentUser['id']): ?>
                                                <option value="<?= $user['id'] ?>">
                                                    <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Se Connecter en Tant Que
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- System Management Section -->
            <section class="mb-8">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Gestion Système
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-white/5">
                            <h3 class="font-semibold">Sauvegarde Système</h3>
                        </div>
                        <div class="p-6">
                            <form method="post" class="space-y-4">
                                <input type="hidden" name="action" value="create_backup">
                                <div class="flex items-center">
                                    <input type="checkbox" name="include_data" id="include_data" checked class="rounded border-gray-300 text-accent-600 focus:ring-accent-500">
                                    <label for="include_data" class="ml-2 text-sm text-gray-300">
                                        Inclure les données (pas seulement la structure)
                                    </label>
                                </div>
                                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                                    Créer une Sauvegarde
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($isSuperAdmin): ?>
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-white/5">
                            <h3 class="font-semibold">Nettoyage des Logs</h3>
                        </div>
                        <div class="p-6">
                            <form method="post" class="space-y-4">
                                <input type="hidden" name="action" value="clear_logs">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Supprimer les logs plus anciens que (jours)</label>
                                    <input type="number" name="older_than_days" class="w-full bg-dark-700 border border-white/10 rounded-lg px-3 py-2 text-white focus:border-accent-400 focus:outline-none" min="1" placeholder="30">
                                    <p class="text-xs text-gray-500 mt-1">Laisser vide pour supprimer tous les logs</p>
                                </div>
                                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-dark-900 font-medium py-2 px-4 rounded-lg transition-colors" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ces logs ?')">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Nettoyer les Logs
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- System Diagnostics -->
            <section class="mb-8">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Diagnostics Système
                </h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="glass-card rounded-2xl p-6 text-center hover:border-blue-500/30 transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500/20 to-blue-600/20 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="font-semibold mb-2">PHP</h3>
                        <p class="text-2xl font-bold text-blue-400 mb-1"><?= $diagnostics['php']['version'] ?></p>
                        <p class="text-sm text-gray-400">Mémoire: <?= $diagnostics['php']['memory_limit'] ?></p>
                        <p class="text-sm text-gray-400">Upload: <?= $diagnostics['php']['upload_max_filesize'] ?></p>
                    </div>
                    
                    <div class="glass-card rounded-2xl p-6 text-center hover:border-green-500/30 transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500/20 to-green-600/20 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                        </div>
                        <h3 class="font-semibold mb-2">Base de Données</h3>
                        <p class="text-lg font-bold text-green-400 mb-1"><?= $diagnostics['database']['version'] ?></p>
                        <p class="text-sm text-gray-400">Taille: <?= $diagnostics['database']['size']['size_mb'] ?> MB</p>
                        <p class="text-sm text-gray-400">Tables: <?= $diagnostics['database']['tables'] ?></p>
                    </div>
                    
                    <div class="glass-card rounded-2xl p-6 text-center hover:border-purple-500/30 transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-600/20 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h3 class="font-semibold mb-2">Stockage</h3>
                        <?php if (isset($diagnostics['storage']['uploads_dir']['error'])): ?>
                            <p class="text-sm text-yellow-400">Erreur d'accès</p>
                        <?php else: ?>
                            <p class="text-lg font-bold text-purple-400 mb-1"><?= $diagnostics['storage']['uploads_dir']['size_mb'] ?> MB</p>
                            <p class="text-sm text-gray-400">Fichiers: <?= $diagnostics['storage']['uploads_dir']['files'] ?></p>
                            <p class="text-sm <?= $diagnostics['storage']['uploads_dir']['writable'] ? 'text-green-400' : 'text-red-400' ?>">
                                <?= $diagnostics['storage']['uploads_dir']['writable'] ? 'Écriture OK' : 'Pas d\'écriture' ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="glass-card rounded-2xl p-6 text-center hover:border-orange-500/30 transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500/20 to-orange-600/20 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <h3 class="font-semibold mb-2">Performance</h3>
                        <p class="text-lg font-bold text-orange-400 mb-1"><?= $diagnostics['performance']['memory_usage'] ?></p>
                        <p class="text-sm text-gray-400">Pic: <?= $diagnostics['performance']['memory_peak'] ?></p>
                        <p class="text-sm text-gray-400">Temps: <?= $diagnostics['performance']['execution_time'] ?></p>
                    </div>
                </div>
            </section>

            <?php if ($isSuperAdmin): ?>
            <!-- Emergency Actions -->
            <section class="mb-8">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2 text-red-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Actions d'Urgence
                </h2>
                <div class="glass-card rounded-xl p-4 border-l-4 border-red-400 mb-4">
                    <p class="text-red-400 font-medium">⚠️ Attention ! Ces actions sont irréversibles et peuvent affecter tous les utilisateurs du système.</p>
                </div>
                
                <div class="glass-card rounded-2xl overflow-hidden border border-red-500/20">
                    <div class="px-6 py-4 border-b border-red-500/20 bg-red-500/10">
                        <h3 class="font-semibold text-red-400">Déconnexion Forcée</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-400 mb-4">Déconnecter immédiatement tous les utilisateurs du système.</p>
                        <form method="post" class="inline">
                            <input type="hidden" name="action" value="force_logout_all">
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition-colors" onclick="return confirm('Êtes-vous sûr de vouloir déconnecter tous les utilisateurs ?')">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Déconnecter Tous les Utilisateurs
                            </button>
                        </form>
                    </div>
                </div>
            </section>
            <?php endif; ?>

        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('mobile-overlay').classList.toggle('hidden');
        }
        
        // Auto-refresh diagnostics every 30 seconds
        setInterval(function() {
            // Only refresh if no forms are being filled
            const forms = document.querySelectorAll('form');
            let hasActiveInput = false;
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input === document.activeElement) {
                        hasActiveInput = true;
                    }
                });
            });
            
            if (!hasActiveInput) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>