<?php
/**
 * CategoryManager Class for E-Lib Digital Library
 * Handles CRUD operations for categories with uniqueness validation
 * 
 * Requirements: 8.1, 8.2, 8.4
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class CategoryManager {
    private DatabaseManager $db;
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
    }
    
    /**
     * Create a new category with uniqueness validation
     * 
     * @param string $name Category name
     * @param string $description Category description (optional)
     * @return array ['success' => bool, 'category_id' => int|null, 'errors' => array]
     */
    public function createCategory(string $name, string $description = ''): array {
        $result = [
            'success' => false,
            'category_id' => null,
            'errors' => []
        ];
        
        // Validate name
        $name = trim($name);
        if (empty($name)) {
            $result['errors'][] = 'Le nom de la catégorie est requis.';
            return $result;
        }
        
        if (strlen($name) > 100) {
            $result['errors'][] = 'Le nom de la catégorie ne doit pas dépasser 100 caractères.';
            return $result;
        }
        
        // Check uniqueness
        if ($this->categoryNameExists($name)) {
            $result['errors'][] = 'Une catégorie avec ce nom existe déjà.';
            return $result;
        }
        
        try {
            $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
            $this->db->executeQuery($sql, [$name, trim($description)]);
            
            $result['success'] = true;
            $result['category_id'] = (int)$this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("CategoryManager::createCategory error: " . $e->getMessage());
            $result['errors'][] = 'Échec de la création de la catégorie. Veuillez réessayer.';
        }
        
        return $result;
    }
    
    /**
     * Update an existing category
     * 
     * @param int $id Category ID
     * @param string $name New category name
     * @param string $description New category description
     * @return array ['success' => bool, 'errors' => array]
     */
    public function updateCategory(int $id, string $name, string $description = ''): array {
        $result = [
            'success' => false,
            'errors' => []
        ];
        
        // Check if category exists
        $existingCategory = $this->getCategoryById($id);
        if (!$existingCategory) {
            $result['errors'][] = 'Catégorie non trouvée.';
            return $result;
        }
        
        // Validate name
        $name = trim($name);
        if (empty($name)) {
            $result['errors'][] = 'Le nom de la catégorie est requis.';
            return $result;
        }
        
        if (strlen($name) > 100) {
            $result['errors'][] = 'Le nom de la catégorie ne doit pas dépasser 100 caractères.';
            return $result;
        }
        
        // Check uniqueness (excluding current category)
        if ($this->categoryNameExists($name, $id)) {
            $result['errors'][] = 'Une catégorie avec ce nom existe déjà.';
            return $result;
        }
        
        try {
            $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
            $this->db->executeQuery($sql, [$name, trim($description), $id]);
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            error_log("CategoryManager::updateCategory error: " . $e->getMessage());
            $result['errors'][] = 'Échec de la mise à jour de la catégorie. Veuillez réessayer.';
        }
        
        return $result;
    }
    
    /**
     * Delete a category and handle associated books
     * Books will have their category_id set to NULL (handled by ON DELETE SET NULL)
     * 
     * @param int $id Category ID
     * @return array ['success' => bool, 'errors' => array, 'affected_books' => int]
     */
    public function deleteCategory(int $id): array {
        $result = [
            'success' => false,
            'errors' => [],
            'affected_books' => 0
        ];
        
        // Check if category exists
        $category = $this->getCategoryById($id);
        if (!$category) {
            $result['errors'][] = 'Catégorie non trouvée.';
            return $result;
        }
        
        try {
            // Count affected books before deletion
            $bookCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM books WHERE category_id = ?", 
                [$id]
            );
            $result['affected_books'] = (int)($bookCount['count'] ?? 0);
            
            // Delete category (books will have category_id set to NULL via foreign key)
            $this->db->executeQuery("DELETE FROM categories WHERE id = ?", [$id]);
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            error_log("CategoryManager::deleteCategory error: " . $e->getMessage());
            $result['errors'][] = 'Échec de la suppression de la catégorie. Veuillez réessayer.';
        }
        
        return $result;
    }
    
    /**
     * Get a category by ID
     * 
     * @param int $id Category ID
     * @return array|null Category data or null if not found
     */
    public function getCategoryById(int $id): ?array {
        try {
            return $this->db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
        } catch (Exception $e) {
            error_log("CategoryManager::getCategoryById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all categories with book counts
     * 
     * @return array List of categories with book counts
     */
    public function getAllCategoriesWithCounts(): array {
        try {
            $sql = "SELECT c.*, COUNT(b.id) as book_count 
                    FROM categories c 
                    LEFT JOIN books b ON c.id = b.category_id 
                    GROUP BY c.id 
                    ORDER BY c.name ASC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("CategoryManager::getAllCategoriesWithCounts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all categories (simple list)
     * 
     * @return array List of categories
     */
    public function getAllCategories(): array {
        try {
            return $this->db->fetchAll("SELECT * FROM categories ORDER BY name ASC");
        } catch (Exception $e) {
            error_log("CategoryManager::getAllCategories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total category count
     * 
     * @return int Total number of categories
     */
    public function getTotalCategoryCount(): int {
        try {
            $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM categories");
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("CategoryManager::getTotalCategoryCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if a category name already exists
     * 
     * @param string $name Category name to check
     * @param int|null $excludeId Category ID to exclude from check (for updates)
     * @return bool True if name exists
     */
    public function categoryNameExists(string $name, ?int $excludeId = null): bool {
        try {
            $sql = "SELECT id FROM categories WHERE LOWER(name) = LOWER(?)";
            $params = [$name];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return $result !== null;
        } catch (Exception $e) {
            error_log("CategoryManager::categoryNameExists error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get category statistics for dashboard
     * 
     * @return array Statistics including total categories, books per category, etc.
     */
    public function getCategoryStatistics(): array {
        try {
            $stats = [
                'total_categories' => $this->getTotalCategoryCount(),
                'categories_with_books' => 0,
                'empty_categories' => 0,
                'top_categories' => []
            ];
            
            // Get categories with book counts
            $categoriesWithCounts = $this->getAllCategoriesWithCounts();
            
            foreach ($categoriesWithCounts as $category) {
                if ($category['book_count'] > 0) {
                    $stats['categories_with_books']++;
                } else {
                    $stats['empty_categories']++;
                }
            }
            
            // Get top 5 categories by book count
            usort($categoriesWithCounts, fn($a, $b) => $b['book_count'] - $a['book_count']);
            $stats['top_categories'] = array_slice($categoriesWithCounts, 0, 5);
            
            return $stats;
        } catch (Exception $e) {
            error_log("CategoryManager::getCategoryStatistics error: " . $e->getMessage());
            return [
                'total_categories' => 0,
                'categories_with_books' => 0,
                'empty_categories' => 0,
                'top_categories' => []
            ];
        }
    }
}
