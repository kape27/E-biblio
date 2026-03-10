<?php
/**
 * Create Database Indexes for Reading History Optimization
 * This script adds the necessary indexes for optimal history query performance
 */

require_once __DIR__ . '/../config/database.php';

echo "🔧 E-Lib - Création des index pour l'historique de lecture\n";
echo "========================================================\n\n";

try {
    $db = DatabaseManager::getInstance();
    
    echo "1. Création de l'index pour les requêtes d'historique chronologique...\n";
    $db->executeQuery(
        "CREATE INDEX IF NOT EXISTS idx_reading_progress_history 
         ON reading_progress (user_id, updated_at DESC)"
    );
    echo "   ✅ Index idx_reading_progress_history créé\n\n";
    
    echo "2. Création de l'index pour le filtrage par données de progression...\n";
    $db->executeQuery(
        "CREATE INDEX IF NOT EXISTS idx_reading_progress_data 
         ON reading_progress (user_id, progress_data(255))"
    );
    echo "   ✅ Index idx_reading_progress_data créé\n\n";
    
    echo "3. Création de l'index composite pour la recherche de livres...\n";
    $db->executeQuery(
        "CREATE INDEX IF NOT EXISTS idx_books_history_search 
         ON books (title, author)"
    );
    echo "   ✅ Index idx_books_history_search créé\n\n";
    
    echo "4. Ajout de l'index pour les requêtes de dernière lecture...\n";
    $db->executeQuery(
        "CREATE INDEX IF NOT EXISTS idx_reading_progress_last_read 
         ON reading_progress (user_id, updated_at DESC, book_id)"
    );
    echo "   ✅ Index idx_reading_progress_last_read créé\n\n";
    
    echo "✅ Tous les index ont été créés avec succès!\n";
    echo "   Les requêtes d'historique seront maintenant optimisées.\n\n";
    
    // Vérifier les index créés
    echo "5. Vérification des index créés:\n";
    $indexes = $db->fetchAll("SHOW INDEX FROM reading_progress WHERE Key_name LIKE 'idx_%'");
    
    foreach ($indexes as $index) {
        echo "   - " . $index['Key_name'] . " sur colonne(s): " . $index['Column_name'] . "\n";
    }
    
    $bookIndexes = $db->fetchAll("SHOW INDEX FROM books WHERE Key_name LIKE 'idx_%'");
    foreach ($bookIndexes as $index) {
        echo "   - " . $index['Key_name'] . " sur colonne(s): " . $index['Column_name'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la création des index: " . $e->getMessage() . "\n";
    echo "   Vérifiez que la base de données est accessible et que les tables existent.\n";
    exit(1);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Optimisation terminée - " . date('Y-m-d H:i:s') . "\n";
?>