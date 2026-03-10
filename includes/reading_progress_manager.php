<?php
/**
 * ReadingProgressManager Class for E-Lib Digital Library
 * Handles reading progress tracking, bookmarks, and history
 * 
 * Requirements: 5.5
 */

require_once __DIR__ . '/../config/database.php';

class ReadingProgressManager {
    private DatabaseManager $db;
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
    }
    
    /**
     * Get reading progress for a user and book
     * 
     * @param int $userId User ID
     * @param int $bookId Book ID
     * @return array|null Progress data or null if not found
     */
    public function getProgress(int $userId, int $bookId): ?array {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM reading_progress WHERE user_id = ? AND book_id = ?",
                [$userId, $bookId]
            );
        } catch (Exception $e) {
            error_log("ReadingProgressManager::getProgress error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save reading progress
     * 
     * @param int $userId User ID
     * @param int $bookId Book ID
     * @param string $position Current position (page number or CFI)
     * @param array $additionalData Additional progress data (bookmarks, etc.)
     * @return bool Success status
     */
    public function saveProgress(int $userId, int $bookId, string $position, array $additionalData = []): bool {
        try {
            $existing = $this->getProgress($userId, $bookId);
            $progressData = json_encode($additionalData);
            
            if ($existing) {
                $this->db->executeQuery(
                    "UPDATE reading_progress SET last_position = ?, progress_data = ?, updated_at = NOW() WHERE id = ?",
                    [$position, $progressData, $existing['id']]
                );
            } else {
                $this->db->executeQuery(
                    "INSERT INTO reading_progress (user_id, book_id, last_position, progress_data) VALUES (?, ?, ?, ?)",
                    [$userId, $bookId, $position, $progressData]
                );
            }
            
            return true;
        } catch (Exception $e) {
            error_log("ReadingProgressManager::saveProgress error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add a bookmark
     * 
     * @param int $userId User ID
     * @param int $bookId Book ID
     * @param string $position Bookmark position
     * @param string $label Bookmark label
     * @return bool Success status
     */
    public function addBookmark(int $userId, int $bookId, string $position, string $label = ''): bool {
        try {
            $progress = $this->getProgress($userId, $bookId);
            $progressData = $progress ? json_decode($progress['progress_data'], true) : [];
            
            if (!isset($progressData['bookmarks'])) {
                $progressData['bookmarks'] = [];
            }
            
            $progressData['bookmarks'][] = [
                'position' => $position,
                'label' => $label,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->saveProgress($userId, $bookId, $progress['last_position'] ?? $position, $progressData);
        } catch (Exception $e) {
            error_log("ReadingProgressManager::addBookmark error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove a bookmark
     * 
     * @param int $userId User ID
     * @param int $bookId Book ID
     * @param int $bookmarkIndex Index of bookmark to remove
     * @return bool Success status
     */
    public function removeBookmark(int $userId, int $bookId, int $bookmarkIndex): bool {
        try {
            $progress = $this->getProgress($userId, $bookId);
            if (!$progress) return false;
            
            $progressData = json_decode($progress['progress_data'], true);
            
            if (isset($progressData['bookmarks'][$bookmarkIndex])) {
                array_splice($progressData['bookmarks'], $bookmarkIndex, 1);
                return $this->saveProgress($userId, $bookId, $progress['last_position'], $progressData);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("ReadingProgressManager::removeBookmark error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get bookmarks for a book
     * 
     * @param int $userId User ID
     * @param int $bookId Book ID
     * @return array List of bookmarks
     */
    public function getBookmarks(int $userId, int $bookId): array {
        try {
            $progress = $this->getProgress($userId, $bookId);
            if (!$progress) return [];
            
            $progressData = json_decode($progress['progress_data'], true);
            return $progressData['bookmarks'] ?? [];
        } catch (Exception $e) {
            error_log("ReadingProgressManager::getBookmarks error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user's reading history (recently read books)
     * 
     * @param int $userId User ID
     * @param int $limit Number of books to return
     * @return array List of recently read books with progress
     */
    public function getReadingHistory(int $userId, int $limit = 10): array {
        try {
            $sql = "SELECT rp.*, b.title, b.author, b.cover_path, b.file_type, c.name as category_name
                    FROM reading_progress rp
                    JOIN books b ON rp.book_id = b.id
                    LEFT JOIN categories c ON b.category_id = c.id
                    WHERE rp.user_id = ?
                    ORDER BY rp.updated_at DESC
                    LIMIT ?";
            return $this->db->fetchAll($sql, [$userId, $limit]);
        } catch (Exception $e) {
            error_log("ReadingProgressManager::getReadingHistory error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get reading statistics for a user
     * 
     * @param int $userId User ID
     * @return array Reading statistics
     */
    public function getUserReadingStats(int $userId): array {
        try {
            $stats = [
                'total_books_read' => 0,
                'total_bookmarks' => 0,
                'last_read_date' => null,
                'favorite_category' => null
            ];
            
            // Total books read
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM reading_progress WHERE user_id = ?",
                [$userId]
            );
            $stats['total_books_read'] = (int)($result['count'] ?? 0);
            
            // Last read date
            $result = $this->db->fetchOne(
                "SELECT MAX(updated_at) as last_read FROM reading_progress WHERE user_id = ?",
                [$userId]
            );
            $stats['last_read_date'] = $result['last_read'] ?? null;
            
            // Favorite category (most read)
            $result = $this->db->fetchOne(
                "SELECT c.name, COUNT(*) as count
                 FROM reading_progress rp
                 JOIN books b ON rp.book_id = b.id
                 JOIN categories c ON b.category_id = c.id
                 WHERE rp.user_id = ?
                 GROUP BY c.id
                 ORDER BY count DESC
                 LIMIT 1",
                [$userId]
            );
            $stats['favorite_category'] = $result['name'] ?? null;
            
            // Count total bookmarks
            $history = $this->getReadingHistory($userId, 100);
            foreach ($history as $item) {
                $progressData = json_decode($item['progress_data'], true);
                $stats['total_bookmarks'] += count($progressData['bookmarks'] ?? []);
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("ReadingProgressManager::getUserReadingStats error: " . $e->getMessage());
            return [
                'total_books_read' => 0,
                'total_bookmarks' => 0,
                'last_read_date' => null,
                'favorite_category' => null
            ];
        }
    }
    
    /**
     * Delete all progress for a book (used when book is deleted)
     * 
     * @param int $bookId Book ID
     * @return bool Success status
     */
    public function deleteBookProgress(int $bookId): bool {
        try {
            $this->db->executeQuery("DELETE FROM reading_progress WHERE book_id = ?", [$bookId]);
            return true;
        } catch (Exception $e) {
            error_log("ReadingProgressManager::deleteBookProgress error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete all progress for a user (used when user is deleted)
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deleteUserProgress(int $userId): bool {
        try {
            $this->db->executeQuery("DELETE FROM reading_progress WHERE user_id = ?", [$userId]);
            return true;
        } catch (Exception $e) {
            error_log("ReadingProgressManager::deleteUserProgress error: " . $e->getMessage());
            return false;
        }
    }
}