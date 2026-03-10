-- E-Lib Digital Library Database Schema
-- Create database and tables for the digital library system

CREATE DATABASE IF NOT EXISTS elib_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE elib_database;

-- Users table for authentication and role management
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'librarian', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Categories table for book classification
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Books table for storing book metadata and file information
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('pdf', 'epub') NOT NULL,
    cover_path VARCHAR(500),
    category_id INT,
    uploaded_by INT NOT NULL,
    file_size BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category_id),
    INDEX idx_file_type (file_type),
    FULLTEXT idx_search (title, author, description)
);

-- Reading progress table for tracking user reading sessions
CREATE TABLE reading_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    progress_data JSON,
    last_position VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, book_id),
    INDEX idx_user_progress (user_id)
);

-- Logs table for audit trail and system monitoring
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_logs (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Favorites table for user bookmarks
CREATE TABLE favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, book_id),
    INDEX idx_user_favorites (user_id),
    INDEX idx_book_favorites (book_id)
);

-- Insert default admin user (password: admin123)
-- Hash generated with: password_hash('admin123', PASSWORD_DEFAULT, ['cost' => 12])
INSERT INTO users (username, email, password_hash, role) VALUES 
('admin', 'admin@elib.local', '$2y$12$0y60E6hiiHmqYYb4V3nOve9tXT93tUYpbKis6F9qBc33nlVCZRMYe', 'admin');

-- Insert default categories
INSERT INTO categories (name, description) VALUES 
('Fiction', 'Novels, short stories, and other fictional works'),
('Non-Fiction', 'Biographies, essays, and factual books'),
('Science', 'Scientific texts and research publications'),
('Technology', 'Computer science, programming, and technical manuals'),
('History', 'Historical texts and documentation'),
('Literature', 'Classic and contemporary literature'),
('Education', 'Educational materials and textbooks'),
('Reference', 'Dictionaries, encyclopedias, and reference materials');