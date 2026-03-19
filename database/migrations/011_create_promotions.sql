-- Migration: Create promotions table + add discount_percent to reservations
-- FASE 14: Promozioni e Badge Sconto

CREATE TABLE IF NOT EXISTS `promotions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `discount_percent` TINYINT UNSIGNED NOT NULL,
    `type` ENUM('recurring','time_slot','specific_date') NOT NULL,
    `days_of_week` VARCHAR(20) DEFAULT NULL COMMENT 'CSV: 0=Lun,1=Mar,...,6=Dom (date N -1)',
    `time_from` TIME DEFAULT NULL,
    `time_to` TIME DEFAULT NULL,
    `date_from` DATE DEFAULT NULL,
    `date_to` DATE DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `reservations` ADD COLUMN `discount_percent` TINYINT UNSIGNED DEFAULT NULL AFTER `source`;
