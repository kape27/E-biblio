<?php
/**
 * E-Lib Digital Library - Category Management
 * Modern Dark Mode Glassmorphism UI - Green Accent
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/category_manager.php';

$auth = new AuthManager();
$auth->requireRole('librarian');

$categoryManager = new CategoryManager();
$errors = [];
$editCategory = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $result = $categoryManager->createCategory(sanitize_input($_POST['name'] ?? ''), sanitize_input($_POST['description'] ?? ''));
            if ($result['success']) {
                log_action('category_create', "Created category: " . $_POST['name'], $auth->getCurrentUser()['id']);
                redirect_with_message('categories.php', 'Catégorie créée!', 'success');
            } else { $errors = $result['errors']; }
        } elseif ($action === 'update') {
            $id = (int)($_POST['category_id'] ?? 0);
            $result = $categoryManager->updateCategory($id, sanitize_input($_POST['name'] ?? ''), sanitize_input($_POST['description'] ?? ''));
            if ($result['success']) {
                log_action('category_update', "Updated category ID: $id", $auth->getCurrentUser()['id']);
                redirect_with_message('categories.php', 'Catégorie mise à jour!', 'success');
            } else { $errors = $result['errors']; $editCategory = $categoryManager->getCategoryById($id); }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['category_id'] ?? 0);
            $result = $categoryManager->deleteCategory($id);
            if ($result['success']) {
                log_action('category_delete', "Deleted category ID: $id", $auth->getCurrentUser()['id']);
                redirect_with_message('categories.php', 'Catégorie supprimée!', 'success');
            } else { $errors = $result['errors']; }
        }
    }
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editCategory = $categoryManager->getCategoryById((int)$_GET['edit']);
}

$categories = $categoryManager->getAllCategoriesWithCounts();
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' }, accent: { 500: '#22c55e', 400: '#4ade80' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); }
        .sidebar-link:hover { background: linear-gradient(90deg, rgba(34,197,94,0.2) 0%, transparent 100%); }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(34,197,94,0.3) 0%, transparent 100%); border-left: 3px solid #22c55e; }
    </style>
</head>
<body class="bg-dark-900 text-gray-100 min-h-screen">
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-dark-800 border-r border-white/5 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                <span class="text-xl font-bold text-white">Bibliothécaire</span>
            </div>
        </div>
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="font-medium">Dashboard</span></a>
            <a href="books.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="font-medium">Livres</span></a>
            <a href="upload.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg><span class="font-medium">Ajouter</span></a>
            <a href="categories.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg><span class="font-medium">Catégories</span></a>
        </nav>
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-sm font-bold"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
                <div class="flex-1"><p class="text-sm font-medium"><?= escape_html($_SESSION['username']) ?></p><p class="text-xs text-gray-500">Bibliothécaire</p></div>
                <a href="../logout.php" class="p-2 text-gray-400 hover:text-red-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></a>
            </div>
        </div>
    </aside>

    <main class="lg:ml-64 min-h-screen">
        <header class="sticky top-0 z-30 glass border-b border-white/5">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-gray-400 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <div>
                    <h1 class="text-xl font-bold">Catégories</h1>
                    <p class="text-sm text-gray-400"><?= count($categories) ?> catégorie<?= count($categories) > 1 ? 's' : '' ?></p>
                </div>
                <div></div>
            </div>
        </header>

        <div class="p-4 lg:p-8">
            <?= display_flash_message() ?>
            
            <?php if (!empty($errors)): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30">
                <?php foreach ($errors as $error): ?><p><?= escape_html($error) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form -->
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="font-semibold mb-4"><?= $editCategory ? 'Modifier' : 'Nouvelle catégorie' ?></h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                        <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'create' ?>">
                        <?php if ($editCategory): ?><input type="hidden" name="category_id" value="<?= $editCategory['id'] ?>"><?php endif; ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Nom <span class="text-red-400">*</span></label>
                            <input type="text" name="name" value="<?= escape_html($editCategory['name'] ?? $_POST['name'] ?? '') ?>" required maxlength="100" class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                            <textarea name="description" rows="3" class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-500"><?= escape_html($editCategory['description'] ?? $_POST['description'] ?? '') ?></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-accent-500 to-green-600 text-white py-3 rounded-xl font-medium hover:from-green-600 hover:to-green-700"><?= $editCategory ? 'Mettre à jour' : 'Créer' ?></button>
                            <?php if ($editCategory): ?><a href="categories.php" class="px-4 py-3 bg-dark-700 rounded-xl hover:bg-dark-600 text-center">Annuler</a><?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- List -->
                <div class="lg:col-span-2 glass-card rounded-2xl">
                    <div class="px-6 py-4 border-b border-white/5"><h2 class="font-semibold">Liste des catégories</h2></div>
                    <?php if (empty($categories)): ?>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-dark-700 flex items-center justify-center"><svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg></div>
                        <p class="text-gray-400">Aucune catégorie</p>
                    </div>
                    <?php else: ?>
                    <div class="divide-y divide-white/5">
                        <?php foreach ($categories as $category): ?>
                        <div class="p-4 hover:bg-white/5 transition-colors <?= ($editCategory && $editCategory['id'] == $category['id']) ? 'bg-accent-500/10' : '' ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center"><svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg></div>
                                    <div>
                                        <h3 class="font-medium"><?= escape_html($category['name']) ?></h3>
                                        <?php if (!empty($category['description'])): ?><p class="text-sm text-gray-500 truncate max-w-xs"><?= escape_html($category['description']) ?></p><?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400"><?= $category['book_count'] ?> livre<?= $category['book_count'] > 1 ? 's' : '' ?></span>
                                    <a href="?edit=<?= $category['id'] ?>" class="p-2 text-gray-400 hover:text-accent-400 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                    <button onclick="confirmDelete(<?= $category['id'] ?>, '<?= escape_html(addslashes($category['name'])) ?>', <?= $category['book_count'] ?>)" class="p-2 text-gray-400 hover:text-red-400 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-2">Supprimer la catégorie</h3>
                <p class="text-gray-400 mb-2">Supprimer "<span id="deleteCategoryName"></span>" ?</p>
                <p id="affectedBooksWarning" class="text-yellow-400 text-sm mb-4 hidden"><span id="affectedBooksCount"></span> livre(s) seront affectés.</p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <div class="flex gap-3 justify-end">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-dark-700 rounded-xl hover:bg-dark-600">Annuler</button>
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600">Supprimer</button>
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
        function confirmDelete(id, name, bookCount) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryName').textContent = name;
            const warning = document.getElementById('affectedBooksWarning');
            if (bookCount > 0) { document.getElementById('affectedBooksCount').textContent = bookCount; warning.classList.remove('hidden'); }
            else { warning.classList.add('hidden'); }
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        function closeDeleteModal() { document.getElementById('deleteModal').classList.add('hidden'); }
    </script>
</body>
</html>
