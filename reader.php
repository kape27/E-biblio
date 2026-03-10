<?php
/**
 * E-Lib Digital Library - Universal Reader
 * Premium Mobile Experience with Smooth Gestures
 */

session_start();

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/auth.php';
require_once 'includes/book_manager.php';
require_once 'includes/csrf_protection.php';

// Initialize CSRF protection
CSRFProtectionManager::initialize();

$auth = new AuthManager();
$auth->requireRole('user');

$db = DatabaseManager::getInstance();
$bookManager = new BookManager();
$userId = $_SESSION['user_id'];

$bookId = (int)($_GET['id'] ?? 0);
if ($bookId <= 0) {
    redirect_with_message('user/catalog.php', 'Livre non trouvé.', 'error');
}

$book = $bookManager->getBookById($bookId);
if (!$book) {
    redirect_with_message('user/catalog.php', 'Livre non trouvé.', 'error');
}

$filePath = 'uploads/books/' . $book['file_path'];
if (!file_exists($filePath)) {
    redirect_with_message('user/catalog.php', 'Fichier du livre introuvable.', 'error');
}

$fileType = strtolower($book['file_type']);
$isPdf = ($fileType === 'pdf');
$isEpub = ($fileType === 'epub');

if (!$isPdf && !$isEpub) {
    redirect_with_message('user/catalog.php', 'Format non supporté.', 'error');
}

$progress = $db->fetchOne("SELECT * FROM reading_progress WHERE user_id = ? AND book_id = ?", [$userId, $bookId]);
$lastPosition = $progress['last_position'] ?? null;

if ($progress) {
    $db->executeQuery("UPDATE reading_progress SET updated_at = NOW() WHERE id = ?", [$progress['id']]);
} else {
    $db->executeQuery("INSERT INTO reading_progress (user_id, book_id, progress_data, last_position) VALUES (?, ?, '{}', NULL)", [$userId, $bookId]);
}

