-- Migration: Create login_attempts table for brute force protection
-- Tracks failed login attempts by email and IP

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email`      VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL       COMMENT 'IPv4 or IPv6',
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_email_time` (`email`, `attempted_at`),
    INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
