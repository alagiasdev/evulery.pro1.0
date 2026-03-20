-- Migration 013: Menu enhancements for v2.1
-- - Add icon field to menu_categories
-- - Add menu config fields to tenants (hero, tagline, hours)

ALTER TABLE `menu_categories`
    ADD COLUMN `icon` VARCHAR(50) NOT NULL DEFAULT 'bi-list' AFTER `description`;

ALTER TABLE `tenants`
    ADD COLUMN `menu_hero_image` VARCHAR(500) DEFAULT NULL AFTER `menu_enabled`,
    ADD COLUMN `menu_tagline` VARCHAR(200) DEFAULT NULL AFTER `menu_hero_image`,
    ADD COLUMN `opening_hours` VARCHAR(500) DEFAULT NULL AFTER `menu_tagline`;
