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
-- Portable pattern (no window functions) for MariaDB < 10.2 compatibility.
SET @rn := 0;
SET @prev := 0;

UPDATE `reservations` r
INNER JOIN (
    SELECT id, rn FROM (
        SELECT id,
               @rn := IF(@prev = tenant_id, @rn + 1, 1) AS rn,
               @prev := tenant_id AS _p
        FROM `reservations`
        ORDER BY tenant_id ASC, created_at ASC, id ASC
    ) s
) sq ON sq.id = r.id
SET r.booking_number = sq.rn;

-- Seed the counter table with the current max per tenant.
INSERT INTO `tenant_booking_counters` (`tenant_id`, `last_number`)
SELECT tenant_id, MAX(booking_number)
FROM `reservations`
GROUP BY tenant_id
ON DUPLICATE KEY UPDATE `last_number` = VALUES(`last_number`);

-- Unique constraint: enforce one number per tenant going forward.
ALTER TABLE `reservations`
    ADD UNIQUE KEY `uq_tenant_booking_number` (`tenant_id`, `booking_number`);
