-- ============================================================
-- Migration 045: Vetrina Digitale — sub-text per custom links
--
-- Aggiunge un campo opzionale `custom_sub` ai custom links della
-- Vetrina, equivalente al `sub` dei preset (es. "Aiutaci a migliorare").
-- Visibile sia in dashboard che nella public hub sotto la label.
-- ============================================================

ALTER TABLE `tenant_hub_actions`
    ADD COLUMN `custom_sub` VARCHAR(100) DEFAULT NULL
        AFTER `custom_url`;
