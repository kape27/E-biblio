<?php
/**
 * E-Lib Digital Library - User Profile
 * Modern Dark Mode Glassmorphism UI
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/user_manager.php';
require_once '../includes/advanced_input_validator.php';

$auth = new AuthManager();
$auth->requireRole('user');

$userManager = new UserManager();
$db = DatabaseManager::getInstance();
$userId = $_SESSION['user_id'];

$message = '';
$messageType = '';
$errors = [];

// Get current user data
$user = $userManager->getUserById($userId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Use AdvancedInputValidator for profile update validation
        $validationRules = [
            'email' => [
                'required' => true,
                'type' => 'email',
                'sanitize' => true
            ],
            'current_password' => [
                'required' => false,
                'type' => 'string',
                'sanitize' => false
            ],
            'new_password' => [
                'required' => false,
                'type' => 'string',
                'min_length' => 8,
                'sanitize' => false,
                'custom_validator' => function($password) {
                    if (empty($password)) return true; // Optional field
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
            'confirm_password' => [
                'required' => false,
                'type' => 'string',
                'sanitize' => false
            ]
        ];
        
        $validationResult = AdvancedInputValidator::validateAndSanitize($_POST, $validationRules);
        
        if (!$validationResult['valid']) {
            $errors = array_values($validationResult['errors']);
            $messageType = 'error';
        } else {
            $validatedData = $validationResult['data'];
            
            // Additional validation for password confirmation
            if (!empty($validatedData['new_password']) && $validatedData['new_password'] !== $validatedData['confirm_password']) {
                $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
                $messageType = 'error';
            } else {
                $result = $userManager->updateProfile($userId, $validatedData);
                
                if ($result['success']) {
                    $message = 'Profil mis à jour avec succès!';
                    $messageType = 'success';
                    // Refresh user data
                    $user = $userManager->getUserById($userId);
                } else {
                    $errors = $result['errors'];
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get reading statistics
$readingStats = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT book_id) as books_read,
        MAX(updated_at) as last_activity
    FROM reading_progress 
    WHERE user_id = ?
", [$userId]);

$booksRead = $readingStats['books_read'] ?? 0;
$lastActivity = $readingStats['last_activity'] ?? null;

// Get categories for sidebar
$categories = $db->fetchAll("
    SELECT c.*, COUNT(b.id) as book_count 
    FROM categories c 
    LEFT JOIN books b ON c.id = b.category_id 
    GROUP BY c.id 
    HAVING book_count > 0
    ORDER BY book_count DESC 
    LIMIT 5
");

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155', 600: '#475569' },
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
        .sidebar-link:hover { background: linear-gradient(90deg, rgba(99,102,241,0.2) 0%, transparent 100%); }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(99,102,241,0.3) 0%, transparent 100%); border-left: 3px solid #6366f1; }
        .glow { box-shadow: 0 0 20px rgba(99, 102, 241, 0.3); }
    </style>
</head>
<body class="bg-dark-900 text-gray-100 min-h-screen">
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-dark-800 border-r border-white/5 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-accent-500 to-accent-600 flex items-center justify-center glow">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <span class="text-xl font-bold bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">E-Lib</span>
            </div>
        </div>
        
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="font-medium">Accueil</span>
            </a>
            <a href="catalog.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="font-medium">Catalogue</span>
            </a>
            <a href="favorites.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <span class="font-medium">Favoris</span>
            </a>
            <a href="search.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="font-medium">Recherche</span>
            </a>
            <a href="profile.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="font-medium">Mon Profil</span>
            </a>
        </nav>
        
        <div class="px-4 mt-6">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-4">Catégories</h3>
            <div class="space-y-1">
                <?php foreach ($categories as $cat): ?>
                <a href="catalog.php?category=<?= $cat['id'] ?>" class="flex items-center justify-between px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-300">
                    <span class="text-sm"><?= escape_html($cat['name']) ?></span>
                    <span class="text-xs bg-dark-700 px-2 py-0.5 rounded-full"><?= $cat['book_count'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 flex items-center justify-center">
                    <span class="text-sm font-bold"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate"><?= escape_html($_SESSION['username']) ?></p>
                    <p class="text-xs text-gray-500">Lecteur</p>
                </div>
                <a href="../logout.php" class="p-2 text-gray-400 hover:text-red-400 transition-colors" title="Déconnexion">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <main class="lg:ml-64 min-h-screen">
        <header class="sticky top-0 z-30 glass border-b border-white/5">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-xl font-bold">Mon Profil</h1>
                <div></div>
            </div>
        </header>

        <div class="p-4 lg:p-8 max-w-4xl mx-auto">
            <?php if ($message): ?>
            <div class="mb-6 px-4 py-3 rounded-xl <?= $messageType === 'success' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30' ?>">
                <?= escape_html($message) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30">
                <?php foreach ($errors as $error): ?>
                <p><?= escape_html($error) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="glass-card rounded-2xl p-6 mb-6">
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 flex items-center justify-center glow">
                        <span class="text-3xl font-bold"><?= strtoupper(substr($user['username'], 0, 2)) ?></span>
                    </div>
                    <div class="text-center sm:text-left">
                        <h2 class="text-2xl font-bold"><?= escape_html($user['username']) ?></h2>
                        <p class="text-gray-400"><?= escape_html($user['email']) ?></p>
                        <p class="text-sm text-gray-500 mt-1">Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="glass-card rounded-xl p-4 text-center">
                    <div class="w-12 h-12 rounded-xl bg-accent-500/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <p class="text-2xl font-bold"><?= $booksRead ?></p>
                    <p class="text-sm text-gray-400">Livres lus</p>
                </div>
                <div class="glass-card rounded-xl p-4 text-center">
                    <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-2xl font-bold"><?= $user['is_active'] ? 'Actif' : 'Inactif' ?></p>
                    <p class="text-sm text-gray-400">Statut du compte</p>
                </div>
                <div class="glass-card rounded-xl p-4 text-center">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-2xl font-bold"><?= $lastActivity ? date('d/m', strtotime($lastActivity)) : '-' ?></p>
                    <p class="text-sm text-gray-400">Dernière activité</p>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Modifier mon profil
                </h3>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <!-- Username (read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Nom d'utilisateur</label>
                        <input type="text" value="<?= escape_html($user['username']) ?>" disabled 
                               class="w-full bg-dark-700/30 border border-white/5 rounded-xl px-4 py-3 text-gray-500 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1">Le nom d'utilisateur ne peut pas être modifié</p>
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Adresse email</label>
                        <input type="email" name="email" value="<?= escape_html($user['email']) ?>" 
                               class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 transition-colors">
                    </div>
                    
                    <hr class="border-white/10">
                    
                    <h4 class="text-md font-medium text-gray-300">Changer le mot de passe</h4>
                    <p class="text-sm text-gray-500 -mt-4">Laissez vide si vous ne souhaitez pas changer votre mot de passe</p>
                    
                    <!-- Current Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Mot de passe actuel</label>
                        <input type="password" name="current_password" 
                               class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 transition-colors"
                               placeholder="••••••••">
                    </div>
                    
                    <!-- New Password -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Nouveau mot de passe</label>
                            <input type="password" name="new_password" minlength="8"
                                   class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 transition-colors"
                                   placeholder="••••••••">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" minlength="8"
                                   class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 transition-colors"
                                   placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4">
                        <button type="submit" class="bg-gradient-to-r from-accent-500 to-accent-600 text-white px-6 py-3 rounded-xl font-medium hover:from-accent-600 hover:to-accent-700 transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>
