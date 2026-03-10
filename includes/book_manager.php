<?php
/**
 * BookManager Class for E-Lib Digital Library
 * Handles CRUD operations for books with metadata validation
 * 
 * Requirements: 3.3, 3.5, 3.6
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/file_manager.php';

class BookManager {
    private DatabaseManager $db;
    private FileManager $fileManager;
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
        $this->fileManager = new FileManager();
    }
    
    /**
     * Create a new book with metadata validation
     * 
     * @param array $bookData Book metadata (title, author, description, category_id, file_path, file_type, cover_path, uploaded_by, file_size)
     * @return array ['success' => bool, 'book_id' => int|null, 'errors' => array]
     */
    public function createBook(array $bookData): array {
        $result = [
            'success' => false,
            'book_id' => null,
            'errors' => []
        ];
        
        // Validate required metadata
        $validationErrors = $this->validateBookMetadata($bookData);
        if (!empty($validationErrors)) {
            $result['errors'] = $validationErrors;
            return $result;
        }
        
        // Validate category exists
        if (!$this->categoryExists($bookData['category_id'])) {
            $result['errors'][] = 'Selected category does not exist.';
            return $result;
        }
        
        try {
            $sql = "INSERT INTO books (title, author, description, file_path, file_type, cover_path, category_id, uploaded_by, file_size) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->executeQuery($sql, [
                $bookData['title'],
                $bookData['author'],
                $bookData['description'],
                $bookData['file_path'],
                $bookData['file_type'],
                $bookData['cover_path'] ?? null,
                $bookData['category_id'],
                $bookData['uploaded_by'],
                $bookData['file_size'] ?? null
            ]);
            
            $result['success'] = true;
            $result['book_id'] = (int)$this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("BookManager::createBook error: " . $e->getMessage());
            $result['errors'][] = 'Failed to create book. Please try again.';
        }
        
        return $result;
    }

    
    /**
     * Update an existing book's metadata
     * 
     * @param int $id Book ID
     * @param array $bookData Updated book metadata
     * @return array ['success' => bool, 'errors' => array]
     */
    public function updateBook(int $id, array $bookData): array {
        $result = [
            'success' => false,
            'errors' => []
        ];
        
        // Check if book exists
        $existingBook = $this->getBookById($id);
        if (!$existingBook) {
            $result['errors'][] = 'Book not found.';
            return $result;
        }
        
        // Validate metadata
        $validationErrors = $this->validateBookMetadata($bookData);
        if (!empty($validationErrors)) {
            $result['errors'] = $validationErrors;
            return $result;
        }
        
        // Validate category exists if provided
        if (isset($bookData['category_id']) && !$this->categoryExists($bookData['category_id'])) {
            $result['errors'][] = 'Selected category does not exist.';
            return $result;
        }
        
        try {
            $sql = "UPDATE books SET title = ?, author = ?, description = ?, category_id = ?, updated_at = NOW() WHERE id = ?";
            
            $this->db->executeQuery($sql, [
                $bookData['title'],
                $bookData['author'],
                $bookData['description'],
                $bookData['category_id'],
                $id
            ]);
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            error_log("BookManager::updateBook error: " . $e->getMessage());
            $result['errors'][] = 'Failed to update book. Please try again.';
        }
        
        return $result;
    }
    
    /**
     * Update book cover image
     * 
     * @param int $id Book ID
     * @param string $coverPath New cover path
     * @return bool Success status
     */
    public function updateBookCover(int $id, string $coverPath): bool {
        try {
            // Get existing book to delete old cover
            $existingBook = $this->getBookById($id);
            if (!$existingBook) {
                return false;
            }
            
            // Delete old cover if exists
            if (!empty($existingBook['cover_path'])) {
                $this->fileManager->deleteCover($existingBook['cover_path']);
            }
            
            $sql = "UPDATE books SET cover_path = ?, updated_at = NOW() WHERE id = ?";
            $this->db->executeQuery($sql, [$coverPath, $id]);
            
            return true;
        } catch (Exception $e) {
            error_log("BookManager::updateBookCover error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a book and its associated files
     * 
     * @param int $id Book ID
     * @return array ['success' => bool, 'errors' => array]
     */
    public function deleteBook(int $id): array {
        $result = [
            'success' => false,
            'errors' => []
        ];
        
        // Get book data first
        $book = $this->getBookById($id);
        if (!$book) {
            $result['errors'][] = 'Book not found.';
            return $result;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Delete reading progress records
            $this->db->executeQuery("DELETE FROM reading_progress WHERE book_id = ?", [$id]);
            
            // Delete book record
            $this->db->executeQuery("DELETE FROM books WHERE id = ?", [$id]);
            
            $this->db->commit();
            
            // Delete associated files after successful database deletion
            if (!empty($book['file_path'])) {
                $this->fileManager->deleteBook($book['file_path']);
            }
            if (!empty($book['cover_path'])) {
                $this->fileManager->deleteCover($book['cover_path']);
            }
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("BookManager::deleteBook error: " . $e->getMessage());
            $result['errors'][] = 'Failed to delete book. Please try again.';
        }
        
        return $result;
    }

    
    /**
     * Get a book by ID
     * 
     * @param int $id Book ID
     * @return array|null Book data or null if not found
     */
    public function getBookById(int $id): ?array {
        try {
            $sql = "SELECT b.*, c.name as category_name, u.username as uploaded_by_name 
                    FROM books b 
                    LEFT JOIN categories c ON b.category_id = c.id 
                    LEFT JOIN users u ON b.uploaded_by = u.id 
                    WHERE b.id = ?";
            return $this->db->fetchOne($sql, [$id]);
        } catch (Exception $e) {
            error_log("BookManager::getBookById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all books with optional pagination
     * 
     * @param int $limit Number of books to return
     * @param int $offset Offset for pagination
     * @return array List of books
     */
    public function getAllBooks(int $limit = 50, int $offset = 0): array {
        try {
            $sql = "SELECT b.*, c.name as category_name, u.username as uploaded_by_name 
                    FROM books b 
                    LEFT JOIN categories c ON b.category_id = c.id 
                    LEFT JOIN users u ON b.uploaded_by = u.id 
                    ORDER BY b.created_at DESC 
                    LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        } catch (Exception $e) {
            error_log("BookManager::getAllBooks error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search books by title, author, or category
     * 
     * @param string $query Search query
     * @param int|null $categoryId Optional category filter
     * @return array Matching books
     */
    public function searchBooks(string $query, ?int $categoryId = null): array {
        try {
            $params = [];
            $searchTerm = '%' . $query . '%';
            
            $sql = "SELECT b.*, c.name as category_name, u.username as uploaded_by_name 
                    FROM books b 
                    LEFT JOIN categories c ON b.category_id = c.id 
                    LEFT JOIN users u ON b.uploaded_by = u.id 
                    WHERE (b.title LIKE ? OR b.author LIKE ? OR c.name LIKE ?)";
            
            $params = [$searchTerm, $searchTerm, $searchTerm];
            
            if ($categoryId !== null) {
                $sql .= " AND b.category_id = ?";
                $params[] = $categoryId;
            }
            
            $sql .= " ORDER BY b.title ASC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log("BookManager::searchBooks error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get books by category
     * 
     * @param int $categoryId Category ID
     * @return array Books in the category
     */
    public function getBooksByCategory(int $categoryId): array {
        try {
            $sql = "SELECT b.*, c.name as category_name, u.username as uploaded_by_name 
                    FROM books b 
                    LEFT JOIN categories c ON b.category_id = c.id 
                    LEFT JOIN users u ON b.uploaded_by = u.id 
                    WHERE b.category_id = ? 
                    ORDER BY b.title ASC";
            return $this->db->fetchAll($sql, [$categoryId]);
        } catch (Exception $e) {
            error_log("BookManager::getBooksByCategory error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total book count
     * 
     * @return int Total number of books
     */
    public function getTotalBookCount(): int {
        try {
            $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM books");
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("BookManager::getTotalBookCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get book count by category
     * 
     * @param int $categoryId Category ID
     * @return int Number of books in category
     */
    public function getBookCountByCategory(int $categoryId): int {
        try {
            $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM books WHERE category_id = ?", [$categoryId]);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("BookManager::getBookCountByCategory error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get recent books
     * 
     * @param int $limit Number of recent books to return
     * @return array Recent books
     */
    public function getRecentBooks(int $limit = 10): array {
        try {
            $sql = "SELECT b.*, c.name as category_name, u.username as uploaded_by_name 
                    FROM books b 
                    LEFT JOIN categories c ON b.category_id = c.id 
                    LEFT JOIN users u ON b.uploaded_by = u.id 
                    ORDER BY b.created_at DESC 
                    LIMIT ?";
            return $this->db->fetchAll($sql, [$limit]);
        } catch (Exception $e) {
            error_log("BookManager::getRecentBooks error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate book metadata
     * 
     * @param array $bookData Book data to validate
     * @return array Validation errors (empty if valid)
     */
    private function validateBookMetadata(array $bookData): array {
        $errors = [];
        
        // Title validation
        if (empty($bookData['title']) || trim($bookData['title']) === '') {
            $errors[] = 'Title is required.';
        } elseif (strlen($bookData['title']) > 255) {
            $errors[] = 'Title must be 255 characters or less.';
        }
        
        // Author validation
        if (empty($bookData['author']) || trim($bookData['author']) === '') {
            $errors[] = 'Author is required.';
        } elseif (strlen($bookData['author']) > 255) {
            $errors[] = 'Author must be 255 characters or less.';
        }
        
        // Category validation
        if (empty($bookData['category_id'])) {
            $errors[] = 'Category is required.';
        }
        
        // Description validation
        if (empty($bookData['description']) || trim($bookData['description']) === '') {
            $errors[] = 'Description is required.';
        }
        
        return $errors;
    }
    
    /**
     * Check if a category exists
     * 
     * @param int $categoryId Category ID to check
     * @return bool True if category exists
     */
    private function categoryExists(int $categoryId): bool {
        try {
            $result = $this->db->fetchOne("SELECT id FROM categories WHERE id = ?", [$categoryId]);
            return $result !== null;
        } catch (Exception $e) {
            error_log("BookManager::categoryExists error: " . $e->getMessage());
            return false;
        }
    }
}
