-- Migration: Create meal_categories table
-- Groups time slots by meal type (brunch, pranzo, aperitivo, cena, after_dinner)

CREATE TABLE IF NOT EXISTS `meal_categories` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `name`          VARCHAR(50) NOT NULL        COMMENT 'Machine key: brunch, pranzo, aperitivo, cena, after_dinner',
    `display_name`  VARCHAR(100) NOT NULL       COMMENT 'Label shown in widget',
    `start_time`    TIME NOT NULL,
    `end_time`      TIME NOT NULL,
    `sort_order`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_tenant_name` (`tenant_id`, `name`),
    INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default categories for demo tenant (id=1)
INSERT INTO `meal_categories` (`tenant_id`, `name`, `display_name`, `start_time`, `end_time`, `sort_order`, `is_active`) VALUES
(1, 'brunch',       'Brunch',       '09:00:00', '11:30:00', 1, 1),
(1, 'pranzo',       'Pranzo',       '11:30:00', '15:00:00', 2, 1),
(1, 'aperitivo',    'Aperitivo',    '17:00:00', '19:00:00', 3, 1),
(1, 'cena',         'Cena',         '19:00:00', '22:30:00', 4, 1),
(1, 'after_dinner', 'After Dinner', '22:30:00', '23:59:00', 5, 1);
