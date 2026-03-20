-- Migration 012: Digital Menu (FASE 20A)
-- Tables: menu_categories, menu_items
-- Alter: tenants.menu_enabled

CREATE TABLE IF NOT EXISTS `menu_categories` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `name`        VARCHAR(100) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `sort_order`  INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `category_id`      INT UNSIGNED NOT NULL,
    `name`             VARCHAR(150) NOT NULL,
    `description`      TEXT DEFAULT NULL,
    `price`            DECIMAL(8,2) NOT NULL,
    `image_url`        VARCHAR(500) DEFAULT NULL,
    `allergens`        JSON DEFAULT NULL COMMENT 'JSON array: ["gluten","eggs","milk",...]',
    `is_available`     TINYINT(1) NOT NULL DEFAULT 1,
    `is_daily_special` TINYINT(1) NOT NULL DEFAULT 0,
    `is_orderable`     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'FASE 20B: abilitato per asporto',
    `sort_order`       INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenant_category` (`tenant_id`, `category_id`),
    INDEX `idx_tenant_available` (`tenant_id`, `is_available`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `menu_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Enable menu feature per tenant
ALTER TABLE `tenants` ADD COLUMN `menu_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `timezone`;

-- ============================================================
-- FASE 20B (future): Ordini Asporto
-- ============================================================
-- CREATE TABLE IF NOT EXISTS `orders` (
--     `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     `tenant_id`         INT UNSIGNED NOT NULL,
--     `customer_id`       INT UNSIGNED DEFAULT NULL,
--     `order_type`        ENUM('takeaway','delivery') NOT NULL DEFAULT 'takeaway',
--     `status`            ENUM('pending','confirmed','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
--     `pickup_time`       DATETIME DEFAULT NULL,
--     `total`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
--     `notes`             TEXT DEFAULT NULL,
--     `customer_name`     VARCHAR(100) NOT NULL,
--     `customer_phone`    VARCHAR(30) NOT NULL,
--     `customer_email`    VARCHAR(255) DEFAULT NULL,
--     `stripe_payment_id` VARCHAR(255) DEFAULT NULL COMMENT 'FASE 20C',
--     `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     INDEX `idx_tenant_status` (`tenant_id`, `status`),
--     FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
--     FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- CREATE TABLE IF NOT EXISTS `order_items` (
--     `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     `order_id`      INT UNSIGNED NOT NULL,
--     `menu_item_id`  INT UNSIGNED NOT NULL,
--     `quantity`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
--     `unit_price`    DECIMAL(8,2) NOT NULL,
--     `notes`         VARCHAR(500) DEFAULT NULL,
--     FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
--     FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE RESTRICT
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
