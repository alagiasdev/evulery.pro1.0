-- ============================================================
-- Migration 040b: REPAIR for 040_add_booking_number.sql
-- Use this ONLY if 040 failed on MariaDB < 10.2 (no window functions).
-- Assumes: tenant_booking_counters exists (empty),
--          reservations.booking_number column exists (all zeros),
--          unique index NOT yet applied.
-- Idempotent: safe to re-run.
-- ============================================================

-- Backfill using session variables (portable across MySQL 5.7+ / MariaDB).
-- We force per-row evaluation by wrapping in a derived table.
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

-- Seed the counter table with current max per tenant.
INSERT INTO `tenant_booking_counters` (`tenant_id`, `last_number`)
SELECT tenant_id, MAX(booking_number)
FROM `reservations`
GROUP BY tenant_id
ON DUPLICATE KEY UPDATE `last_number` = VALUES(`last_number`);

-- Unique constraint: enforce one number per tenant going forward.
ALTER TABLE `reservations`
    ADD UNIQUE KEY `uq_tenant_booking_number` (`tenant_id`, `booking_number`);
