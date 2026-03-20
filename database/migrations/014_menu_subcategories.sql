-- Migration 014: Add subcategories support (max 1 level depth)
-- parent_id NULL = top-level category, parent_id = N = subcategory of category N

ALTER TABLE `menu_categories`
    ADD COLUMN `parent_id` INT UNSIGNED DEFAULT NULL AFTER `tenant_id`,
    ADD INDEX `idx_parent_id` (`parent_id`);
