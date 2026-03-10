<?php
/**
 * E-Lib Digital Library - Book Upload
 * Modern Dark Mode Glassmorphism UI - Green Accent
 */

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/file_manager.php';
require_once '../includes/advanced_input_validator.php';

$auth = new AuthManager();
$auth->requireRole('librarian');

$db = DatabaseManager::getInstance();
$fileManager = new FileManager();

$errors = [];
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        // Use AdvancedInputValidator for book metadata validation
        $validationRules = AdvancedInputValidator::getBookMetadataRules();
        
        $inputData = [
            'title' => $_POST['title'] ?? '',
            'author' => $_POST['author'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category_id' => $_POST['category_id'] ?? '',
            'isbn' => $_POST['isbn'] ?? ''
        ];
        
        $validationResult = AdvancedInputValidator::validateAndSanitize($inputData, $validationRules);
        
        if (!$validationResult['valid']) {
            $errors = array_merge($errors, array_values($validationResult['errors']));
        }
        
        $validatedData = $validationResult['data'];
        
        $bookFile = $_FILES['book_file'] ?? null;
        $bookUploadResult = null;
        
        if (!$bookFile || $bookFile['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Le fichier du livre est requis.';
        } else {
            // Use AdvancedInputValidator for file validation
            $bookConfig = AdvancedInputValidator::getBookUploadConfig();
            $bookValidation = AdvancedInputValidator::validateFileUpload($bookFile, $bookConfig);
            
            if (!$bookValidation['valid']) {
                $errors = array_merge($errors, $bookValidation['errors']);
            } else {
                $bookUploadResult = $fileManager->uploadBook($bookFile);
                if (!$bookUploadResult['success']) {
                    $errors = array_merge($errors, $bookUploadResult['errors']);
                }
            }
        }
        
        $coverFile = $_FILES['cover_image'] ?? null;
        $coverUrl = trim($_POST['cover_url'] ?? '');
        $coverUploadResult = null;
        
        // Priority: uploaded file > URL
        if ($coverFile && $coverFile['error'] !== UPLOAD_ERR_NO_FILE) {
            // Use AdvancedInputValidator for image validation
            $imageConfig = AdvancedInputValidator::getImageUploadConfig();
            $imageValidation = AdvancedInputValidator::validateFileUpload($coverFile, $imageConfig);
            
            if (!$imageValidation['valid']) {
                $errors = array_merge($errors, $imageValidation['errors']);
            } else {
                $coverUploadResult = $fileManager->uploadCover($coverFile);
                if (!$coverUploadResult['success']) {
                    $errors = array_merge($errors, $coverUploadResult['errors']);
                }
            }
        } elseif (!empty($coverUrl)) {
            // Validate URL using AdvancedInputValidator
            if (!AdvancedInputValidator::validateURL($coverUrl)) {
                $errors[] = 'URL de couverture invalide.';
            } else {
                $coverUploadResult = $fileManager->downloadCoverFromUrl($coverUrl);
                if (!$coverUploadResult['success']) {
                    $errors = array_merge($errors, $coverUploadResult['errors']);
                }
            }
        }
        
        if (empty($errors) && $bookUploadResult && $bookUploadResult['success']) {
            try {
                $db->beginTransaction();
                $coverPath = $coverUploadResult && $coverUploadResult['success'] ? $coverUploadResult['filename'] : null;
                
                // Auto-extract cover from EPUB if no cover was uploaded
                if ($coverPath === null && $bookUploadResult['file_type'] === 'epub') {
                    $epubPath = dirname(__DIR__) . '/uploads/books/' . $bookUploadResult['filename'];
                    $extractResult = $fileManager->extractEpubCover($epubPath);
                    if ($extractResult['success']) {
                        $coverPath = $extractResult['filename'];
                    }
                }
                
                $db->executeQuery("INSERT INTO books (title, author, description, file_path, file_type, cover_path, category_id, uploaded_by, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [$validatedData['title'], $validatedData['author'], $validatedData['description'], $bookUploadResult['filename'], $bookUploadResult['file_type'], $coverPath, $validatedData['category_id'], $auth->getCurrentUser()['id'], $bookUploadResult['file_size']]);
                $db->commit();
                log_action('book_upload', "Uploaded book: {$validatedData['title']}", $auth->getCurrentUser()['id']);
                redirect_with_message('books.php', 'Livre ajouté avec succès!', 'success');
            } catch (Exception $e) {
                $db->rollback();
                if ($bookUploadResult && $bookUploadResult['success']) $fileManager->deleteBook($bookUploadResult['filename']);
                if ($coverUploadResult && $coverUploadResult['success']) $fileManager->deleteCover($coverUploadResult['filename']);
                $errors[] = 'Erreur lors de l\'enregistrement.';
            }
        } else if ($bookUploadResult && $bookUploadResult['success'] && !empty($errors)) {
            $fileManager->deleteBook($bookUploadResult['filename']);
            if ($coverUploadResult && $coverUploadResult['success']) $fileManager->deleteCover($coverUploadResult['filename']);
        }
    }
}

$csrfToken = generate_csrf_token();
$maxBookSize = format_file_size(FileManager::getMaxBookSize());
$maxImageSize = format_file_size(FileManager::getMaxImageSize());
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Livre - E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' }, accent: { 500: '#22c55e', 400: '#4ade80' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .glass-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); }
        .sidebar-link:hover { background: linear-gradient(90deg, rgba(34,197,94,0.2) 0%, transparent 100%); }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(34,197,94,0.3) 0%, transparent 100%); border-left: 3px solid #22c55e; }
        .drop-zone { border: 2px dashed rgba(255,255,255,0.2); transition: all 0.3s; }
        .drop-zone:hover, .drop-zone.dragover { border-color: #22c55e; background: rgba(34,197,94,0.1); }
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
            <a href="upload.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-white transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg><span class="font-medium">Ajouter</span></a>
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
                    <h1 class="text-xl font-bold">Ajouter un Livre</h1>
                </div>
                <div></div>
            </div>
        </header>

        <div class="p-4 lg:p-8 max-w-3xl mx-auto">
            <?php if (!empty($errors)): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30">
                <?php foreach ($errors as $error): ?><p><?= escape_html($error) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
                
                <!-- Book File -->
                <div class="glass-card rounded-2xl p-6">
                    <label class="block text-sm font-medium text-gray-300 mb-3">Fichier du livre (PDF ou EPUB) <span class="text-red-400">*</span></label>
                    <div class="drop-zone rounded-xl p-8 text-center cursor-pointer" onclick="document.getElementById('book_file').click()">
                        <svg class="w-12 h-12 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <p class="text-gray-400 mb-1" id="book_file_name">Glissez-déposez ou cliquez pour sélectionner</p>
                        <p class="text-xs text-gray-500">Max: <?= $maxBookSize ?></p>
                        <input type="file" id="book_file" name="book_file" accept=".pdf,.epub" required class="hidden" onchange="updateFileName(this, 'book_file_name')">
                    </div>
                </div>

                <!-- Cover Image -->
                <div class="glass-card rounded-2xl p-6">
                    <label class="block text-sm font-medium text-gray-300 mb-3">Image de couverture (Optionnel)</label>
                    <p class="text-xs text-gray-500 mb-3">💡 Pour les fichiers EPUB, la couverture sera extraite automatiquement si non fournie.</p>
                    
                    <!-- Tabs for upload method -->
                    <div class="flex gap-2 mb-4">
                        <button type="button" onclick="switchCoverTab('file')" id="tab-file" class="flex-1 py-2 px-4 rounded-lg text-sm font-medium bg-accent-500/20 text-accent-400 border border-accent-500/30 transition-all">
                            📁 Fichier local
                        </button>
                        <button type="button" onclick="switchCoverTab('url')" id="tab-url" class="flex-1 py-2 px-4 rounded-lg text-sm font-medium bg-dark-700/50 text-gray-400 border border-white/10 transition-all">
                            🔗 URL internet
                        </button>
                    </div>
                    
                    <!-- File upload -->
                    <div id="cover-file-section">
                        <div class="drop-zone rounded-xl p-8 text-center cursor-pointer" onclick="document.getElementById('cover_image').click()">
                            <svg class="w-12 h-12 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-gray-400 mb-1" id="cover_file_name">Glissez-déposez ou cliquez pour sélectionner</p>
                            <p class="text-xs text-gray-500">Max: <?= $maxImageSize ?></p>
                            <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp" class="hidden" onchange="updateFileName(this, 'cover_file_name'); clearCoverUrl();">
                        </div>
                    </div>
                    
                    <!-- URL input -->
                    <div id="cover-url-section" class="hidden">
                        <div class="space-y-3">
                            <input type="url" id="cover_url" name="cover_url" placeholder="https://exemple.com/image.jpg" value="<?= escape_html($_POST['cover_url'] ?? '') ?>" class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500" onchange="clearCoverFile()">
                            <p class="text-xs text-gray-500">Collez l'URL d'une image (JPG, PNG, GIF, WebP). Max: <?= $maxImageSize ?></p>
                            
                            <!-- Preview -->
                            <div id="url-preview" class="hidden">
                                <p class="text-xs text-gray-400 mb-2">Aperçu:</p>
                                <img id="url-preview-img" src="" alt="Aperçu" class="max-h-48 rounded-lg border border-white/10">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="glass-card rounded-2xl p-6 space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-300 mb-2">Titre <span class="text-red-400">*</span></label>
                        <input type="text" id="title" name="title" value="<?= escape_html($_POST['title'] ?? '') ?>" required maxlength="255" class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500">
                    </div>
                    <div>
                        <label for="author" class="block text-sm font-medium text-gray-300 mb-2">Auteur <span class="text-red-400">*</span></label>
                        <input type="text" id="author" name="author" value="<?= escape_html($_POST['author'] ?? '') ?>" required maxlength="255" class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500">
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-300 mb-2">Catégorie <span class="text-red-400">*</span></label>
                        <select id="category_id" name="category_id" required class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-500">
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>><?= escape_html($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($categories)): ?>
                        <p class="mt-2 text-sm text-yellow-400">Aucune catégorie. <a href="categories.php" class="underline">Créez-en une</a>.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description <span class="text-red-400">*</span></label>
                        <textarea id="description" name="description" rows="4" required class="w-full bg-dark-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500"><?= escape_html($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="flex gap-4 justify-end">
                    <a href="books.php" class="px-6 py-3 bg-dark-700 rounded-xl hover:bg-dark-600 transition-colors">Annuler</a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-accent-500 to-green-600 text-white rounded-xl font-medium hover:from-green-600 hover:to-green-700 transition-all" <?= empty($categories) ? 'disabled' : '' ?>>Ajouter le livre</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('mobile-overlay').classList.toggle('hidden');
        }
        function updateFileName(input, labelId) {
            const label = document.getElementById(labelId);
            label.textContent = input.files[0] ? input.files[0].name : 'Glissez-déposez ou cliquez pour sélectionner';
        }
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
            zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
            zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('dragover'); const input = zone.querySelector('input'); input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); });
        });
        
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
            document.getElementById('cover_url').value = '';
            document.getElementById('url-preview').classList.add('hidden');
        }
        
        function clearCoverFile() {
            document.getElementById('cover_image').value = '';
            document.getElementById('cover_file_name').textContent = 'Glissez-déposez ou cliquez pour sélectionner';
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
