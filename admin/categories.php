<?php
/**
 * E-Lib Digital Library - Admin Category Management
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/category_manager.php';

$auth = new AuthManager();
$auth->requireRole('admin');

$categoryManager = new CategoryManager();
$errors = [];
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = sanitize_input($_POST['name'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $result = $categoryManager->createCategory($name, $description);
        if ($result['success']) {
            redirect_with_message('categories.php', 'Catégorie créée!', 'success');
        } else {
            $errors = $result['errors'];
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['category_id'] ?? 0);
        $result = $categoryManager->deleteCategory($id);
        if ($result['success']) {
            redirect_with_message('categories.php', 'Catégorie supprimée!', 'success');
        } else {
            $errors = $result['errors'];
        }
    }
}

$categories = $categoryManager->getAllCategoriesWithCounts();
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - Admin E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' }, accent: { 500: '#ef4444', 400: '#f87171' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
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
            <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="font-medium">Dashboard</span></a>
            <a href="users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span class="font-medium">Utilisateurs</span></a>
            <a href="books.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="font-medium">Livres</span></a>
            <a href="categories.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg><span class="font-medium">Catégories</span></a>
            <a href="logs.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="font-medium">Journaux</span></a>
        </nav>
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center text-sm font-bold"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
                <div class="flex-1"><p class="text-sm font-medium"><?= escape_html($_SESSION['username']) ?></p><p class="text-xs text-gray-500">Administrateur</p></div>
                <a href="../logout.php" class="p-2 text-gray-400 hover:text-red-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></a>
            </div>
        </div>
    </aside>

    <main class="lg:ml-64 min-h-screen">
        <header class="sticky top-0 z-30 glass border-b border-white/5">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-gray-400 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-xl font-bold">Gestion des Catégories</h1>
                <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nouvelle
                </button>
            </div>
        </header>

        <div class="p-4 lg:p-8">
            <?= display_flash_message() ?>
            
            <?php if (!empty($errors)): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30">
                <?php foreach ($errors as $error): ?><p><?= escape_html($error) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($categories as $cat): ?>
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-600/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <span class="px-3 py-1 text-sm font-bold rounded-full bg-dark-700 text-gray-300"><?= $cat['book_count'] ?> livre<?= $cat['book_count'] > 1 ? 's' : '' ?></span>
                    </div>
                    <h3 class="font-semibold text-lg mb-1"><?= escape_html($cat['name']) ?></h3>
                    <p class="text-sm text-gray-400 mb-4 line-clamp-2"><?= escape_html($cat['description'] ?? 'Aucune description') ?></p>
                    <div class="flex gap-2">
                        <a href="books.php?category=<?= $cat['id'] ?>" class="flex-1 text-center py-2 bg-dark-700 rounded-lg text-sm hover:bg-dark-600">Voir livres</a>
                        <?php if ($cat['book_count'] == 0): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                            <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="p-2 text-red-400 hover:bg-red-500/20 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Create Modal -->
    <div id="createModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4">Nouvelle catégorie</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Nom</label>
                            <input type="text" name="name" required class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                            <textarea name="description" rows="3" class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500"></textarea>
                        </div>
                    </div>
                    <div class="flex gap-3 justify-end mt-6">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 bg-dark-700 rounded-xl hover:bg-dark-600">Annuler</button>
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('mobile-overlay').classList.toggle('hidden');
        }
    </script>
</body>
</html>
