<?php
/**
 * EPUB Content Extractor API
 */

session_start();
header('Content-Type: application/json');

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$bookId = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'toc';
$chapter = (int)($_GET['chapter'] ?? 0);

if ($bookId <= 0) {
    die(json_encode(['error' => 'Invalid book ID']));
}

$db = DatabaseManager::getInstance();
$book = $db->fetchOne("SELECT file_path, file_type FROM books WHERE id = ?", [$bookId]);

if (!$book || $book['file_type'] !== 'epub') {
    die(json_encode(['error' => 'Book not found or not EPUB']));
}

$epubPath = dirname(__DIR__) . '/uploads/books/' . $book['file_path'];

if (!file_exists($epubPath)) {
    die(json_encode(['error' => 'EPUB file not found']));
}

$zip = new ZipArchive();
if ($zip->open($epubPath) !== true) {
    die(json_encode(['error' => 'Cannot open EPUB file']));
}

// Find container.xml
$containerXml = $zip->getFromName('META-INF/container.xml');
if (!$containerXml) {
    $zip->close();
    die(json_encode(['error' => 'Invalid EPUB - no container.xml']));
}

// Extract OPF path
preg_match('/full-path="([^"]+)"/', $containerXml, $matches);
$opfPath = $matches[1] ?? '';

if (empty($opfPath)) {
    $zip->close();
    die(json_encode(['error' => 'Cannot find OPF path']));
}

$opfDir = dirname($opfPath);
if ($opfDir === '.') $opfDir = '';

// Read OPF
$opfContent = $zip->getFromName($opfPath);
if (!$opfContent) {
    $zip->close();
    die(json_encode(['error' => 'Cannot read OPF']));
}

// Parse OPF
libxml_use_internal_errors(true);
$opf = simplexml_load_string($opfContent);
if (!$opf) {
    $zip->close();
    die(json_encode(['error' => 'Cannot parse OPF']));
}

// Build manifest
$manifest = [];
foreach ($opf->manifest->item as $item) {
    $id = (string)$item['id'];
    $href = (string)$item['href'];
    $manifest[$id] = $href;
}

// Build spine
$spine = [];
foreach ($opf->spine->itemref as $itemref) {
    $idref = (string)$itemref['idref'];
    if (isset($manifest[$idref])) {
        $spine[] = $manifest[$idref];
    }
}

if (empty($spine)) {
    $zip->close();
    die(json_encode(['error' => 'No chapters in spine']));
}

// Handle TOC action
if ($action === 'toc') {
    $zip->close();
    echo json_encode(['total' => count($spine)]);
    exit;
}

// Handle chapter action
if ($action === 'chapter') {
    if ($chapter < 0 || $chapter >= count($spine)) {
        $zip->close();
        die(json_encode(['error' => 'Invalid chapter index']));
    }
    
    $href = $spine[$chapter];
    $path = $opfDir ? "$opfDir/$href" : $href;
    
    $content = $zip->getFromName($path);
    if (!$content) {
        $content = $zip->getFromName($href);
    }
    
    $zip->close();
    
    if (!$content) {
        die(json_encode(['error' => 'Chapter not found']));
    }
    
    // Extract body content only
    if (preg_match('/<body[^>]*>(.*)<\/body>/is', $content, $bodyMatch)) {
        $content = $bodyMatch[1];
    }
    
    // Remove XML declaration and doctype
    $content = preg_replace('/<\?xml[^>]*\?>/i', '', $content);
    $content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $content);
    
    echo json_encode([
        'chapter' => $chapter,
        'total' => count($spine),
        'content' => $content
    ]);
    exit;
}

$zip->close();
echo json_encode(['error' => 'Invalid action']);
