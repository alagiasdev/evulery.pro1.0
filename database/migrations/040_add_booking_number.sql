-- ============================================================
-- Migration 040: Per-tenant sequential booking number
-- Display-friendly reservation number (#1, #2, ...) scoped per tenant.
-- Internal `id` is unchanged (still primary key and used in URLs).
-- ============================================================

-- Per-tenant counter table: holds the last allocated booking number.
CREATE TABLE IF NOT EXISTS `tenant_booking_counters` (
    `tenant_id`   INT UNSIGNED NOT NULL PRIMARY KEY,
    `last_number` INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `fk_tbc_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add booking_number column to reservations (default 0 until backfilled).
ALTER TABLE `reservations`
    ADD COLUMN `booking_number` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;

-- Backfill: for each tenant, number reservations 1..N ordered by created_at ASC, id ASC.
-- Requires MySQL 8.0+ / MariaDB 10.2+ for ROW_NUMBER().
UPDATE `reservations` r
JOIN (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY tenant_id ORDER BY created_at ASC, id ASC) AS rn
    FROM `reservations`
) numbered ON numbered.id = r.id
SET r.booking_number = numbered.rn;

-- Seed the counter table with the current max per tenant.
INSERT INTO `tenant_booking_counters` (`tenant_id`, `last_number`)
SELECT tenant_id, MAX(booking_number)
FROM `reservations`
GROUP BY tenant_id
ON DUPLICATE KEY UPDATE `last_number` = VALUES(`last_number`);

-- Unique constraint: enforce one number per tenant going forward.
ALTER TABLE `reservations`
    ADD UNIQUE KEY `uq_tenant_booking_number` (`tenant_id`, `booking_number`);
