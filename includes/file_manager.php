<?php
/**
 * FileManager Class for E-Lib Digital Library
 * Handles secure file uploads for books and cover images
 * 
 * Requirements: 3.1, 3.2, 3.4, 6.3
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';

class FileManager {
    // Allowed file types
    private const ALLOWED_BOOK_EXTENSIONS = ['pdf', 'epub'];
    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // MIME types mapping
    private const BOOK_MIME_TYPES = [
        'pdf' => 'application/pdf',
        'epub' => 'application/epub+zip'
    ];
    
    private const IMAGE_MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    // File size limits
    private const MAX_BOOK_SIZE = 50 * 1024 * 1024; // 50MB
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
    
    // Cover image dimensions
    private const COVER_MAX_WIDTH = 400;
    private const COVER_MAX_HEIGHT = 600;
    
    // Upload directories (relative to project root)
    private string $booksDir;
    private string $coversDir;
    
    public function __construct(?string $booksDir = null, ?string $coversDir = null) {
        $baseDir = dirname(__DIR__);
        $this->booksDir = $booksDir ?? $baseDir . '/uploads/books';
        $this->coversDir = $coversDir ?? $baseDir . '/uploads/covers';
        
        $this->ensureDirectoriesExist();
    }
    
    /**
     * Ensure upload directories exist with proper permissions
     */
    private function ensureDirectoriesExist(): void {
        if (!is_dir($this->booksDir)) {
            mkdir($this->booksDir, 0755, true);
        }
        if (!is_dir($this->coversDir)) {
            mkdir($this->coversDir, 0755, true);
        }
    }
    
    /**
     * Upload a book file (PDF or EPUB)
     * 
     * @param array $file The $_FILES array element
     * @return array ['success' => bool, 'filename' => string|null, 'file_type' => string|null, 'file_size' => int|null, 'errors' => array]
     */
    public function uploadBook(array $file): array {
        $result = [
            'success' => false,
            'filename' => null,
            'file_type' => null,
            'file_size' => null,
            'errors' => []
        ];
        
        // Validate the file
        $validationErrors = $this->validateBookFile($file);
        if (!empty($validationErrors)) {
            $result['errors'] = $validationErrors;
            return $result;
        }
        
        // Generate secure filename
        $extension = $this->getFileExtension($file['name']);
        $secureFilename = $this->generateSecureFilename($extension);
        $targetPath = $this->booksDir . '/' . $secureFilename;
        
        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $result['success'] = true;
            $result['filename'] = $secureFilename;
            $result['file_type'] = $extension;
            $result['file_size'] = $file['size'];
        } else {
            $result['errors'][] = 'Failed to move uploaded file to destination.';
        }
        
        return $result;
    }
    
    /**
     * Upload a cover image with automatic resizing
     * Falls back to simple copy if GD extension is not available
     * 
     * @param array $file The $_FILES array element
     * @return array ['success' => bool, 'filename' => string|null, 'errors' => array]
     */
    public function uploadCover(array $file): array {
        $result = [
            'success' => false,
            'filename' => null,
            'errors' => []
        ];
        
        // Validate the image file
        $validationErrors = $this->validateImageFile($file);
        if (!empty($validationErrors)) {
            $result['errors'] = $validationErrors;
            return $result;
        }
        
        // Determine file extension based on GD availability
        // If GD is available, convert to jpg; otherwise keep original extension
        $gdAvailable = extension_loaded('gd') && function_exists('imagecreatetruecolor');
        $extension = $gdAvailable ? 'jpg' : $this->getFileExtension($file['name']);
        
        // Generate secure filename
        $secureFilename = $this->generateSecureFilename($extension);
        $targetPath = $this->coversDir . '/' . $secureFilename;
        
        // Resize and save the image (or copy if GD not available)
        $resizeResult = $this->resizeAndSaveImage($file['tmp_name'], $targetPath);
        if ($resizeResult['success']) {
            $result['success'] = true;
            $result['filename'] = $secureFilename;
        } else {
            $result['errors'][] = $resizeResult['error'] ?? 'Failed to process cover image.';
        }
        
        return $result;
    }
    
    /**
     * Validate a book file (PDF/EPUB)
     * 
     * @param array $file The $_FILES array element
     * @return array Array of error messages (empty if valid)
     */
    public function validateBookFile(array $file): array {
        $errors = [];
        
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return $errors;
        }
        
        // Check if file exists
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No valid file was uploaded.';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > self::MAX_BOOK_SIZE) {
            $errors[] = 'File size exceeds maximum allowed size of ' . format_file_size(self::MAX_BOOK_SIZE) . '.';
        }
        
        // Check file extension
        $extension = $this->getFileExtension($file['name']);
        if (!$this->isAllowedBookExtension($extension)) {
            $errors[] = 'Invalid file type. Only PDF and EPUB files are allowed.';
            return $errors;
        }
        
        // Verify MIME type
        if (!$this->verifyBookMimeType($file['tmp_name'], $extension)) {
            $errors[] = 'File content does not match the expected format.';
        }
        
        return $errors;
    }
    
    /**
     * Validate an image file
     * 
     * @param array $file The $_FILES array element
     * @return array Array of error messages (empty if valid)
     */
    public function validateImageFile(array $file): array {
        $errors = [];
        
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return $errors;
        }
        
        // Check if file exists
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No valid image was uploaded.';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > self::MAX_IMAGE_SIZE) {
            $errors[] = 'Image size exceeds maximum allowed size of ' . format_file_size(self::MAX_IMAGE_SIZE) . '.';
        }
        
        // Check file extension
        $extension = $this->getFileExtension($file['name']);
        if (!$this->isAllowedImageExtension($extension)) {
            $errors[] = 'Invalid image type. Only JPG, PNG, GIF, and WebP images are allowed.';
            return $errors;
        }
        
        // Verify it's actually an image using getimagesize
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'File is not a valid image.';
        }
        
        return $errors;
    }
    
    /**
     * Validate file type (extension and MIME)
     * 
     * @param array $file The $_FILES array element
     * @param array $allowedTypes Array of allowed extensions
     * @return bool True if valid
     */
    public function validateFileType(array $file, array $allowedTypes): bool {
        if (!isset($file['name']) || !isset($file['tmp_name'])) {
            return false;
        }
        
        $extension = $this->getFileExtension($file['name']);
        return in_array($extension, $allowedTypes);
    }
    
    /**
     * Generate a secure random filename
     * 
     * @param string $extension File extension
     * @return string Secure filename
     */
    public function generateSecureFilename(string $extension): string {
        $randomBytes = bin2hex(random_bytes(16));
        $timestamp = time();
        return $randomBytes . '_' . $timestamp . '.' . strtolower($extension);
    }
    
    /**
     * Get file extension from filename
     * 
     * @param string $filename Original filename
     * @return string Lowercase extension
     */
    private function getFileExtension(string $filename): string {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Check if extension is allowed for books
     */
    private function isAllowedBookExtension(string $extension): bool {
        return in_array($extension, self::ALLOWED_BOOK_EXTENSIONS);
    }
    
    /**
     * Check if extension is allowed for images
     */
    private function isAllowedImageExtension(string $extension): bool {
        return in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS);
    }
    
    /**
     * Verify MIME type matches expected type for books
     */
    private function verifyBookMimeType(string $filePath, string $extension): bool {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        $expectedMime = self::BOOK_MIME_TYPES[$extension] ?? null;
        
        if ($expectedMime === null) {
            return false;
        }
        
        // For EPUB files, also accept application/zip as they are ZIP archives
        if ($extension === 'epub' && $detectedMime === 'application/zip') {
            return true;
        }
        
        return $detectedMime === $expectedMime;
    }

    
    /**
     * Check if GD extension is available
     */
    private function isGdAvailable(): bool {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }
    
    /**
     * Resize and save an image to the specified path
     * Falls back to simple copy if GD extension is not available
     * 
     * @param string $sourcePath Source image path
     * @param string $targetPath Target path for resized image
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function resizeAndSaveImage(string $sourcePath, string $targetPath): array {
        $result = ['success' => false, 'error' => null];
        
        // Get image info
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            $result['error'] = 'Could not read image information.';
            return $result;
        }
        
        // If GD is not available, fall back to simple copy
        if (!$this->isGdAvailable()) {
            return $this->copyImageWithoutResize($sourcePath, $targetPath);
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Create image resource based on type
        $sourceImage = $this->createImageFromFile($sourcePath, $mimeType);
        if ($sourceImage === null) {
            // Fallback to copy if image type not supported by GD
            return $this->copyImageWithoutResize($sourcePath, $targetPath);
        }
        
        // Calculate new dimensions while maintaining aspect ratio
        $newDimensions = $this->calculateResizeDimensions(
            $width, 
            $height, 
            self::COVER_MAX_WIDTH, 
            self::COVER_MAX_HEIGHT
        );
        
        // Create resized image
        $resizedImage = imagecreatetruecolor($newDimensions['width'], $newDimensions['height']);
        
        // Preserve transparency for PNG images
        if ($mimeType === 'image/png') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newDimensions['width'], $newDimensions['height'], $transparent);
        } else {
            // Fill with white background for non-transparent images
            $white = imagecolorallocate($resizedImage, 255, 255, 255);
            imagefilledrectangle($resizedImage, 0, 0, $newDimensions['width'], $newDimensions['height'], $white);
        }
        
        // Resize the image
        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $newDimensions['width'],
            $newDimensions['height'],
            $width,
            $height
        );
        
        // Save as JPEG with good quality
        $saveResult = imagejpeg($resizedImage, $targetPath, 85);
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        if ($saveResult) {
            $result['success'] = true;
        } else {
            $result['error'] = 'Failed to save resized image.';
        }
        
        return $result;
    }
    
    /**
     * Copy image without resizing (fallback when GD is not available)
     * 
     * @param string $sourcePath Source image path
     * @param string $targetPath Target path for image
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function copyImageWithoutResize(string $sourcePath, string $targetPath): array {
        $result = ['success' => false, 'error' => null];
        
        // Simply copy the file to the target location
        if (copy($sourcePath, $targetPath)) {
            $result['success'] = true;
        } else {
            $result['error'] = 'Failed to copy image file.';
        }
        
        return $result;
    }
    
    /**
     * Create image resource from file based on MIME type
     */
    private function createImageFromFile(string $filePath, string $mimeType): ?\GdImage {
        return match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($filePath),
            'image/png' => @imagecreatefrompng($filePath),
            'image/gif' => @imagecreatefromgif($filePath),
            'image/webp' => @imagecreatefromwebp($filePath),
            default => null
        };
    }
    
    /**
     * Calculate new dimensions while maintaining aspect ratio
     */
    private function calculateResizeDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array {
        // If image is smaller than max dimensions, keep original size
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return ['width' => $width, 'height' => $height];
        }
        
        // Calculate aspect ratio
        $ratio = $width / $height;
        
        // Calculate new dimensions
        if ($width / $maxWidth > $height / $maxHeight) {
            // Width is the limiting factor
            $newWidth = $maxWidth;
            $newHeight = (int) round($maxWidth / $ratio);
        } else {
            // Height is the limiting factor
            $newHeight = $maxHeight;
            $newWidth = (int) round($maxHeight * $ratio);
        }
        
        return ['width' => $newWidth, 'height' => $newHeight];
    }
    
    /**
     * Get human-readable upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server\'s maximum file size.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form\'s maximum file size.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by a PHP extension.',
            default => 'Unknown upload error occurred.'
        };
    }
    
    /**
     * Delete a book file
     * 
     * @param string $filename The filename to delete
     * @return bool True if deleted successfully
     */
    public function deleteBook(string $filename): bool {
        $filePath = $this->booksDir . '/' . basename($filename);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * Delete a cover image
     * 
     * @param string $filename The filename to delete
     * @return bool True if deleted successfully
     */
    public function deleteCover(string $filename): bool {
        $filePath = $this->coversDir . '/' . basename($filename);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * Get the full path to a book file
     */
    public function getBookPath(string $filename): string {
        return $this->booksDir . '/' . basename($filename);
    }
    
    /**
     * Get the full path to a cover image
     */
    public function getCoverPath(string $filename): string {
        return $this->coversDir . '/' . basename($filename);
    }
    
    /**
     * Check if a book file exists
     */
    public function bookExists(string $filename): bool {
        return file_exists($this->getBookPath($filename));
    }
    
    /**
     * Check if a cover image exists
     */
    public function coverExists(string $filename): bool {
        return file_exists($this->getCoverPath($filename));
    }
    
    /**
     * Get allowed book extensions
     */
    public static function getAllowedBookExtensions(): array {
        return self::ALLOWED_BOOK_EXTENSIONS;
    }
    
    /**
     * Get allowed image extensions
     */
    public static function getAllowedImageExtensions(): array {
        return self::ALLOWED_IMAGE_EXTENSIONS;
    }
    
    /**
     * Get maximum book file size
     */
    public static function getMaxBookSize(): int {
        return self::MAX_BOOK_SIZE;
    }
    
    /**
     * Get maximum image file size
     */
    public static function getMaxImageSize(): int {
        return self::MAX_IMAGE_SIZE;
    }
    
    /**
     * Download and save a cover image from URL
     * 
     * @param string $url The URL of the image
     * @return array ['success' => bool, 'filename' => string|null, 'errors' => array]
     */
    public function downloadCoverFromUrl(string $url): array {
        $result = [
            'success' => false,
            'filename' => null,
            'errors' => []
        ];
        
        // Validate URL
        $url = filter_var(trim($url), FILTER_VALIDATE_URL);
        if ($url === false) {
            $result['errors'][] = 'URL invalide.';
            return $result;
        }
        
        // Only allow http and https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array(strtolower($scheme), ['http', 'https'])) {
            $result['errors'][] = 'Seules les URLs HTTP et HTTPS sont autorisées.';
            return $result;
        }
        
        // Download the image
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'E-Lib Digital Library/1.0',
                'follow_location' => true,
                'max_redirects' => 3
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        if ($imageData === false) {
            $result['errors'][] = 'Impossible de télécharger l\'image depuis cette URL.';
            return $result;
        }
        
        // Check file size
        if (strlen($imageData) > self::MAX_IMAGE_SIZE) {
            $result['errors'][] = 'L\'image dépasse la taille maximale autorisée de ' . format_file_size(self::MAX_IMAGE_SIZE) . '.';
            return $result;
        }
        
        // Save to temp file to validate
        $tempFile = tempnam(sys_get_temp_dir(), 'cover_');
        if (file_put_contents($tempFile, $imageData) === false) {
            $result['errors'][] = 'Erreur lors de la sauvegarde temporaire.';
            return $result;
        }
        
        // Validate it's actually an image
        $imageInfo = @getimagesize($tempFile);
        if ($imageInfo === false) {
            unlink($tempFile);
            $result['errors'][] = 'Le fichier téléchargé n\'est pas une image valide.';
            return $result;
        }
        
        // Check MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageInfo['mime'], $allowedMimes)) {
            unlink($tempFile);
            $result['errors'][] = 'Type d\'image non supporté. Utilisez JPG, PNG, GIF ou WebP.';
            return $result;
        }
        
        // Determine extension from MIME type
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        $extension = $mimeToExt[$imageInfo['mime']] ?? 'jpg';
        
        // If GD is available, convert to jpg
        if ($this->isGdAvailable()) {
            $extension = 'jpg';
        }
        
        // Generate secure filename and save
        $secureFilename = $this->generateSecureFilename($extension);
        $targetPath = $this->coversDir . '/' . $secureFilename;
        
        // Resize and save (or copy if GD not available)
        $resizeResult = $this->resizeAndSaveImage($tempFile, $targetPath);
        unlink($tempFile);
        
        if ($resizeResult['success']) {
            $result['success'] = true;
            $result['filename'] = $secureFilename;
        } else {
            $result['errors'][] = $resizeResult['error'] ?? 'Échec du traitement de l\'image.';
        }
        
        return $result;
    }
    
    /**
     * Extract cover image from EPUB file
     * 
     * @param string $epubPath Path to the EPUB file
     * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
     */
    public function extractEpubCover(string $epubPath): array {
        $result = [
            'success' => false,
            'filename' => null,
            'error' => null
        ];
        
        if (!file_exists($epubPath)) {
            $result['error'] = 'EPUB file not found.';
            return $result;
        }
        
        // EPUB files are ZIP archives
        $zip = new ZipArchive();
        if ($zip->open($epubPath) !== true) {
            $result['error'] = 'Could not open EPUB file.';
            return $result;
        }
        
        try {
            // Common cover image patterns in EPUB files
            $coverPatterns = [
                'cover.jpg', 'cover.jpeg', 'cover.png', 'cover.gif',
                'Cover.jpg', 'Cover.jpeg', 'Cover.png', 'Cover.gif',
                'OEBPS/cover.jpg', 'OEBPS/cover.jpeg', 'OEBPS/cover.png',
                'OEBPS/images/cover.jpg', 'OEBPS/images/cover.jpeg', 'OEBPS/images/cover.png',
                'OEBPS/Images/cover.jpg', 'OEBPS/Images/cover.jpeg', 'OEBPS/Images/cover.png',
                'images/cover.jpg', 'images/cover.jpeg', 'images/cover.png',
                'Images/cover.jpg', 'Images/cover.jpeg', 'Images/cover.png',
            ];
            
            $coverData = null;
            $coverExtension = 'jpg';
            
            // Try common patterns first
            foreach ($coverPatterns as $pattern) {
                $index = $zip->locateName($pattern, ZipArchive::FL_NOCASE);
                if ($index !== false) {
                    $coverData = $zip->getFromIndex($index);
                    $coverExtension = pathinfo($pattern, PATHINFO_EXTENSION);
                    break;
                }
            }
            
            // If not found, try to find cover from OPF metadata
            if ($coverData === null) {
                $coverData = $this->findCoverFromOpf($zip);
                if ($coverData !== null) {
                    $coverExtension = 'jpg'; // Default to jpg
                }
            }
            
            // If still not found, look for any image with "cover" in the name
            if ($coverData === null) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $lowerFilename = strtolower($filename);
                    if (strpos($lowerFilename, 'cover') !== false && 
                        preg_match('/\.(jpg|jpeg|png|gif)$/i', $filename)) {
                        $coverData = $zip->getFromIndex($i);
                        $coverExtension = pathinfo($filename, PATHINFO_EXTENSION);
                        break;
                    }
                }
            }
            
            $zip->close();
            
            if ($coverData === null) {
                $result['error'] = 'No cover image found in EPUB.';
                return $result;
            }
            
            // Save the cover image
            $secureFilename = $this->generateSecureFilename($coverExtension);
            $targetPath = $this->coversDir . '/' . $secureFilename;
            
            if (file_put_contents($targetPath, $coverData) !== false) {
                // Try to resize if GD is available
                if ($this->isGdAvailable()) {
                    $resizeResult = $this->resizeAndSaveImage($targetPath, $targetPath);
                    if (!$resizeResult['success']) {
                        // Keep original if resize fails
                    }
                }
                
                $result['success'] = true;
                $result['filename'] = $secureFilename;
            } else {
                $result['error'] = 'Failed to save cover image.';
            }
            
        } catch (Exception $e) {
            $zip->close();
            $result['error'] = 'Error extracting cover: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Find cover image from OPF metadata
     * 
     * @param ZipArchive $zip The opened EPUB ZIP archive
     * @return string|null Cover image data or null
     */
    private function findCoverFromOpf(ZipArchive $zip): ?string {
        // Find container.xml to locate OPF file
        $containerXml = $zip->getFromName('META-INF/container.xml');
        if ($containerXml === false) {
            return null;
        }
        
        // Parse container.xml to find OPF path
        $xml = @simplexml_load_string($containerXml);
        if ($xml === false) {
            return null;
        }
        
        $xml->registerXPathNamespace('c', 'urn:oasis:names:tc:opendocument:xmlns:container');
        $rootfiles = $xml->xpath('//c:rootfile[@media-type="application/oebps-package+xml"]/@full-path');
        
        if (empty($rootfiles)) {
            return null;
        }
        
        $opfPath = (string)$rootfiles[0];
        $opfContent = $zip->getFromName($opfPath);
        if ($opfContent === false) {
            return null;
        }
        
        // Parse OPF to find cover
        $opf = @simplexml_load_string($opfContent);
        if ($opf === false) {
            return null;
        }
        
        $opf->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');
        
        // Look for cover meta tag
        $coverMeta = $opf->xpath('//opf:meta[@name="cover"]/@content');
        if (!empty($coverMeta)) {
            $coverId = (string)$coverMeta[0];
            
            // Find the item with this ID
            $coverItem = $opf->xpath("//opf:item[@id='$coverId']/@href");
            if (!empty($coverItem)) {
                $coverHref = (string)$coverItem[0];
                $opfDir = dirname($opfPath);
                $coverPath = ($opfDir !== '.') ? $opfDir . '/' . $coverHref : $coverHref;
                
                $coverData = $zip->getFromName($coverPath);
                if ($coverData !== false) {
                    return $coverData;
                }
            }
        }
        
        // Look for item with properties="cover-image"
        $coverItem = $opf->xpath('//opf:item[@properties="cover-image"]/@href');
        if (!empty($coverItem)) {
            $coverHref = (string)$coverItem[0];
            $opfDir = dirname($opfPath);
            $coverPath = ($opfDir !== '.') ? $opfDir . '/' . $coverHref : $coverHref;
            
            $coverData = $zip->getFromName($coverPath);
            if ($coverData !== false) {
                return $coverData;
            }
        }
        
        return null;
    }
}
