CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `tenant_id` INT UNSIGNED NULL,
    `event` VARCHAR(50) NOT NULL,
    `description` VARCHAR(500) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_event` (`event`),
    INDEX `idx_user` (`user_id`, `created_at`),
    INDEX `idx_tenant` (`tenant_id`, `created_at`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
