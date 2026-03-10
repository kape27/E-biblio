<?php
/**
 * E-Lib Digital Library - Book Catalog
 * Modern Dark Mode Glassmorphism UI
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/book_manager.php';
require_once '../includes/category_manager.php';
require_once '../includes/favorites_manager.php';
require_once '../includes/csrf_protection.php';

// Initialize CSRF protection
CSRFProtectionManager::initialize();

$auth = new AuthManager();
$auth->requireRole('user');

$bookManager = new BookManager();
$categoryManager = new CategoryManager();
$favoritesManager = new FavoritesManager();

$userId = $_SESSION['user_id'];
$userFavoriteIds = $favoritesManager->getUserFavoriteIds($userId);

$categoryFilter = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$categories = $categoryManager->getAllCategoriesWithCounts();

if ($categoryFilter > 0) {
    $books = $bookManager->getBooksByCategory($categoryFilter);
    $totalBooks = count($books);
    $books = array_slice($books, $offset, $perPage);
    $currentCategory = $categoryManager->getCategoryById($categoryFilter);
} else {
    $books = $bookManager->getAllBooks($perPage, $offset);
    $totalBooks = $bookManager->getTotalBookCount();
    $currentCategory = null;
}

$totalPages = ceil($totalBooks / $perPage);
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= CSRFProtectionManager::generateTokenMeta() ?>
    <title>Catalogue - E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' }, accent: { 500: '#6366f1', 600: '#4f46e5', 400: '#818cf8' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); }
        .book-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15); }
        .sidebar-link:hover { background: linear-gradient(90deg, rgba(99,102,241,0.2) 0%, transparent 100%); }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(99,102,241,0.3) 0%, transparent 100%); border-left: 3px solid #6366f1; }
        .favorite-btn { transition: all 0.3s ease; }
        .favorite-btn:hover { transform: scale(1.2); }
        .favorite-btn.active svg { fill: #ef4444; color: #ef4444; }
    </style>
</head>
<body class="bg-dark-900 text-gray-100 min-h-screen">
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-dark-800 border-r border-white/5 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-accent-500 to-accent-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <span class="text-xl font-bold text-white">E-Lib</span>
            </div>
        </div>
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="font-medium">Accueil</span>
            </a>
            <a href="catalog.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="font-medium">Catalogue</span>
            </a>
            <a href="favorites.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <span class="font-medium">Favoris</span>
            </a>
            <a href="search.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="font-medium"></span>Recherche</span>
            </a>
            <a href="profile.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="font-medium">Mon Profil</span>
            </a>
        </nav>
        <div class="px-4 mt-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-4">Catégories</h3>
            <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
            <a href="catalog.php?category=<?= $cat['id'] ?>" class="flex items-center justify-between px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all <?= $categoryFilter == $cat['id'] ? 'text-accent-400 bg-accent-500/10' : '' ?>">
                <span class="text-sm"><?= escape_html($cat['name']) ?></span>
                <span class="text-xs bg-dark-700 px-2 py-0.5 rounded-full"><?= $cat['book_count'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 flex items-center justify-center text-sm font-bold"><?= strtoupper(substr($_SESSION['username'], 0, 2)) ?></div>
                <div class="flex-1"><p class="text-sm font-medium"><?= escape_html($_SESSION['username']) ?></p></div>
                <a href="../logout.php" class="p-2 text-gray-400 hover:text-red-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen">
        <header class="sticky top-0 z-30 glass border-b border-white/5">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-gray-400 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <div class="flex-1 max-w-xl mx-4">
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="searchInput" placeholder="Rechercher..." class="w-full bg-dark-700/50 border border-white/10 rounded-xl pl-12 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 transition-all">
                    </div>
                </div>
                <div></div>
            </div>
        </header>
        
        <div class="p-4 lg:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold"><?= $currentCategory ? escape_html($currentCategory['name']) : 'Catalogue' ?></h1>
                <p class="text-gray-400 mt-1"><?= $totalBooks ?> livre<?= $totalBooks > 1 ? 's' : '' ?></p>
            </div>
            
            <!-- Category Pills -->
            <div class="flex flex-wrap gap-2 mb-6">
                <a href="catalog.php" class="px-4 py-2 rounded-full text-sm font-medium transition-all <?= !$categoryFilter ? 'bg-accent-500 text-white' : 'bg-dark-700 text-gray-300 hover:bg-dark-600' ?>">Tous</a>
                <?php foreach ($categories as $cat): if ($cat['book_count'] > 0): ?>
                <a href="catalog.php?category=<?= $cat['id'] ?>" class="px-4 py-2 rounded-full text-sm font-medium transition-all <?= $categoryFilter == $cat['id'] ? 'bg-accent-500 text-white' : 'bg-dark-700 text-gray-300 hover:bg-dark-600' ?>"><?= escape_html($cat['name']) ?></a>
                <?php endif; endforeach; ?>
            </div>
            
            <!-- Books Grid -->
            <?php if (empty($books)): ?>
            <div class="glass-card rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <h3 class="text-lg font-medium mb-2">Aucun livre trouvé</h3>
                <p class="text-gray-500">La bibliothèque est vide pour le moment.</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 lg:gap-6">
                <?php foreach ($books as $book): 
                    $isFavorite = in_array($book['id'], $userFavoriteIds);
                ?>
                <div class="book-card glass-card rounded-2xl overflow-hidden transition-all duration-300 group relative">
                    <button onclick="toggleFavorite(<?= $book['id'] ?>, this)" class="favorite-btn <?= $isFavorite ? 'active' : '' ?> absolute top-2 left-2 z-10 p-2 rounded-full bg-dark-900/70 hover:bg-dark-900">
                        <svg class="w-5 h-5 <?= $isFavorite ? 'text-red-500' : 'text-gray-400' ?>" fill="<?= $isFavorite ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </button>
                    <a href="../reader.php?id=<?= $book['id'] ?>" class="block">
                        <div class="aspect-[3/4] bg-gradient-to-br from-accent-500/10 to-purple-500/10 relative overflow-hidden">
                            <?php if (!empty($book['cover_path'])): ?>
                            <img src="../uploads/covers/<?= escape_html($book['cover_path']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center"><svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                            <?php endif; ?>
                            <span class="absolute top-2 right-2 px-2 py-0.5 text-xs font-bold rounded <?= $book['file_type'] === 'pdf' ? 'bg-red-500' : 'bg-accent-500' ?> text-white"><?= strtoupper($book['file_type']) ?></span>
                            <div class="absolute inset-0 bg-gradient-to-t from-dark-900/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center pb-4">
                                <span class="bg-accent-500 text-white px-4 py-2 rounded-full text-sm font-medium transform translate-y-4 group-hover:translate-y-0 transition-transform">Lire</span>
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-semibold text-sm truncate group-hover:text-accent-400 transition-colors"><?= escape_html($book['title']) ?></h3>
                            <p class="text-xs text-gray-400 truncate"><?= escape_html($book['author']) ?></p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="mt-8 flex justify-center gap-2">
                <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&category=<?= $categoryFilter ?>" class="px-4 py-2 bg-dark-700 rounded-lg hover:bg-dark-600 transition-colors">←</a><?php endif; ?>
                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                <a href="?page=<?= $i ?>&category=<?= $categoryFilter ?>" class="px-4 py-2 rounded-lg transition-colors <?= $i === $page ? 'bg-accent-500 text-white' : 'bg-dark-700 hover:bg-dark-600' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>&category=<?= $categoryFilter ?>" class="px-4 py-2 bg-dark-700 rounded-lg hover:bg-dark-600 transition-colors">→</a><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('-translate-x-full'); document.getElementById('mobile-overlay').classList.toggle('hidden'); }
        document.getElementById('searchInput').addEventListener('keypress', e => { if (e.key === 'Enter' && e.target.value.trim()) window.location.href = 'search.php?q=' + encodeURIComponent(e.target.value.trim()); });
        
        function toggleFavorite(bookId, btn) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch('../api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    book_id: bookId,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const svg = btn.querySelector('svg');
                    if (data.is_favorite) {
                        btn.classList.add('active');
                        svg.setAttribute('fill', 'currentColor');
                        svg.classList.remove('text-gray-400');
                        svg.classList.add('text-red-500');
                    } else {
                        btn.classList.remove('active');
                        svg.setAttribute('fill', 'none');
                        svg.classList.remove('text-red-500');
                        svg.classList.add('text-gray-400');
                    }
                }
            })
            .catch(err => console.error('Error:', err));
        }
    </script>
</body>
</html>
