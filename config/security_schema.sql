-- Security Enhancement Database Schema
-- Additional tables for enhanced security features
-- Requirements: 7.1, 7.2, 9.4, 5.1

USE elib_database;

-- Security events table for comprehensive audit logging
-- Stores all security-related events for monitoring and analysis
CREATE TABLE security_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    event_data JSON,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at)
);

-- Rate limiting table for tracking and controlling request rates
-- Prevents brute force attacks and abuse
CREATE TABLE rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL,
    attempt_count INT DEFAULT 0,
    first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    UNIQUE KEY unique_identifier (identifier),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_last_attempt (last_attempt)
);

-- Password history table for preventing password reuse
-- Maintains history of user passwords to enforce security policies
CREATE TABLE password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Secure sessions table for enhanced session management
-- Tracks session integrity and detects potential hijacking
CREATE TABLE secure_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent_hash VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_valid BOOLEAN DEFAULT TRUE,
    fingerprint_data JSON,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),
    INDEX idx_is_valid (is_valid)
);

-- CSRF tokens table for managing Cross-Site Request Forgery protection
-- Stores and validates CSRF tokens for form submissions
CREATE TABLE csrf_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_hash VARCHAR(64) NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(128),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    is_valid BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_valid (is_valid)
);

-- Failed login attempts table for detailed tracking
-- Provides granular tracking of authentication failures
CREATE TABLE failed_login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL, -- username, email, or IP
    identifier_type ENUM('username', 'email', 'ip') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    failure_reason VARCHAR(100),
    INDEX idx_identifier (identifier),
    INDEX idx_identifier_type (identifier_type),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
);

-- Security configuration table for dynamic security settings
-- Allows runtime configuration of security parameters
CREATE TABLE security_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    config_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_config_key (config_key)
);

-- Insert default security configuration values
INSERT INTO security_config (config_key, config_value, config_type, description) VALUES
('max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lockout'),
('lockout_duration', '900', 'integer', 'Account lockout duration in seconds (15 minutes)'),
('session_timeout', '1800', 'integer', 'Session timeout in seconds (30 minutes)'),
('password_min_length', '8', 'integer', 'Minimum password length requirement'),
('password_require_uppercase', 'true', 'boolean', 'Require uppercase letters in passwords'),
('password_require_lowercase', 'true', 'boolean', 'Require lowercase letters in passwords'),
('password_require_numbers', 'true', 'boolean', 'Require numbers in passwords'),
('password_require_symbols', 'false', 'boolean', 'Require special symbols in passwords'),
('password_history_count', '5', 'integer', 'Number of previous passwords to remember'),
('csrf_token_lifetime', '3600', 'integer', 'CSRF token lifetime in seconds (1 hour)'),
('rate_limit_window', '300', 'integer', 'Rate limiting time window in seconds (5 minutes)'),
('enable_session_fingerprinting', 'true', 'boolean', 'Enable session fingerprinting for security'),
('enable_ip_tracking', 'false', 'boolean', 'Enable IP address tracking (may cause issues with mobile users)'),
('security_headers_enabled', 'true', 'boolean', 'Enable enhanced security headers'),
('audit_all_actions', 'true', 'boolean', 'Log all user actions for audit purposes');

-- Create indexes for performance optimization
-- Additional composite indexes for common query patterns
CREATE INDEX idx_security_events_user_type ON security_events(user_id, event_type);
CREATE INDEX idx_security_events_ip_time ON security_events(ip_address, created_at);
CREATE INDEX idx_rate_limits_identifier_time ON rate_limits(identifier, last_attempt);
CREATE INDEX idx_password_history_user_time ON password_history(user_id, created_at);
CREATE INDEX idx_secure_sessions_user_activity ON secure_sessions(user_id, last_activity);
CREATE INDEX idx_failed_attempts_ip_time ON failed_login_attempts(ip_address, attempted_at);

-- Create a view for active security events (last 24 hours)
CREATE VIEW recent_security_events AS
SELECT 
    se.*,
    u.username,
    u.email
FROM security_events se
LEFT JOIN users u ON se.user_id = u.id
WHERE se.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY se.created_at DESC;

-- Create a view for current rate limit status
CREATE VIEW current_rate_limits AS
SELECT 
    identifier,
    attempt_count,
    first_attempt,
    last_attempt,
    blocked_until,
    CASE 
        WHEN blocked_until IS NULL THEN 'active'
        WHEN blocked_until > NOW() THEN 'blocked'
        ELSE 'expired'
    END as status
FROM rate_limits
WHERE last_attempt >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY last_attempt DESC;

-- Create a stored procedure for cleaning up old security data
DELIMITER //
CREATE PROCEDURE CleanupSecurityData()
BEGIN
    -- Clean up old security events (keep 90 days)
    DELETE FROM security_events 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Clean up expired CSRF tokens
    DELETE FROM csrf_tokens 
    WHERE expires_at < NOW() OR used_at IS NOT NULL;
    
    -- Clean up old rate limit entries (keep 24 hours)
    DELETE FROM rate_limits 
    WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
    AND (blocked_until IS NULL OR blocked_until < NOW());
    
    -- Clean up old failed login attempts (keep 30 days)
    DELETE FROM failed_login_attempts 
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Clean up invalid sessions
    DELETE FROM secure_sessions 
    WHERE is_valid = FALSE 
    OR last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Clean up old password history (keep only last 10 per user)
    DELETE ph1 FROM password_history ph1
    INNER JOIN (
        SELECT user_id, id
        FROM password_history ph2
        WHERE (
            SELECT COUNT(*)
            FROM password_history ph3
            WHERE ph3.user_id = ph2.user_id
            AND ph3.created_at >= ph2.created_at
        ) > 10
    ) ph_old ON ph1.id = ph_old.id;
END //
DELIMITER ;

-- Create an event to run cleanup procedure daily
-- Note: This requires EVENT_SCHEDULER to be enabled
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS daily_security_cleanup
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_TIMESTAMP
-- DO CALL CleanupSecurityData();