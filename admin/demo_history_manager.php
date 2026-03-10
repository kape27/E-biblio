<?php
/**
 * Demonstration of HistoryManager functionality with sample data
 */

require_once __DIR__ . '/../includes/history_manager.php';
require_once __DIR__ . '/../includes/reading_progress_manager.php';

echo "🔧 E-Lib - Démonstration du HistoryManager\n";
echo "==========================================\n\n";

try {
    $historyManager = new HistoryManager();
    $progressManager = new ReadingProgressManager();
    
    // Test with user ID 1 (admin user)
    $testUserId = 1;
    
    echo "1. Démonstration des fonctionnalités principales:\n\n";
    
    // Test getUserHistory with different options
    echo "   a) Récupération de l'historique complet:\n";
    $fullHistory = $historyManager->getUserHistory($testUserId);
    echo "      - Entrées trouvées: " . count($fullHistory['entries']) . "\n";
    echo "      - Total: " . $fullHistory['pagination']['total'] . "\n";
    echo "      - Limite par page: " . $fullHistory['pagination']['limit'] . "\n\n";
    
    echo "   b) Récupération avec pagination (5 éléments):\n";
    $paginatedHistory = $historyManager->getUserHistory($testUserId, ['limit' => 5]);
    echo "      - Entrées: " . count($paginatedHistory['entries']) . "\n";
    echo "      - Page courante: " . $paginatedHistory['pagination']['current_page'] . "\n";
    echo "      - Pages totales: " . $paginatedHistory['pagination']['total_pages'] . "\n\n";
    
    echo "   c) Test des filtres de recherche:\n";
    $searchResults = $historyManager->getUserHistory($testUserId, [
        'filters' => ['search' => 'livre']
    ]);
    echo "      - Résultats pour 'livre': " . count($searchResults['entries']) . "\n";
    
    $statusResults = $historyManager->getUserHistory($testUserId, [
        'filters' => ['status' => 'in_progress']
    ]);
    echo "      - Livres en cours: " . count($statusResults['entries']) . "\n\n";
    
    echo "2. Test du formatage des entrées:\n";
    if (!empty($fullHistory['entries'])) {
        $entry = $fullHistory['entries'][0];
        echo "   - Titre: " . $entry['title'] . "\n";
        echo "   - Auteur: " . $entry['author'] . "\n";
        echo "   - Progression: " . $entry['progress_percentage'] . "%\n";
        echo "   - Statut: " . $entry['reading_status'] . "\n";
        echo "   - Dernière lecture: " . $entry['last_read_relative'] . "\n";
        echo "   - Temps estimé restant: " . $entry['estimated_time_remaining'] . "\n";
        echo "   - Signets: " . $entry['bookmarks_count'] . "\n\n";
    } else {
        echo "   ℹ️  Aucune entrée d'historique trouvée (base de données vide)\n\n";
    }
    
    echo "3. Test des fonctions de gestion:\n";
    
    // Test removeHistoryEntry (simulation)
    echo "   a) Test de suppression d'entrée (simulation):\n";
    if (!empty($fullHistory['entries'])) {
        $bookId = $fullHistory['entries'][0]['book_id'];
        echo "      - Tentative de suppression du livre ID: $bookId\n";
        $removeResult = $historyManager->removeHistoryEntry($testUserId, $bookId);
        echo "      - Résultat: " . ($removeResult ? "✅ Succès" : "❌ Échec") . "\n";
        
        // Vérifier que l'entrée est maintenant cachée
        $historyAfterRemove = $historyManager->getUserHistory($testUserId);
        echo "      - Entrées après suppression: " . count($historyAfterRemove['entries']) . "\n\n";
    } else {
        echo "      ℹ️  Aucune entrée à supprimer\n\n";
    }
    
    echo "4. Vérification des index de performance:\n";
    $db = DatabaseManager::getInstance();
    $indexes = $db->fetchAll("SHOW INDEX FROM reading_progress WHERE Key_name LIKE 'idx_%'");
    echo "   - Index créés pour reading_progress:\n";
    foreach ($indexes as $index) {
        echo "     * " . $index['Key_name'] . " (" . $index['Column_name'] . ")\n";
    }
    
    $bookIndexes = $db->fetchAll("SHOW INDEX FROM books WHERE Key_name LIKE 'idx_%'");
    echo "   - Index créés pour books:\n";
    foreach ($bookIndexes as $index) {
        echo "     * " . $index['Key_name'] . " (" . $index['Column_name'] . ")\n";
    }
    
    echo "\n✅ Démonstration terminée avec succès!\n";
    echo "   Le HistoryManager est prêt à être utilisé.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la démonstration: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Démonstration terminée - " . date('Y-m-d H:i:s') . "\n";
?>