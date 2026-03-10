<?php
/**
 * E-Lib Digital Library - User Dashboard
 * Modern Dark Mode Glassmorphism UI
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';

$auth = new AuthManager();
$auth->requireRole('user');

$db = DatabaseManager::getInstance();
$userId = $_SESSION['user_id'];

// Get user's reading progress
$recentlyRead = $db->fetchAll("
    SELECT b.*, c.name as category_name, rp.last_position, rp.updated_at as last_read
    FROM reading_progress rp
    JOIN books b ON rp.book_id = b.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE rp.user_id = ?
    ORDER BY rp.updated_at DESC
    LIMIT 6
", [$userId]);

// Get total books available
$bookCount = $db->fetchOne("SELECT COUNT(*) as count FROM books")['count'];

// Get categories for quick access
$categories = $db->fetchAll("
    SELECT c.*, COUNT(b.id) as book_count 
    FROM categories c 
    LEFT JOIN books b ON c.id = b.category_id 
    GROUP BY c.id 
    HAVING book_count > 0
    ORDER BY book_count DESC 
    LIMIT 8
");

// Get all books for the grid
$allBooks = $db->fetchAll("
    SELECT b.*, c.name as category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    ORDER BY b.created_at DESC 
    LIMIT 12
");
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155', 600: '#475569' },
                        accent: { 500: '#6366f1', 600: '#4f46e5', 400: '#818cf8' },
                        sunset: { 500: '#f97316', 600: '#ea580c', 400: '#fb923c' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); }
        .book-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15); }
        .sidebar-link:hover { background: linear-gradient(90deg, rgba(99,102,241,0.2) 0%, transparent 100%); }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(99,102,241,0.3) 0%, transparent 100%); border-left: 3px solid #6366f1; }
        .glow { box-shadow: 0 0 20px rgba(99, 102, 241, 0.3); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #6366f1; }
    </style>
</head>
<body class="bg-dark-900 text-gray-100 min-h-screen">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-dark-800 border-r border-white/5 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <!-- Logo -->
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
        
        <!-- Navigation -->
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="font-medium">Accueil</span>
            </a>
            <a href="catalog.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="font-medium">Catalogue</span>
            </a>
            <a href="search.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="font-medium">Recherche</span>
            </a>
            <a href="favorites.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <span class="font-medium">Favoris</span>
            </a>
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="font-medium">Historique</span>
            </a>
            <a href="profile.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:text-white transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="font-medium">Mon Profil</span>
            </a>
        </nav>
        
        <!-- Categories Section -->
        <div class="px-4 mt-6">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-4">Catégories</h3>
            <div class="space-y-1">
                <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                <a href="catalog.php?category=<?= $cat['id'] ?>" class="flex items-center justify-between px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-300">
                    <span class="text-sm"><?= escape_html($cat['name']) ?></span>
                    <span class="text-xs bg-dark-700 px-2 py-0.5 rounded-full"><?= $cat['book_count'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- User Profile -->
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

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen">
        <!-- Top Navigation Bar (Glassmorphism) -->
        <header class="sticky top-0 z-30 glass border-b border-white/5">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4">
                <!-- Mobile Menu Button -->
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                
                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl mx-4">
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" placeholder="Rechercher un livre, un auteur..." class="w-full bg-dark-700/50 border border-white/10 rounded-xl pl-12 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 focus:ring-1 focus:ring-accent-500 transition-all duration-300">
                    </div>
                </div>
                
                <!-- Right Actions -->
                <div class="flex items-center gap-3">
                    <button class="p-2 text-gray-400 hover:text-white transition-colors relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-accent-500 rounded-full"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-4 lg:p-8">
            <!-- Welcome Section -->
            <div class="mb-8">
                <h1 class="text-2xl lg:text-3xl font-bold mb-2">Bienvenue, <?= escape_html($_SESSION['username']) ?> 👋</h1>
                <p class="text-gray-400">Découvrez notre collection de <?= $bookCount ?> livres numériques</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="glass-card rounded-2xl p-5 transition-all duration-300 hover:border-accent-500/30">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-accent-500/20 to-accent-600/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">+12%</span>
                    </div>
                    <p class="text-2xl font-bold"><?= $bookCount ?></p>
                    <p class="text-sm text-gray-400">Livres disponibles</p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 transition-all duration-300 hover:border-sunset-500/30">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sunset-500/20 to-sunset-600/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-sunset-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= count($recentlyRead) ?></p>
                    <p class="text-sm text-gray-400">En cours de lecture</p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 transition-all duration-300 hover:border-green-500/30">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500/20 to-green-600/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= count($categories) ?></p>
                    <p class="text-sm text-gray-400">Catégories</p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 transition-all duration-300 hover:border-purple-500/30">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-600/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold">24h</p>
                    <p class="text-sm text-gray-400">Temps de lecture</p>
                </div>
            </div>

            <!-- Continue Reading Section -->
            <?php if (!empty($recentlyRead)): ?>
            <div class="mb-8">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold">Continuer la lecture</h2>
                    <a href="#" class="text-sm text-accent-400 hover:text-accent-300 transition-colors">Voir tout →</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach (array_slice($recentlyRead, 0, 3) as $book): ?>
                    <a href="../reader.php?id=<?= $book['id'] ?>" class="glass-card rounded-2xl p-4 flex gap-4 group hover:border-accent-500/30 transition-all duration-300">
                        <div class="w-16 h-24 rounded-lg bg-gradient-to-br from-accent-500/30 to-purple-500/30 flex-shrink-0 overflow-hidden">
                            <?php if ($book['cover_path']): ?>
                                <img src="../uploads/covers/<?= escape_html($book['cover_path']) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <span class="text-xs font-bold text-accent-300 uppercase"><?= escape_html($book['file_type']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold truncate group-hover:text-accent-400 transition-colors"><?= escape_html($book['title']) ?></h3>
                            <p class="text-sm text-gray-400 truncate"><?= escape_html($book['author']) ?></p>
                            <div class="mt-3">
                                <div class="h-1.5 bg-dark-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-accent-500 to-accent-400 rounded-full" style="width: 45%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">45% complété</p>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Books Grid -->
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold">Bibliothèque</h2>
                    <div class="flex items-center gap-2">
                        <button class="p-2 bg-dark-700 rounded-lg text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </button>
                        <button class="p-2 bg-dark-800 rounded-lg text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Books Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 lg:gap-6">
                    <?php if (empty($allBooks)): ?>
                        <!-- Placeholder Cards -->
                        <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="book-card glass-card rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer">
                            <div class="aspect-[3/4] bg-gradient-to-br from-accent-500/20 to-purple-500/20 relative">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-accent-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                </div>
                                <span class="absolute top-2 right-2 px-2 py-0.5 text-xs font-bold rounded bg-accent-500 text-white">PDF</span>
                            </div>
                            <div class="p-3">
                                <h3 class="font-semibold text-sm truncate">Livre Exemple <?= $i + 1 ?></h3>
                                <p class="text-xs text-gray-400 truncate">Auteur Inconnu</p>
                            </div>
                        </div>
                        <?php endfor; ?>
                    <?php else: ?>
                        <?php foreach ($allBooks as $book): ?>
                        <a href="../reader.php?id=<?= $book['id'] ?>" class="book-card glass-card rounded-2xl overflow-hidden transition-all duration-300 group">
                            <div class="aspect-[3/4] bg-gradient-to-br from-accent-500/10 to-purple-500/10 relative overflow-hidden">
                                <?php if (!empty($book['cover_path'])): ?>
                                    <img src="../uploads/covers/<?= escape_html($book['cover_path']) ?>" alt="<?= escape_html($book['title']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-dark-700 to-dark-800">
                                        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </div>
                                <?php endif; ?>
                                <!-- Format Badge -->
                                <span class="absolute top-2 right-2 px-2 py-0.5 text-xs font-bold rounded <?= $book['file_type'] === 'pdf' ? 'bg-red-500' : 'bg-accent-500' ?> text-white shadow-lg">
                                    <?= strtoupper(escape_html($book['file_type'])) ?>
                                </span>
                                <!-- Hover Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-dark-900/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
                                    <span class="bg-accent-500 text-white px-4 py-2 rounded-full text-sm font-medium transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                        Lire maintenant
                                    </span>
                                </div>
                            </div>
                            <div class="p-3">
                                <h3 class="font-semibold text-sm truncate group-hover:text-accent-400 transition-colors" title="<?= escape_html($book['title']) ?>">
                                    <?= escape_html($book['title']) ?>
                                </h3>
                                <p class="text-xs text-gray-400 truncate"><?= escape_html($book['author']) ?></p>
                                <p class="text-xs text-gray-500 mt-1 truncate"><?= escape_html($book['category_name'] ?? 'Sans catégorie') ?></p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        
        // Search functionality
        const searchInput = document.querySelector('input[type="text"]');
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.value.trim()) {
                window.location.href = 'search.php?q=' + encodeURIComponent(this.value.trim());
            }
        });
        
        // Close sidebar on window resize (if open on mobile)
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                document.getElementById('sidebar').classList.remove('-translate-x-full');
                document.getElementById('mobile-overlay').classList.add('hidden');
            }
        });
    </script>
</body>
</html>
