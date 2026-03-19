-- Migration: Add customer segment thresholds to tenants
-- Configurable thresholds: occasionale (default 2), abituale (4), vip (10)

ALTER TABLE `tenants`
  ADD COLUMN `segment_occasionale` INT UNSIGNED NOT NULL DEFAULT 2,
  ADD COLUMN `segment_abituale` INT UNSIGNED NOT NULL DEFAULT 4,
  ADD COLUMN `segment_vip` INT UNSIGNED NOT NULL DEFAULT 10;