log_action('book_read', "Started reading: {$book['title']}", $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0f172a">
    <?= CSRFProtectionManager::generateTokenMeta() ?>
    <title><?= escape_html($book['title']) ?> - E-Lib Reader</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <?php if ($isPdf): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <?php endif; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0f1a; 
            color: #fff;
            height: 100%; 
            overflow: hidden;
            touch-action: none;
        }
        
        /* Main Container */
        #app { 
            height: 100%; 
            display: flex; 
            flex-direction: column;
            position: relative;
        }
        
        /* Header - Slide down animation */
        .header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: linear-gradient(to bottom, rgba(15, 23, 42, 0.98) 0%, rgba(15, 23, 42, 0.9) 80%, transparent 100%);
            padding: 12px 16px 24px;
            transform: translateY(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .header.visible { transform: translateY(0); }
        .header-content {
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 100%;
        }
        .back-btn {
            width: 44px; height: 44px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            flex-shrink: 0;
            transition: background 0.2s;
        }
        .back-btn:active { background: rgba(99, 102, 241, 0.3); }
        .book-info { flex: 1; min-width: 0; text-align: center; }
        .book-title { 
            font-size: 15px; 
            font-weight: 600; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
        }
        .book-author { 
            font-size: 12px; 
            color: #94a3b8; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
        }
        .header-actions { display: flex; gap: 8px; }
        .header-btn {
            width: 44px; height: 44px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 12px;
            color: #fff;
            cursor: pointer;
            transition: background 0.2s;
        }
        .header-btn:active { background: rgba(99, 102, 241, 0.3); }
        
        /* PDF Viewer */
        #pdf-container {
            flex: 1;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #pdf-wrapper {
            position: relative;
            transform-origin: center center;
            transition: none;
        }
        #pdf-wrapper.animating {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #pdf-canvas {
            display: block;
            background: #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            border-radius: 4px;
        }
        
        /* Page Turn Indicators */
        .page-indicator {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            pointer-events: none;
            z-index: 100;
        }
        .page-indicator.left { left: 0; }
        .page-indicator.right { right: 0; }
        .page-indicator.active { opacity: 1; }
        .page-indicator svg {
            width: 32px;
            height: 32px;
            color: rgba(255,255,255,0.6);
        }
        
        /* Bottom Controls */
        .controls {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 1000;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.98) 0%, rgba(15, 23, 42, 0.9) 80%, transparent 100%);
            padding: 24px 16px 20px;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .controls.visible { transform: translateY(0); }
        
        /* Progress Bar */
        .progress-container {
            margin-bottom: 16px;
            padding: 0 8px;
        }
        .progress-bar {
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 12px;
            color: #94a3b8;
        }
        
        /* Page Slider */
        .slider-container {
            padding: 0 8px;
            margin-bottom: 16px;
        }
        .page-slider {
            width: 100%;
            height: 36px;
            -webkit-appearance: none;
            background: transparent;
            cursor: pointer;
        }
        .page-slider::-webkit-slider-runnable-track {
            height: 6px;
            background: rgba(255,255,255,0.15);
            border-radius: 3px;
        }
        .page-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 50%;
            margin-top: -9px;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }
        
        /* Control Buttons */
        .control-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .ctrl-btn {
            width: 56px; height: 56px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            color: #fff;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ctrl-btn:active { 
            background: rgba(99, 102, 241, 0.3);
            transform: scale(0.95);
        }
        .ctrl-btn:disabled { 
            opacity: 0.3; 
            pointer-events: none;
        }
        .ctrl-btn.primary {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
        }
        .ctrl-btn svg { width: 24px; height: 24px; }
        
        .page-display {
            min-width: 100px;
            text-align: center;
            padding: 0 16px;
        }
        .page-current {
            font-size: 24px;
            font-weight: 600;
            color: #fff;
        }
        .page-total {
            font-size: 13px;
            color: #64748b;
        }
        
        /* Zoom indicator */
        .zoom-indicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.2s;
            pointer-events: none;
            z-index: 2000;
        }
        .zoom-indicator.visible { opacity: 1; }
        
        /* Loading */
        .loading {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #0a0f1a;
            z-index: 500;
        }
        .spinner {
            width: 48px; height: 48px;
            border: 3px solid rgba(99, 102, 241, 0.2);
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { margin-top: 16px; color: #64748b; font-size: 14px; }
        
        /* Settings Panel */
        .settings-panel {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #1e293b;
            border-radius: 24px 24px 0 0;
            padding: 24px;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2000;
        }
        .settings-panel.visible { transform: translateY(0); }
        .settings-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
            z-index: 1999;
        }
        .settings-backdrop.visible { opacity: 1; visibility: visible; }
        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .settings-title { font-size: 18px; font-weight: 600; }
        .settings-close {
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
        }
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .setting-label { font-size: 15px; color: #e2e8f0; }
        .setting-value {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .setting-btn {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 10px;
            color: #fff;
            cursor: pointer;
        }
        .setting-btn:active { background: rgba(99, 102, 241, 0.3); }
        .setting-display {
            min-width: 50px;
            text-align: center;
            font-weight: 600;
        }
        
        /* View mode buttons */
        .view-modes {
            display: flex;
            gap: 8px;
        }
        .view-mode-btn {
            flex: 1;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: #94a3b8;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .view-mode-btn.active {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
            color: #fff;
        }
        
        /* EPUB Viewer */
        #epub-container {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 20px;
        }
        #epub-content {
            max-width: 720px;
            margin: 0 auto;
            color: #e2e8f0;
            font-size: 18px;
            line-height: 1.8;
        }
        #epub-content img { max-width: 100%; height: auto; }
        
        /* Safe area for notched phones */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .controls { padding-bottom: calc(20px + env(safe-area-inset-bottom)); }
            .settings-panel { padding-bottom: calc(24px + env(safe-area-inset-bottom)); }
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Header -->
        <header class="header" id="header">
            <div class="header-content">
                <a href="user/catalog.php" class="back-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="book-info">
                    <div class="book-title"><?= escape_html($book['title']) ?></div>
                    <div class="book-author"><?= escape_html($book['author']) ?></div>
                </div>
                <div class="header-actions">
                    <button class="header-btn" id="settingsBtn" title="Paramètres">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </button>
                    <button class="header-btn" id="fullscreenBtn" title="Plein écran">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Turn Indicators -->
        <div class="page-indicator left" id="indicatorLeft">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </div>
        <div class="page-indicator right" id="indicatorRight">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>

        <?php if ($isPdf): ?>
        <!-- PDF Viewer -->
        <div id="pdf-container">
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <div class="loading-text">Chargement du livre...</div>
            </div>
            <div id="pdf-wrapper">
                <canvas id="pdf-canvas"></canvas>
            </div>
        </div>
        <?php else: ?>
        <!-- EPUB Viewer -->
        <div id="epub-container">
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <div class="loading-text">Chargement du livre...</div>
            </div>
            <div id="epub-content"></div>
        </div>
        <?php endif; ?>

        <!-- Zoom Indicator -->
        <div class="zoom-indicator" id="zoomIndicator">100%</div>

        <!-- Bottom Controls -->
        <div class="controls" id="controls">
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                </div>
                <div class="progress-text">
                    <span id="progressPercent">0%</span>
                    <span id="progressPages">Page 1 sur 1</span>
                </div>
            </div>
            
            <!-- Page Slider -->
            <div class="slider-container">
                <input type="range" class="page-slider" id="pageSlider" min="1" max="1" value="1">
            </div>
            
            <!-- Control Buttons -->
            <div class="control-row">
                <button class="ctrl-btn" id="prevBtn" disabled>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                
                <?php if ($isPdf): ?>
                <button class="ctrl-btn" id="zoomOutBtn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                    </svg>
                </button>
                <?php endif; ?>
                
                <div class="page-display">
                    <div class="page-current" id="pageCurrent">1</div>
                    <div class="page-total">sur <span id="pageTotal">1</span></div>
                </div>
                
                <?php if ($isPdf): ?>
                <button class="ctrl-btn" id="zoomInBtn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                    </svg>
                </button>
                <?php endif; ?>
                
                <button class="ctrl-btn" id="nextBtn" disabled>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Settings Panel -->
        <div class="settings-backdrop" id="settingsBackdrop"></div>
        <div class="settings-panel" id="settingsPanel">
            <div class="settings-header">
                <span class="settings-title">Paramètres</span>
                <button class="settings-close" id="settingsClose">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <?php if ($isPdf): ?>
            <div class="setting-row">
                <span class="setting-label">Mode d'affichage</span>
            </div>
            <div class="view-modes" style="margin-bottom: 16px;">
                <button class="view-mode-btn active" data-mode="fit-width">Largeur</button>
                <button class="view-mode-btn" data-mode="fit-page">Page entière</button>
                <button class="view-mode-btn" data-mode="custom">Personnalisé</button>
            </div>
            
            <div class="setting-row">
                <span class="setting-label">Zoom</span>
                <div class="setting-value">
                    <button class="setting-btn" id="settingZoomOut">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </button>
                    <span class="setting-display" id="zoomDisplay">100%</span>
                    <button class="setting-btn" id="settingZoomIn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="setting-row" style="border-bottom: none;">
                <span class="setting-label">Aller à la page</span>
                <div class="setting-value">
                    <input type="number" id="gotoPage" min="1" style="width: 70px; padding: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-align: center; font-size: 16px;">
                    <button class="setting-btn" id="gotoBtn" style="background: #6366f1;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        const bookId = <?= $bookId ?>;
        const fileUrl = 'api/serve_book.php?id=<?= $bookId ?>';
        const savedPosition = <?= json_encode($lastPosition) ?>;
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) || window.innerWidth <= 768;
        
        // UI Elements
        const header = document.getElementById('header');
        const controls = document.getElementById('controls');
        const settingsPanel = document.getElementById('settingsPanel');
        const settingsBackdrop = document.getElementById('settingsBackdrop');
        const zoomIndicator = document.getElementById('zoomIndicator');
        
        // State
        let uiVisible = true;
        let uiTimeout = null;
        
        // Toggle UI visibility
        function toggleUI(show = null) {
            if (show === null) show = !uiVisible;
            uiVisible = show;
            header.classList.toggle('visible', show);
            controls.classList.toggle('visible', show);
            
            clearTimeout(uiTimeout);
            if (show) {
                uiTimeout = setTimeout(() => toggleUI(false), 4000);
            }
        }
        
        // Show UI initially
        toggleUI(true);
        
        // Settings panel
        document.getElementById('settingsBtn').onclick = () => {
            settingsPanel.classList.add('visible');
            settingsBackdrop.classList.add('visible');
            toggleUI(true);
            clearTimeout(uiTimeout);
        };
        
        function closeSettings() {
            settingsPanel.classList.remove('visible');
            settingsBackdrop.classList.remove('visible');
        }
        
        document.getElementById('settingsClose').onclick = closeSettings;
        settingsBackdrop.onclick = closeSettings;
        
        // Fullscreen
        document.getElementById('fullscreenBtn').onclick = () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen?.() || 
                document.documentElement.webkitRequestFullscreen?.();
            } else {
                document.exitFullscreen?.() || document.webkitExitFullscreen?.();
            }
        };
        
        // Save progress
        function saveProgress(position) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch('api/save_progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    book_id: bookId, 
                    position: position,
                    csrf_token: csrfToken
                })
            }).catch(console.error);
        }
        
        // Show zoom indicator
        function showZoomIndicator(value) {
            zoomIndicator.textContent = Math.round(value * 100) + '%';
            zoomIndicator.classList.add('visible');
            setTimeout(() => zoomIndicator.classList.remove('visible'), 800);
        }
    </script>

    <?php if ($isPdf): ?>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        // PDF State
        let pdfDoc = null;
        let pageNum = savedPosition ? parseInt(savedPosition) : 1;
        let pageRendering = false;
        let pageNumPending = null;
        let scale = 1;
        let viewMode = 'fit-width'; // fit-width, fit-page, custom
        let baseScale = 1;
        
        // Elements
        const container = document.getElementById('pdf-container');
        const wrapper = document.getElementById('pdf-wrapper');
        const canvas = document.getElementById('pdf-canvas');
        const ctx = canvas.getContext('2d');
        const loading = document.getElementById('loading');
        const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
        
        // UI Elements
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const pageSlider = document.getElementById('pageSlider');
        const pageCurrent = document.getElementById('pageCurrent');
        const pageTotal = document.getElementById('pageTotal');
        const progressFill = document.getElementById('progressFill');
        const progressPercent = document.getElementById('progressPercent');
        const progressPages = document.getElementById('progressPages');
        const zoomDisplay = document.getElementById('zoomDisplay');
        const indicatorLeft = document.getElementById('indicatorLeft');
        const indicatorRight = document.getElementById('indicatorRight');
        
        // Calculate optimal scale
        function calculateScale(page) {
            const viewport = page.getViewport({ scale: 1 });
            const containerW = container.clientWidth - 20;
            const containerH = container.clientHeight - 20;
            
            if (viewMode === 'fit-width') {
                baseScale = containerW / viewport.width;
            } else if (viewMode === 'fit-page') {
                baseScale = Math.min(containerW / viewport.width, containerH / viewport.height);
            }
            // For custom mode, baseScale stays as is
            
            return baseScale * scale;
        }
        
        // Render page
        async function renderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
                return;
            }
            
            pageRendering = true;
            
            try {
                const page = await pdfDoc.getPage(num);
                const finalScale = calculateScale(page);
                const viewport = page.getViewport({ scale: finalScale });
                
                // Set canvas size for high DPI
                canvas.width = viewport.width * pixelRatio;
                canvas.height = viewport.height * pixelRatio;
                canvas.style.width = viewport.width + 'px';
                canvas.style.height = viewport.height + 'px';
                
                ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
                
                await page.render({
                    canvasContext: ctx,
                    viewport: viewport,
                    intent: 'display'
                }).promise;
                
                pageRendering = false;
                
                if (pageNumPending !== null) {
                    const pending = pageNumPending;
                    pageNumPending = null;
                    renderPage(pending);
                }
            } catch (err) {
                console.error('Render error:', err);
                pageRendering = false;
            }
            
            updateUI();
        }
        
        // Update UI
        function updateUI() {
            const total = pdfDoc?.numPages || 1;
            const percent = Math.round((pageNum / total) * 100);
            
            pageCurrent.textContent = pageNum;
            pageTotal.textContent = total;
            pageSlider.max = total;
            pageSlider.value = pageNum;
            progressFill.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
            progressPages.textContent = `Page ${pageNum} sur ${total}`;
            
            prevBtn.disabled = pageNum <= 1;
            nextBtn.disabled = pageNum >= total;
            
            zoomDisplay.textContent = Math.round(scale * 100) + '%';
            
            saveProgress(pageNum.toString());
        }
        
        // Navigation
        function goToPage(num, animate = false) {
            num = Math.max(1, Math.min(num, pdfDoc?.numPages || 1));
            if (num === pageNum) return;
            
            pageNum = num;
            
            if (animate) {
                wrapper.classList.add('animating');
                setTimeout(() => wrapper.classList.remove('animating'), 300);
            }
            
            renderPage(pageNum);
        }
        
        function prevPage() {
            if (pageNum > 1) {
                indicatorLeft.classList.add('active');
                setTimeout(() => indicatorLeft.classList.remove('active'), 200);
                goToPage(pageNum - 1, true);
            }
        }
        
        function nextPage() {
            if (pageNum < (pdfDoc?.numPages || 1)) {
                indicatorRight.classList.add('active');
                setTimeout(() => indicatorRight.classList.remove('active'), 200);
                goToPage(pageNum + 1, true);
            }
        }
        
        // Zoom
        function setZoom(newScale, showIndicator = true) {
            scale = Math.max(0.5, Math.min(4, newScale));
            viewMode = 'custom';
            document.querySelectorAll('.view-mode-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.mode === 'custom');
            });
            if (showIndicator) showZoomIndicator(scale);
            renderPage(pageNum);
        }
        
        function zoomIn() { setZoom(scale + 0.25); }
        function zoomOut() { setZoom(scale - 0.25); }
        
        // Event listeners
        prevBtn.onclick = prevPage;
        nextBtn.onclick = nextPage;
        zoomInBtn.onclick = zoomIn;
        zoomOutBtn.onclick = zoomOut;
        
        pageSlider.oninput = () => {
            goToPage(parseInt(pageSlider.value));
        };
        
        // Settings
        document.getElementById('settingZoomIn')?.addEventListener('click', zoomIn);
        document.getElementById('settingZoomOut')?.addEventListener('click', zoomOut);
        
        document.querySelectorAll('.view-mode-btn').forEach(btn => {
            btn.onclick = () => {
                viewMode = btn.dataset.mode;
                scale = 1;
                document.querySelectorAll('.view-mode-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                renderPage(pageNum);
            };
        });
        
        document.getElementById('gotoBtn').onclick = () => {
            const page = parseInt(document.getElementById('gotoPage').value);
            if (page >= 1 && page <= (pdfDoc?.numPages || 1)) {
                goToPage(page);
                closeSettings();
            }
        };
        
        // Keyboard
        document.addEventListener('keydown', e => {
            if (e.target.tagName === 'INPUT') return;
            switch(e.key) {
                case 'ArrowLeft': prevPage(); break;
                case 'ArrowRight': case ' ': nextPage(); e.preventDefault(); break;
                case '+': case '=': zoomIn(); break;
                case '-': zoomOut(); break;
                case 'Escape': toggleUI(); break;
            }
        });
        
        // Touch gestures
        let touchStartX = 0, touchStartY = 0;
        let touchStartTime = 0;
        let lastTapTime = 0;
        let pinchStartDist = 0;
        let pinchStartScale = 1;
        
        container.addEventListener('touchstart', e => {
            if (e.touches.length === 1) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                touchStartTime = Date.now();
            } else if (e.touches.length === 2) {
                pinchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                pinchStartScale = scale;
            }
        }, { passive: true });
        
        container.addEventListener('touchmove', e => {
            if (e.touches.length === 2 && pinchStartDist > 0) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                const newScale = pinchStartScale * (dist / pinchStartDist);
                scale = Math.max(0.5, Math.min(4, newScale));
                zoomDisplay.textContent = Math.round(scale * 100) + '%';
            }
        }, { passive: true });
        
        container.addEventListener('touchend', e => {
            // Pinch end
            if (pinchStartDist > 0) {
                pinchStartDist = 0;
                viewMode = 'custom';
                document.querySelectorAll('.view-mode-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.mode === 'custom');
                });
                showZoomIndicator(scale);
                renderPage(pageNum);
                return;
            }
            
            if (e.changedTouches.length !== 1) return;
            
            const touch = e.changedTouches[0];
            const deltaX = touch.clientX - touchStartX;
            const deltaY = touch.clientY - touchStartY;
            const deltaTime = Date.now() - touchStartTime;
            const now = Date.now();
            
            // Double tap to zoom
            if (now - lastTapTime < 300 && Math.abs(deltaX) < 30 && Math.abs(deltaY) < 30) {
                if (scale > 1.2) {
                    scale = 1;
                    viewMode = 'fit-width';
                } else {
                    scale = 2;
                    viewMode = 'custom';
                }
                document.querySelectorAll('.view-mode-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.mode === viewMode);
                });
                showZoomIndicator(scale);
                renderPage(pageNum);
                lastTapTime = 0;
                return;
            }
            lastTapTime = now;
            
            // Swipe to change page
            if (deltaTime < 300 && Math.abs(deltaY) < 100) {
                if (deltaX > 60) {
                    prevPage();
                    return;
                } else if (deltaX < -60) {
                    nextPage();
                    return;
                }
            }
            
            // Tap zones
            if (Math.abs(deltaX) < 20 && Math.abs(deltaY) < 20 && deltaTime < 200) {
                const x = touch.clientX;
                const w = container.clientWidth;
                
                if (x < w * 0.25) {
                    prevPage();
                } else if (x > w * 0.75) {
                    nextPage();
                } else {
                    toggleUI();
                }
            }
        }, { passive: true });
        
        // Click for desktop
        container.addEventListener('click', e => {
            if (isMobile) return;
            const x = e.clientX;
            const w = container.clientWidth;
            
            if (x < w * 0.2) prevPage();
            else if (x > w * 0.8) nextPage();
            else toggleUI();
        });
        
        // Load PDF
        pdfjsLib.getDocument(fileUrl).promise.then(pdf => {
            pdfDoc = pdf;
            loading.style.display = 'none';
            
            if (pageNum > pdf.numPages) pageNum = 1;
            document.getElementById('gotoPage').max = pdf.numPages;
            
            renderPage(pageNum);
        }).catch(err => {
            console.error('PDF load error:', err);
            loading.innerHTML = `
                <div style="text-align: center; color: #f87171;">
                    <svg style="width: 48px; height: 48px; margin-bottom: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p>Erreur de chargement</p>
                    <a href="user/catalog.php" style="display: inline-block; margin-top: 16px; padding: 12px 24px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none;">Retour</a>
                </div>
            `;
        });
        
        // Resize
        window.addEventListener('resize', () => {
            if (pdfDoc) renderPage(pageNum);
        });
    </script>
    <?php else: ?>
    <script>
        // EPUB Reader
        const container = document.getElementById('epub-container');
        const content = document.getElementById('epub-content');
        const loading = document.getElementById('loading');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const pageSlider = document.getElementById('pageSlider');
        const pageCurrent = document.getElementById('pageCurrent');
        const pageTotal = document.getElementById('pageTotal');
        const progressFill = document.getElementById('progressFill');
        const progressPercent = document.getElementById('progressPercent');
        const progressPages = document.getElementById('progressPages');
        
        let currentChapter = savedPosition ? parseInt(savedPosition) : 0;
        let totalChapters = 0;
        
        async function loadChapter(index) {
            if (index < 0 || index >= totalChapters) return;
            currentChapter = index;
            
            try {
                const res = await fetch(`api/epub_content.php?id=${bookId}&action=chapter&chapter=${index}`);
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);
                
                content.innerHTML = data.content;
                container.scrollTop = 0;
                updateUI();
                saveProgress(currentChapter.toString());
            } catch (err) {
                content.innerHTML = `<p style="color: #f87171; text-align: center;">Erreur: ${err.message}</p>`;
            }
        }
        
        function updateUI() {
            const percent = Math.round(((currentChapter + 1) / totalChapters) * 100);
            
            pageCurrent.textContent = currentChapter + 1;
            pageTotal.textContent = totalChapters;
            pageSlider.max = totalChapters;
            pageSlider.value = currentChapter + 1;
            progressFill.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
            progressPages.textContent = `Chapitre ${currentChapter + 1} sur ${totalChapters}`;
            
            prevBtn.disabled = currentChapter <= 0;
            nextBtn.disabled = currentChapter >= totalChapters - 1;
        }
        
        prevBtn.onclick = () => loadChapter(currentChapter - 1);
        nextBtn.onclick = () => loadChapter(currentChapter + 1);
        pageSlider.oninput = () => loadChapter(parseInt(pageSlider.value) - 1);
        
        document.getElementById('gotoBtn').onclick = () => {
            const chapter = parseInt(document.getElementById('gotoPage').value) - 1;
            if (chapter >= 0 && chapter < totalChapters) {
                loadChapter(chapter);
                closeSettings();
            }
        };
        
        // Touch gestures
        let touchStartX = 0;
        container.addEventListener('touchstart', e => {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });
        
        container.addEventListener('touchend', e => {
            const deltaX = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(deltaX) > 80) {
                if (deltaX > 0) loadChapter(currentChapter - 1);
                else loadChapter(currentChapter + 1);
            }
        }, { passive: true });
        
        // Tap to toggle UI
        container.addEventListener('click', e => {
            const x = e.clientX;
            const w = container.clientWidth;
            if (x > w * 0.25 && x < w * 0.75) {
                toggleUI();
            }
        });
        
        // Keyboard
        document.addEventListener('keydown', e => {
            if (e.target.tagName === 'INPUT') return;
            if (e.key === 'ArrowLeft') loadChapter(currentChapter - 1);
            if (e.key === 'ArrowRight') loadChapter(currentChapter + 1);
        });
        
        // Load EPUB
        (async () => {
            try {
                const res = await fetch(`api/epub_content.php?id=${bookId}&action=toc`);
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);
                
                totalChapters = data.total;
                loading.style.display = 'none';
                
                if (currentChapter >= totalChapters) currentChapter = 0;
                document.getElementById('gotoPage').max = totalChapters;
                
                await loadChapter(currentChapter);
            } catch (err) {
                loading.innerHTML = `
                    <div style="text-align: center; color: #f87171;">
                        <p style="margin-bottom: 16px;">${err.message}</p>
                        <a href="user/catalog.php" style="padding: 12px 24px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none;">Retour</a>
                    </div>
                `;
            }
        })();
    </script>
    <?php endif; ?>
</body>
</html>
