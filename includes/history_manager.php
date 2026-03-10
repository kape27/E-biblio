<?php
/**
 * HistoryManager Class for E-Lib Digital Library
 * Handles reading history display, management, and statistics
 * 
 * Requirements: 1.1, 1.4, 1.5, 7.1
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/reading_progress_manager.php';
require_once __DIR__ . '/book_manager.php';

class HistoryManager {
    private DatabaseManager $db;
    private ReadingProgressManager $progressManager;
    private BookManager $bookManager;
    
    public function __construct() {
        $this->db = DatabaseManager::getInstance();
        $this->progressManager = new ReadingProgressManager();
        $this->bookManager = new BookManager();
    }
    
    /**
     * Get user's reading history with pagination support
     * 
     * @param int $userId User ID
     * @param array $options Options array with keys: limit, offset, filters
     * @return array History entries with pagination info
     */
    public function getUserHistory(int $userId, array $options = []): array {
        try {
            // Default options
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            $filters = $options['filters'] ?? [];
            
            // Build base query
            $sql = "SELECT rp.*, b.title, b.author, b.cover_path, b.file_type, b.file_size,
                           c.name as category_name, rp.updated_at as last_read_date
                    FROM reading_progress rp
                    JOIN books b ON rp.book_id = b.id
                    LEFT JOIN categories c ON b.category_id = c.id
                    WHERE rp.user_id = ? 
                    AND (rp.progress_data IS NULL OR rp.progress_data NOT LIKE '%\"hidden_from_history\":true%')";
            
            $params = [$userId];
            
            // Apply filters
            if (!empty($filters['search'])) {
                $sql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['status'])) {
                switch ($filters['status']) {
                    case 'completed':
                        $sql .= " AND rp.progress_data LIKE '%\"completion_percentage\":100%'";
                        break;
                    case 'in_progress':
                        $sql .= " AND rp.progress_data LIKE '%\"completion_percentage\":%' 
                                 AND rp.progress_data NOT LIKE '%\"completion_percentage\":0%'
                                 AND rp.progress_data NOT LIKE '%\"completion_percentage\":100%'";
                        break;
                    case 'not_started':
                        $sql .= " AND (rp.progress_data IS NULL 
                                 OR rp.progress_data NOT LIKE '%\"completion_percentage\":%'
                                 OR rp.progress_data LIKE '%\"completion_percentage\":0%')";
                        break;
                }
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND rp.updated_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND rp.updated_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Add ordering and pagination
            $sql .= " ORDER BY rp.updated_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $entries = $this->db->fetchAll($sql, $params);
            
            // Format entries
            $formattedEntries = [];
            foreach ($entries as $entry) {
                $formattedEntries[] = $this->formatHistoryEntry($entry);
            }
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM reading_progress rp
                        JOIN books b ON rp.book_id = b.id
                        LEFT JOIN categories c ON b.category_id = c.id
                        WHERE rp.user_id = ?
                        AND (rp.progress_data IS NULL OR rp.progress_data NOT LIKE '%\"hidden_from_history\":true%')";
            
            $countParams = [$userId];
            
            // Apply same filters for count
            if (!empty($filters['search'])) {
                $countSql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
            }
            
            if (!empty($filters['status'])) {
                switch ($filters['status']) {
                    case 'completed':
                        $countSql .= " AND rp.progress_data LIKE '%\"completion_percentage\":100%'";
                        break;
                    case 'in_progress':
                        $countSql .= " AND rp.progress_data LIKE '%\"completion_percentage\":%' 
                                      AND rp.progress_data NOT LIKE '%\"completion_percentage\":0%'
                                      AND rp.progress_data NOT LIKE '%\"completion_percentage\":100%'";
                        break;
                    case 'not_started':
                        $countSql .= " AND (rp.progress_data IS NULL 
                                      OR rp.progress_data NOT LIKE '%\"completion_percentage\":%'
                                      OR rp.progress_data LIKE '%\"completion_percentage\":0%')";
                        break;
                }
            }
            
            if (!empty($filters['date_from'])) {
                $countSql .= " AND rp.updated_at >= ?";
                $countParams[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $countSql .= " AND rp.updated_at <= ?";
                $countParams[] = $filters['date_to'];
            }
            
            $totalResult = $this->db->fetchOne($countSql, $countParams);
            $total = (int)($totalResult['total'] ?? 0);
            
            return [
                'entries' => $formattedEntries,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total,
                    'current_page' => floor($offset / $limit) + 1,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("HistoryManager::getUserHistory error: " . $e->getMessage());
            return [
                'entries' => [],
                'pagination' => [
                    'total' => 0,
                    'limit' => $limit,
                    'offset' => 0,
                    'has_more' => false,
                    'current_page' => 1,
                    'total_pages' => 0
                ]
            ];
        }
    }
    
    /**
     * Format a history entry with all required display information
     * Requirements: 1.2, 3.1, 3.2, 3.3
     * 
     * @param array $entry Raw database entry
     * @return array Formatted entry with calculated fields
     */
    public function formatHistoryEntry(array $entry): array {
        $progressData = json_decode($entry['progress_data'] ?? '{}', true);
        $completionPercentage = $this->calculateProgressPercentage($progressData, $entry);
        
        // Determine reading status with enhanced logic
        $readingStatus = $this->determineReadingStatus($completionPercentage, $progressData);
        
        // Calculate estimated time remaining with improved accuracy
        $estimatedTimeRemaining = $this->calculateEstimatedTime($completionPercentage, $progressData);
        
        // Format last read date with multiple formats
        $lastReadDate = new DateTime($entry['last_read_date']);
        
        // Get progress display information
        $progressDisplay = $this->getProgressDisplayInfo($completionPercentage, $progressData);
        
        return [
            // Core book information (Requirement 1.2)
            'book_id' => (int)$entry['book_id'],
            'title' => $entry['title'],
            'author' => $entry['author'],
            'cover_image' => $this->formatCoverImagePath($entry['cover_path']),
            'category_name' => $entry['category_name'] ?? 'Non classé',
            'file_type' => strtoupper($entry['file_type'] ?? 'UNKNOWN'),
            
            // Progress information (Requirements 3.1, 3.2, 3.3)
            'progress_percentage' => $completionPercentage,
            'progress_percentage_display' => number_format($completionPercentage, 1) . '%',
            'reading_status' => $readingStatus,
            'reading_status_label' => $this->getReadingStatusLabel($readingStatus),
            'progress_bar_width' => min(100, max(0, $completionPercentage)),
            'is_completed' => $completionPercentage >= 100,
            'is_in_progress' => $completionPercentage > 0 && $completionPercentage < 100,
            'is_not_started' => $completionPercentage <= 0,
            
            // Date and time information
            'last_read' => $lastReadDate,
            'last_read_formatted' => $lastReadDate->format('d/m/Y H:i'),
            'last_read_date_only' => $lastReadDate->format('d/m/Y'),
            'last_read_time_only' => $lastReadDate->format('H:i'),
            'last_read_relative' => $this->getRelativeTime($lastReadDate),
            'last_read_iso' => $lastReadDate->format('c'),
            
            // Position and navigation
            'last_position' => $entry['last_position'],
            'current_page' => $progressDisplay['current_page'],
            'total_pages' => $progressDisplay['total_pages'],
            'page_display' => $progressDisplay['page_display'],
            
            // Time estimation
            'estimated_time_remaining' => $estimatedTimeRemaining,
            'estimated_time_remaining_minutes' => $this->getEstimatedMinutes($completionPercentage, $progressData),
            
            // Additional metadata
            'bookmarks_count' => count($progressData['bookmarks'] ?? []),
            'has_bookmarks' => !empty($progressData['bookmarks']),
            'file_size_formatted' => $this->formatFileSize($entry['file_size'] ?? 0),
            
            // Display helpers
            'css_status_class' => 'status-' . str_replace('_', '-', $readingStatus),
            'progress_color_class' => $this->getProgressColorClass($completionPercentage),
            'can_continue_reading' => !empty($entry['last_position'])
        ];
    }
    
    /**
     * Calculate accurate progress percentage from various data sources
     * 
     * @param array $progressData Progress data from database
     * @param array $entry Full database entry
     * @return float Progress percentage (0-100)
     */
    private function calculateProgressPercentage(array $progressData, array $entry): float {
        // Try to get from explicit completion percentage first
        if (isset($progressData['completion_percentage'])) {
            return (float)$progressData['completion_percentage'];
        }
        
        // Calculate from page numbers if available
        $totalPages = (int)($progressData['total_pages'] ?? 0);
        $currentPage = (int)($progressData['current_page'] ?? 0);
        
        if ($totalPages > 0 && $currentPage > 0) {
            return min(100, ($currentPage / $totalPages) * 100);
        }
        
        // For EPUB files, try to calculate from CFI position
        if (isset($progressData['cfi_position']) && isset($progressData['total_cfi_positions'])) {
            $cfiProgress = (float)($progressData['cfi_position'] / $progressData['total_cfi_positions']);
            return min(100, $cfiProgress * 100);
        }
        
        // Default to 0 if no progress data available
        return 0.0;
    }
    
    /**
     * Determine reading status with enhanced logic
     * 
     * @param float $completionPercentage Completion percentage
     * @param array $progressData Progress data
     * @return string Reading status
     */
    private function determineReadingStatus(float $completionPercentage, array $progressData): string {
        if ($completionPercentage >= 100) {
            return 'completed';
        } elseif ($completionPercentage > 0) {
            return 'in_progress';
        } elseif (isset($progressData['started_reading']) && $progressData['started_reading']) {
            return 'in_progress'; // Started but no measurable progress yet
        } else {
            return 'not_started';
        }
    }
    
    /**
     * Get human-readable reading status label
     * 
     * @param string $status Reading status
     * @return string Localized status label
     */
    private function getReadingStatusLabel(string $status): string {
        switch ($status) {
            case 'completed':
                return 'Terminé';
            case 'in_progress':
                return 'En cours';
            case 'not_started':
            default:
                return 'Non commencé';
        }
    }
    
    /**
     * Get progress display information for pages
     * 
     * @param float $completionPercentage Completion percentage
     * @param array $progressData Progress data
     * @return array Progress display info
     */
    private function getProgressDisplayInfo(float $completionPercentage, array $progressData): array {
        $totalPages = (int)($progressData['total_pages'] ?? 0);
        $currentPage = (int)($progressData['current_page'] ?? 0);
        
        // If we don't have page info, estimate from percentage
        if ($totalPages === 0 && $completionPercentage > 0) {
            $totalPages = 100; // Fallback estimate
            $currentPage = (int)($completionPercentage);
        }
        
        $pageDisplay = '';
        if ($totalPages > 0) {
            if ($currentPage > 0) {
                $pageDisplay = "Page {$currentPage} sur {$totalPages}";
            } else {
                $pageDisplay = "{$totalPages} pages";
            }
        }
        
        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'page_display' => $pageDisplay
        ];
    }
    
    /**
     * Format cover image path with fallback
     * 
     * @param string|null $coverPath Cover image path
     * @return string Formatted cover path or default
     */
    private function formatCoverImagePath(?string $coverPath): string {
        if (empty($coverPath)) {
            return 'assets/images/default-book-cover.jpg';
        }
        
        // Ensure path starts with uploads/ if it's a relative path
        if (!str_starts_with($coverPath, 'http') && !str_starts_with($coverPath, '/') && !str_starts_with($coverPath, 'uploads/')) {
            return 'uploads/covers/' . $coverPath;
        }
        
        return $coverPath;
    }
    
    /**
     * Get CSS class for progress color coding
     * 
     * @param float $percentage Progress percentage
     * @return string CSS class name
     */
    private function getProgressColorClass(float $percentage): string {
        if ($percentage >= 100) {
            return 'progress-completed';
        } elseif ($percentage >= 75) {
            return 'progress-high';
        } elseif ($percentage >= 25) {
            return 'progress-medium';
        } elseif ($percentage > 0) {
            return 'progress-low';
        } else {
            return 'progress-none';
        }
    }
    
    /**
     * Format file size in human readable format
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize(int $bytes): string {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
    
    /**
     * Get estimated reading time in minutes
     * 
     * @param float $completionPercentage Current completion percentage
     * @param array $progressData Progress data
     * @return int Estimated minutes remaining
     */
    private function getEstimatedMinutes(float $completionPercentage, array $progressData): int {
        if ($completionPercentage >= 100) {
            return 0;
        }
        
        if ($completionPercentage <= 0) {
            // Estimate total reading time for unstarted books
            $totalPages = (int)($progressData['total_pages'] ?? 100);
            return $totalPages * 2; // 2 minutes per page estimate
        }
        
        // Calculate remaining time based on current progress
        $totalPages = (int)($progressData['total_pages'] ?? 100);
        $currentPage = (int)($progressData['current_page'] ?? 0);
        $remainingPages = $totalPages - $currentPage;
        
        return max(0, $remainingPages * 2);
    }
    
    /**
     * Remove a specific history entry for a user
     * 
     * @param int $userId User ID
     * @param int $bookId Book ID
     * @return bool Success status
     */
    public function removeHistoryEntry(int $userId, int $bookId): bool {
        try {
            // Verify the entry belongs to the user
            $entry = $this->db->fetchOne(
                "SELECT id, progress_data FROM reading_progress WHERE user_id = ? AND book_id = ?",
                [$userId, $bookId]
            );
            
            if (!$entry) {
                return false;
            }
            
            // Delete the history entry (this removes it from history but preserves reading progress)
            // We'll implement this by marking it as hidden rather than deleting
            $existingData = json_decode($entry['progress_data'] ?? '{}', true);
            $existingData['hidden_from_history'] = true;
            $updatedData = json_encode($existingData);
            
            $this->db->executeQuery(
                "UPDATE reading_progress SET progress_data = ? WHERE user_id = ? AND book_id = ?",
                [$updatedData, $userId, $bookId]
            );
            
            // Log the action
            $this->logHistoryAction($userId, 'remove_entry', "Removed book ID $bookId from history");
            
            return true;
        } catch (Exception $e) {
            error_log("HistoryManager::removeHistoryEntry error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all history for a user
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function clearUserHistory(int $userId): bool {
        try {
            // Get all entries for the user first
            $entries = $this->db->fetchAll(
                "SELECT id, progress_data FROM reading_progress WHERE user_id = ?",
                [$userId]
            );
            
            // Mark all entries as hidden from history while preserving reading progress
            foreach ($entries as $entry) {
                $existingData = json_decode($entry['progress_data'] ?? '{}', true);
                $existingData['hidden_from_history'] = true;
                $updatedData = json_encode($existingData);
                
                $this->db->executeQuery(
                    "UPDATE reading_progress SET progress_data = ? WHERE id = ?",
                    [$updatedData, $entry['id']]
                );
            }
            
            // Log the action
            $this->logHistoryAction($userId, 'clear_all', "Cleared all reading history");
            
            return true;
        } catch (Exception $e) {
            error_log("HistoryManager::clearUserHistory error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate estimated reading time remaining with improved accuracy
     * 
     * @param float $completionPercentage Current completion percentage
     * @param array $progressData Progress data with timing information
     * @return string Formatted time estimate
     */
    private function calculateEstimatedTime(float $completionPercentage, array $progressData): string {
        if ($completionPercentage >= 100) {
            return 'Terminé';
        }
        
        if ($completionPercentage <= 0) {
            return 'Non commencé';
        }
        
        $estimatedMinutes = $this->getEstimatedMinutes($completionPercentage, $progressData);
        
        if ($estimatedMinutes <= 0) {
            return 'Presque terminé';
        }
        
        return $this->formatTimeEstimate($estimatedMinutes);
    }
    
    /**
     * Format time estimate into human readable string
     * 
     * @param int $minutes Time in minutes
     * @return string Formatted time string
     */
    private function formatTimeEstimate(int $minutes): string {
        if ($minutes < 60) {
            return $minutes . ' min';
        } elseif ($minutes < 1440) { // Less than 24 hours
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return $hours . 'h' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . 'min' : '');
        } else {
            $days = floor($minutes / 1440);
            return $days . ' jour' . ($days > 1 ? 's' : '');
        }
    }
    
    /**
     * Get relative time string (e.g., "il y a 2 heures")
     * 
     * @param DateTime $date Date to compare
     * @return string Relative time string
     */
    private function getRelativeTime(DateTime $date): string {
        $now = new DateTime();
        $diff = $now->diff($date);
        
        if ($diff->days > 0) {
            if ($diff->days == 1) {
                return 'hier';
            } elseif ($diff->days < 7) {
                return 'il y a ' . $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
            } elseif ($diff->days < 30) {
                $weeks = floor($diff->days / 7);
                return 'il y a ' . $weeks . ' semaine' . ($weeks > 1 ? 's' : '');
            } else {
                $months = floor($diff->days / 30);
                return 'il y a ' . $months . ' mois';
            }
        } elseif ($diff->h > 0) {
            return 'il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return 'il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        } else {
            return 'à l\'instant';
        }
    }
    
    /**
     * Log history management actions for audit purposes
     * 
     * @param int $userId User ID
     * @param string $action Action type
     * @param string $details Action details
     * @return void
     */
    private function logHistoryAction(int $userId, string $action, string $details): void {
        try {
            $this->db->executeQuery(
                "INSERT INTO logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
                [
                    $userId,
                    'history_' . $action,
                    $details,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            );
        } catch (Exception $e) {
            error_log("HistoryManager::logHistoryAction error: " . $e->getMessage());
        }
    }
}