<?php
/**
 * Favorites Manager for E-Lib Digital Library
 * Handles user favorites/bookmarks for books
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class FavoritesManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
    }
    
    /**
     * Add a book to user's favorites
     */
    public function addFavorite(int $userId, int $bookId): array {
        try {
            // Check if already favorited
            if ($this->isFavorite($userId, $bookId)) {
                return ['success' => false, 'errors' => ['Ce livre est déjà dans vos favoris.']];
            }
            
            // Check if book exists
            $book = $this->db->fetchOne("SELECT id FROM books WHERE id = ?", [$bookId]);
            if (!$book) {
                return ['success' => false, 'errors' => ['Livre non trouvé.']];
            }
            
            $sql = "INSERT INTO favorites (user_id, book_id) VALUES (?, ?)";
            $this->db->executeQuery($sql, [$userId, $bookId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error adding favorite: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur lors de l\'ajout aux favoris.']];
        }
    }
    
    /**
     * Remove a book from user's favorites
     */
    public function removeFavorite(int $userId, int $bookId): array {
        try {
            $sql = "DELETE FROM favorites WHERE user_id = ? AND book_id = ?";
            $this->db->executeQuery($sql, [$userId, $bookId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error removing favorite: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur lors de la suppression du favori.']];
        }
    }
    
    /**
     * Toggle favorite status
     */
    public function toggleFavorite(int $userId, int $bookId): array {
        if ($this->isFavorite($userId, $bookId)) {
            $result = $this->removeFavorite($userId, $bookId);
            $result['is_favorite'] = false;
            $result['action'] = 'removed';
        } else {
            $result = $this->addFavorite($userId, $bookId);
            $result['is_favorite'] = true;
            $result['action'] = 'added';
        }
        return $result;
    }
    
    /**
     * Check if a book is in user's favorites
     */
    public function isFavorite(int $userId, int $bookId): bool {
        $sql = "SELECT id FROM favorites WHERE user_id = ? AND book_id = ?";
        $result = $this->db->fetchOne($sql, [$userId, $bookId]);
        return $result !== null;
    }
    
    /**
     * Get all favorites for a user
     */
    public function getUserFavorites(int $userId, int $limit = 50, int $offset = 0): array {
        $sql = "SELECT b.*, c.name as category_name, f.created_at as favorited_at
                FROM favorites f
                JOIN books b ON f.book_id = b.id
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$userId, $limit, $offset]);
    }
    
    /**
     * Get favorites count for a user
     */
    public function getUserFavoritesCount(int $userId): int {
        $sql = "SELECT COUNT(*) as count FROM favorites WHERE user_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Get favorite count for a book (popularity)
     */
    public function getBookFavoritesCount(int $bookId): int {
        $sql = "SELECT COUNT(*) as count FROM favorites WHERE book_id = ?";
        $result = $this->db->fetchOne($sql, [$bookId]);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Get user's favorite book IDs (for quick lookup)
     */
    public function getUserFavoriteIds(int $userId): array {
        $sql = "SELECT book_id FROM favorites WHERE user_id = ?";
        $results = $this->db->fetchAll($sql, [$userId]);
        return array_column($results, 'book_id');
    }
    
    /**
     * Get most favorited books
     */
    public function getMostFavoritedBooks(int $limit = 10): array {
        $sql = "SELECT b.*, c.name as category_name, COUNT(f.id) as favorite_count
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                JOIN favorites f ON b.id = f.book_id
                GROUP BY b.id
                ORDER BY favorite_count DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
}
