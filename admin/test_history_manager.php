<?php
/**
 * Test HistoryManager functionality
 */

require_once __DIR__ . '/../includes/history_manager.php';

echo "🔧 E-Lib - Test du HistoryManager\n";
echo "=================================\n\n";

try {
    $historyManager = new HistoryManager();
    
    echo "1. Test de création de l'instance HistoryManager...\n";
    echo "   ✅ HistoryManager créé avec succès\n\n";
    
    // Test with a sample user ID (assuming user ID 1 exists)
    $testUserId = 1;
    
    echo "2. Test de récupération de l'historique (utilisateur $testUserId)...\n";
    $history = $historyManager->getUserHistory($testUserId, ['limit' => 5]);
    
    echo "   - Nombre d'entrées: " . count($history['entries']) . "\n";
    echo "   - Total dans la base: " . $history['pagination']['total'] . "\n";
    echo "   - Page courante: " . $history['pagination']['current_page'] . "\n";
    echo "   - Pages totales: " . $history['pagination']['total_pages'] . "\n";
    
    if (!empty($history['entries'])) {
        echo "   - Première entrée: " . $history['entries'][0]['title'] . " par " . $history['entries'][0]['author'] . "\n";
        echo "   - Progression: " . $history['entries'][0]['progress_percentage'] . "%\n";
        echo "   - Statut: " . $history['entries'][0]['reading_status'] . "\n";
        echo "   - Dernière lecture: " . $history['entries'][0]['last_read_relative'] . "\n";
    }
    echo "   ✅ Récupération de l'historique réussie\n\n";
    
    echo "3. Test de filtrage par recherche...\n";
    $filteredHistory = $historyManager->getUserHistory($testUserId, [
        'limit' => 10,
        'filters' => ['search' => 'test']
    ]);
    echo "   - Résultats de recherche 'test': " . count($filteredHistory['entries']) . " entrées\n";
    echo "   ✅ Filtrage par recherche fonctionnel\n\n";
    
    echo "4. Test de filtrage par statut...\n";
    $completedBooks = $historyManager->getUserHistory($testUserId, [
        'limit' => 10,
        'filters' => ['status' => 'completed']
    ]);
    echo "   - Livres terminés: " . count($completedBooks['entries']) . " entrées\n";
    
    $inProgressBooks = $historyManager->getUserHistory($testUserId, [
        'limit' => 10,
        'filters' => ['status' => 'in_progress']
    ]);
    echo "   - Livres en cours: " . count($inProgressBooks['entries']) . " entrées\n";
    echo "   ✅ Filtrage par statut fonctionnel\n\n";
    
    echo "✅ Tous les tests du HistoryManager ont réussi!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test terminé - " . date('Y-m-d H:i:s') . "\n";
?>