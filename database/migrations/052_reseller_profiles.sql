-- ============================================================
-- Migration 052: Estensione ruolo reseller + tabella profili
--
-- 1) users.role: da ENUM('super_admin','owner','staff') a VARCHAR(20)
--    Aggiunge 'reseller' senza vincoli ENUM (Lesson #14: state machines
--    che evolvono fanno male in ENUM).
--
-- 2) tabella reseller_profiles: dati estesi specifici per i reseller
--    (commissioni configurabili per-reseller, decisione 2026-05-11).
--    Tabella separata per non appesantire users e permettere estensioni
--    future (partner_code, partita_iva, ecc.) senza migration.
--
-- 3) tenants.acquired_by_reseller_id: snapshot del reseller che ha
--    portato il tenant (Opzione A, decisa il 2026-05-11). NULL = house.
-- ============================================================

-- 1) users.role -> VARCHAR(20)
ALTER TABLE `users`
    MODIFY COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'owner';

-- 2) Tabella reseller_profiles
CREATE TABLE IF NOT EXISTS `reseller_profiles` (
    `user_id`                 INT UNSIGNED NOT NULL,
    `commission_setup`        DECIMAL(8,2) NOT NULL DEFAULT 130.00,
    `commission_starter`      DECIMAL(8,2) NOT NULL DEFAULT 120.00,
    `commission_professional` DECIMAL(8,2) NOT NULL DEFAULT 200.00,
    `commission_enterprise`   DECIMAL(8,2) NOT NULL DEFAULT 320.00,
    `notes`                   TEXT NULL,
    `created_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_reseller_profile_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) tenants.acquired_by_reseller_id
ALTER TABLE `tenants`
    ADD COLUMN `acquired_by_reseller_id` INT UNSIGNED NULL AFTER `plan_id`,
    ADD INDEX `idx_acquired_reseller` (`acquired_by_reseller_id`);
