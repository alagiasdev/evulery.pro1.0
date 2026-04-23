-- ============================================================
-- Migration 041: Extend tenants.domain_status for new state machine
--
-- Old: ENUM('none','dns_pending','linked')
-- New: VARCHAR(20) to allow: none, dns_pending, dns_ok, ssl_pending,
--      active, linked (legacy), error
--
-- VARCHAR chosen over ENUM to avoid future ALTERs when adding states.
-- Backward compat: existing 'linked' rows are kept as-is; code reads
-- 'linked' and 'active' equivalently.
-- ============================================================

ALTER TABLE `tenants`
    MODIFY COLUMN `domain_status` VARCHAR(20) NOT NULL DEFAULT 'none';
