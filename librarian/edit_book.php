<?php
/**
 * E-Lib Digital Library - Edit Book
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
$bookId = (int)($_GET['id'] ?? 0);

$book = $bookManager->getBookById($bookId);
if (!$book) { redirect_with_message('books.php', 'Livre non trouvé.', 'error'); }

$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? 'update_metadata';
        
        if ($action === 'update_metadata') {
            $bookData = ['title' => sanitize_input($_POST['title'] ?? ''), 'author' => sanitize_input($_POST['author'] ?? ''), 'description' => sanitize_input($_POST['description'] ?? ''), 'category_id' => (int)($_POST['category_id'] ?? 0)];
            $result = $bookManager->updateBook($bookId, $bookData);
            if ($result['success']) {
                log_action('book_update', "Updated book: {$bookData['title']}", $auth->getCurrentUser()['id']);
                redirect_with_message('edit_book.php?id=' . $bookId, 'Livre mis à jour!', 'success');
            } else { $errors = $result['errors']; }
        } elseif ($action === 'update_cover') {
            $coverFile = $_FILES['cover_image'] ?? null;
            $coverUrl = trim($_POST['cover_url'] ?? '');
            $coverUploadResult = null;
            
            // Priority: uploaded file > URL
            if ($coverFile && $coverFile['error'] !== UPLOAD_ERR_NO_FILE) {
                $coverUploadResult = $fileManager->uploadCover($coverFile);
            } elseif (!empty($coverUrl)) {
                $coverUploadResult = $fileManager->downloadCoverFromUrl($coverUrl);
            } else {
                $errors[] = 'Sélectionnez une image ou entrez une URL.';
            }
            
            if ($coverUploadResult) {
                if ($coverUploadResult['success']) {
                    if ($bookManager->updateBookCover($bookId, $coverUploadResult['filename'])) {
                        log_action('book_cover_update', "Updated cover for book ID: $bookId", $auth->getCurrentUser()['id']);
                        redirect_with_message('edit_book.php?id=' . $bookId, 'Couverture mise à jour!', 'success');
                    } else { 
                        $fileManager->deleteCover($coverUploadResult['filename']); 
                        $errors[] = 'Échec de la mise à jour.'; 
                    }
                } else { 
                    $errors = array_merge($errors, $coverUploadResult['errors']); 
                }
            }
        }
        $book = $bookManager->getBookById($bookId);
    }
}

$csrfToken = generate_csrf_token();
$maxImageSize = format_file_size(FileManager::getMaxImageSize());
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier - <?= escape_html($book['title']) ?> - E-Lib</title>
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
                    <a href="books.php" class="text-accent-400 hover:text-accent-300 text-sm">← Retour aux livres</a>
                    <h1 class="text-xl font-bold">Modifier le livre</h1>
                </div>
                <span class="px-3 py-1 text-sm font-bold rounded <?= $book['file_type'] === 'pdf' ? 'bg-red-500/20 text-red-400' : 'bg-blue-500/20 text-blue-400' ?>"><?= strtoupper(escape_html($book['file_type'])) ?></span>
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
                <!-- Cover -->
                <div class="space-y-6">
                    <div class="glass-card rounded-2xl p-6">
                        <h2 class="font-semibold mb-4">Couverture</h2>
                        <div class="aspect-[3/4] rounded-xl overflow-hidden mb-4 bg-gradient-to-br from-dark-700 to-dark-800">
                            <?php if (!empty($book['cover_path'])): ?>
                            <img src="../uploads/covers/<?= escape_html($book['cover_path']) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center"><span class="text-2xl font-bold text-gray-600">Pas de couverture</span></div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                            <input type="hidden" name="action" value="update_cover">
                            
                            <!-- Tabs -->
                            <div class="flex gap-2 mb-3">
                                <button type="button" onclick="switchCoverTab('file')" id="tab-file" class="flex-1 py-2 px-3 rounded-lg text-xs font-medium bg-accent-500/20 text-accent-400 border border-accent-500/30">📁 Fichier</button>
                                <button type="button" onclick="switchCoverTab('url')" id="tab-url" class="flex-1 py-2 px-3 rounded-lg text-xs font-medium bg-dark-700/50 text-gray-400 border border-white/10">🔗 URL</button>
                            </div>
                            
                            <!-- File upload -->
                            <div id="cover-file-section">
                                <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-accent-500/20 file:text-accent-400 hover:file:bg-accent-500/30 mb-2" onchange="clearCoverUrl()">
                                <p class="text-xs text-gray-500 mb-3">Max: <?= $maxImageSize ?></p>
                            </div>
                            
                            <!-- URL input -->
                            <div id="cover-url-section" class="hidden">
                                <input type="url" id="cover_url" name="cover_url" placeholder="https://exemple.com/image.jpg" class="w-full bg-dark-700/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 mb-2" onchange="clearCoverFile()">
                                <p class="text-xs text-gray-500 mb-3">Collez l'URL d'une image</p>
                                <div id="url-preview" class="hidden mb-3">
                                    <img id="url-preview-img" src="" alt="Aperçu" class="max-h-24 rounded-lg border border-white/10">
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full bg-dark-700 text-white py-2 rounded-xl hover:bg-dark-600 transition-colors">Mettre à jour</button>
                        </form>
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <h2 class="font-semibold mb-4">Informations</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Type:</dt><dd class="font-medium"><?= strtoupper(escape_html($book['file_type'])) ?></dd></div>
                            <?php if (!empty($book['file_size'])): ?><div class="flex justify-between"><dt class="text-gray-500">Taille:</dt><dd class="font-medium"><?= format_file_size($book['file_size']) ?></dd></div><?php endif; ?>
                            <div class="flex justify-between"><dt class="text-gray-500">Ajouté:</dt><dd class="font-medium"><?= date('d/m/Y', strtotime($book['created_at'])) ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Modifié:</dt><dd class="font-medium"><?= date('d/m/Y', strtotime($book['updated_at'])) ?></dd></div>
                        </dl>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-2xl p-6">
                        <h2 class="font-semibold mb-4">Métadonnées</h2>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                            <input type="hidden" name="action" value="update_metadata">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Titre <span class="text-red-400">*</span></label>
                                <input type="text" name="title" value="<?= escape_html($book['title']) ?>" required maxlength="255" class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Auteur <span class="text-red-400">*</span></label>
                                <input type="text" name="author" value="<?= escape_html($book['author']) ?>" required maxlength="255" class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Catégorie <span class="text-red-400">*</span></label>
                                <select name="category_id" required class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-500">
                                    <option value="">Sélectionner</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $book['category_id'] == $category['id'] ? 'selected' : '' ?>><?= escape_html($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Description <span class="text-red-400">*</span></label>
                                <textarea name="description" rows="6" required class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-500"><?= escape_html($book['description']) ?></textarea>
                            </div>
                            <div class="flex gap-4 pt-4">
                                <button type="submit" class="flex-1 bg-gradient-to-r from-accent-500 to-green-600 text-white py-3 rounded-xl font-medium hover:from-green-600 hover:to-green-700">Enregistrer</button>
                                <a href="books.php" class="px-6 py-3 bg-dark-700 rounded-xl hover:bg-dark-600 text-center">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('mobile-overlay').classList.toggle('hidden');
        }
        
        // Cover tabs
        function switchCoverTab(tab) {
            const fileSection = document.getElementById('cover-file-section');
            const urlSection = document.getElementById('cover-url-section');
            const tabFile = document.getElementById('tab-file');
            const tabUrl = document.getElementById('tab-url');
            
            if (tab === 'file') {
                fileSection.classList.remove('hidden');
                urlSection.classList.add('hidden');
                tabFile.classList.add('bg-accent-500/20', 'text-accent-400', 'border-accent-500/30');
                tabFile.classList.remove('bg-dark-700/50', 'text-gray-400', 'border-white/10');
                tabUrl.classList.remove('bg-accent-500/20', 'text-accent-400', 'border-accent-500/30');
                tabUrl.classList.add('bg-dark-700/50', 'text-gray-400', 'border-white/10');
            } else {
                fileSection.classList.add('hidden');
                urlSection.classList.remove('hidden');
                tabUrl.classList.add('bg-accent-500/20', 'text-accent-400', 'border-accent-500/30');
                tabUrl.classList.remove('bg-dark-700/50', 'text-gray-400', 'border-white/10');
                tabFile.classList.remove('bg-accent-500/20', 'text-accent-400', 'border-accent-500/30');
                tabFile.classList.add('bg-dark-700/50', 'text-gray-400', 'border-white/10');
            }
        }
        
        function clearCoverUrl() {
            const urlInput = document.getElementById('cover_url');
            if (urlInput) urlInput.value = '';
            const preview = document.getElementById('url-preview');
            if (preview) preview.classList.add('hidden');
        }
        
        function clearCoverFile() {
            const fileInput = document.getElementById('cover_image');
            if (fileInput) fileInput.value = '';
        }
        
        // URL preview
        document.getElementById('cover_url')?.addEventListener('input', function() {
            const url = this.value.trim();
            const preview = document.getElementById('url-preview');
            const previewImg = document.getElementById('url-preview-img');
            
            if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                previewImg.src = url;
                previewImg.onload = () => preview.classList.remove('hidden');
                previewImg.onerror = () => preview.classList.add('hidden');
            } else {
                preview.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
