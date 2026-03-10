<?php
/**
 * E-Lib Digital Library - Book Management
 * Modern Dark Mode Glassmorphism UI - Green Accent
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/book_manager.php';
require_once '../includes/file_manager.php';

$auth = new AuthManager();
$auth->requireRole('librarian');

$db = DatabaseManager::getInstance();
$bookManager = new BookManager();
$fileManager = new FileManager();

$errors = [];

// Handle book deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $bookId = (int)($_POST['book_id'] ?? 0);
        if ($bookId > 0) {
            $result = $bookManager->deleteBook($bookId);
            if ($result['success']) {
                log_action('book_delete', "Deleted book ID: $bookId", $auth->getCurrentUser()['id']);
                redirect_with_message('books.php', 'Livre supprimé avec succès!', 'success');
            } else {
                $errors = $result['errors'];
            }
        }
    }
}

// Get filter parameters
$search = sanitize_input($_GET['search'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name ASC");

if (!empty($search) || $categoryFilter > 0) {
    $books = $bookManager->searchBooks($search, $categoryFilter > 0 ? $categoryFilter : null);
    $totalBooks = count($books);
    $books = array_slice($books, $offset, $perPage);
} else {
    $books = $bookManager->getAllBooks($perPage, $offset);
    $totalBooks = $bookManager->getTotalBookCount();
}

$totalPages = ceil($totalBooks / $perPage);
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Livres - E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' }, accent: { 500: '#22c55e', 400: '#4ade80' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); }
        .sidebar-link:hover { background: linear-gradient(90deg, rgba(34,197,94,0.2) 0%, transparent 100%); }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(34,197,94,0.3) 0%, transparent 100%); border-left: 3px solid #22c55e; }
        .book-card { transition: all 0.3s ease; }
        .book-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
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
            <a href="books.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="font-medium">Livres</span></a>
            <a href="upload.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg><span class="font-medium">Ajouter</span></a>
            <a href="categories.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg><span class="font-medium">Catégories</span></a>
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
                    <h1 class="text-xl font-bold">Gestion des Livres</h1>
                    <p class="text-sm text-gray-400"><?= $totalBooks ?> livre<?= $totalBooks > 1 ? 's' : '' ?></p>
                </div>
                <a href="upload.php" class="bg-gradient-to-r from-accent-500 to-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:from-green-600 hover:to-green-700 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Ajouter
                </a>
            </div>
        </header>

        <div class="p-4 lg:p-8">
            <?= display_flash_message() ?>
            
            <?php if (!empty($errors)): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30">
                <?php foreach ($errors as $error): ?><p><?= escape_html($error) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <div class="glass-card rounded-2xl p-4 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-4">
                    <input type="text" name="search" value="<?= escape_html($search) ?>" placeholder="Rechercher par titre, auteur..." class="flex-1 bg-dark-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500">
                    <select name="category" class="bg-dark-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-accent-500">
                        <option value="">Toutes catégories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $categoryFilter == $category['id'] ? 'selected' : '' ?>><?= escape_html($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-dark-700 text-white px-6 py-2.5 rounded-xl hover:bg-dark-600 transition-colors">Rechercher</button>
                    <?php if (!empty($search) || $categoryFilter > 0): ?>
                    <a href="books.php" class="px-4 py-2.5 text-gray-400 hover:text-white text-center">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Books Grid -->
            <?php if (empty($books)): ?>
            <div class="glass-card rounded-2xl p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-dark-700 flex items-center justify-center"><svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                <h3 class="text-lg font-medium mb-2">Aucun livre trouvé</h3>
                <p class="text-gray-400 mb-4"><?= (!empty($search) || $categoryFilter > 0) ? 'Aucun livre ne correspond à vos critères.' : 'Commencez par ajouter votre premier livre.' ?></p>
                <a href="upload.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-accent-500 to-green-600 text-white px-4 py-2 rounded-xl"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Ajouter un livre</a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                <?php foreach ($books as $book): ?>
                <div class="book-card glass-card rounded-2xl overflow-hidden group">
                    <div class="aspect-[3/4] bg-gradient-to-br from-dark-700 to-dark-800 relative overflow-hidden">
                        <?php if (!empty($book['cover_path'])): ?>
                        <img src="../uploads/covers/<?= escape_html($book['cover_path']) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center"><span class="text-2xl font-bold text-gray-600 uppercase"><?= escape_html($book['file_type']) ?></span></div>
                        <?php endif; ?>
                        <span class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded <?= $book['file_type'] === 'pdf' ? 'bg-red-500' : 'bg-blue-500' ?> text-white"><?= strtoupper(escape_html($book['file_type'])) ?></span>
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <a href="edit_book.php?id=<?= $book['id'] ?>" class="p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                            <button onclick="confirmDelete(<?= $book['id'] ?>, '<?= escape_html(addslashes($book['title'])) ?>')" class="p-2 bg-red-500/50 rounded-lg hover:bg-red-500/70 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                    <div class="p-3">
                        <h3 class="font-medium text-sm truncate"><?= escape_html($book['title']) ?></h3>
                        <p class="text-xs text-gray-500 truncate"><?= escape_html($book['author']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="mt-8 flex justify-center gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" class="px-4 py-2 bg-dark-700 rounded-lg text-gray-300 hover:bg-dark-600">← Précédent</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" class="px-4 py-2 rounded-lg <?= $i === $page ? 'bg-accent-500 text-white' : 'bg-dark-700 text-gray-300 hover:bg-dark-600' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" class="px-4 py-2 bg-dark-700 rounded-lg text-gray-300 hover:bg-dark-600">Suivant →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Delete Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-2">Confirmer la suppression</h3>
                <p class="text-gray-400 mb-4">Supprimer "<span id="deleteBookTitle"></span>" ? Cette action est irréversible.</p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="book_id" id="deleteBookId">
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
        function confirmDelete(id, title) {
            document.getElementById('deleteBookId').value = id;
            document.getElementById('deleteBookTitle').textContent = title;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>
