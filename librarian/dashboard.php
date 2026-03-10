<?php
/**
 * E-Lib Digital Library - Librarian Dashboard
 * Modern Dark Mode Glassmorphism UI - Green Accent
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
$auth->requireRole('librarian');

$db = DatabaseManager::getInstance();

// Get statistics
$bookCount = $db->fetchOne("SELECT COUNT(*) as count FROM books")['count'];
$categoryCount = $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'];
$pdfCount = $db->fetchOne("SELECT COUNT(*) as count FROM books WHERE file_type = 'pdf'")['count'];
$epubCount = $db->fetchOne("SELECT COUNT(*) as count FROM books WHERE file_type = 'epub'")['count'];

// Get recent books
$recentBooks = $db->fetchAll("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.created_at DESC LIMIT 6");

// Get categories with book counts
$categoriesWithCounts = $db->fetchAll("SELECT c.*, COUNT(b.id) as book_count FROM categories c LEFT JOIN books b ON c.id = b.category_id GROUP BY c.id ORDER BY book_count DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Bibliothécaire - E-Lib</title>
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
            <a href="dashboard.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="font-medium">Dashboard</span></a>
            <a href="books.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="font-medium">Livres</span></a>
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
                    <h1 class="text-xl font-bold">Dashboard</h1>
                    <p class="text-sm text-gray-400">Gérez votre collection</p>
                </div>
                <a href="upload.php" class="bg-gradient-to-r from-accent-500 to-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:from-green-600 hover:to-green-700 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Ajouter
                </a>
            </div>
        </header>

        <div class="p-4 lg:p-8">
            <?= display_flash_message() ?>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center"><svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                        <div><p class="text-xs text-gray-400">Total Livres</p><p class="text-2xl font-bold"><?= $bookCount ?></p></div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center"><svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div>
                        <div><p class="text-xs text-gray-400">PDF</p><p class="text-2xl font-bold"><?= $pdfCount ?></p></div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center"><svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                        <div><p class="text-xs text-gray-400">EPUB</p><p class="text-2xl font-bold"><?= $epubCount ?></p></div>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center"><svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg></div>
                        <div><p class="text-xs text-gray-400">Catégories</p><p class="text-2xl font-bold"><?= $categoryCount ?></p></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Books -->
                <div class="lg:col-span-2 glass-card rounded-2xl">
                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
                        <h2 class="font-semibold">Livres récents</h2>
                        <a href="books.php" class="text-accent-400 hover:text-accent-300 text-sm">Voir tout →</a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentBooks)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-dark-700 flex items-center justify-center"><svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                            <p class="text-gray-400 mb-2">Aucun livre</p>
                            <a href="upload.php" class="text-accent-400 hover:underline">Ajouter votre premier livre</a>
                        </div>
                        <?php else: ?>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <?php foreach ($recentBooks as $book): ?>
                            <a href="edit_book.php?id=<?= $book['id'] ?>" class="book-card group">
                                <div class="aspect-[3/4] rounded-xl bg-gradient-to-br from-dark-700 to-dark-800 mb-2 flex items-center justify-center overflow-hidden">
                                    <?php if ($book['cover_image']): ?>
                                    <img src="../uploads/covers/<?= escape_html($book['cover_image']) ?>" alt="" class="w-full h-full object-cover">
                                    <?php else: ?>
                                    <span class="text-2xl font-bold text-gray-600 uppercase"><?= escape_html($book['file_type']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="font-medium text-sm truncate group-hover:text-accent-400 transition-colors"><?= escape_html($book['title']) ?></h3>
                                <p class="text-xs text-gray-500 truncate"><?= escape_html($book['author']) ?></p>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Categories -->
                <div class="glass-card rounded-2xl">
                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
                        <h2 class="font-semibold">Catégories</h2>
                        <a href="categories.php" class="text-purple-400 hover:text-purple-300 text-sm">Gérer →</a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($categoriesWithCounts)): ?>
                        <p class="text-gray-500 text-center py-4">Aucune catégorie</p>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($categoriesWithCounts as $category): ?>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-gray-300"><?= escape_html($category['name']) ?></span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400"><?= $category['book_count'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
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
