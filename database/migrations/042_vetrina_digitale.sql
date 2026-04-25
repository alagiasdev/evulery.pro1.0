-- ============================================================
-- Migration 042: Vetrina Digitale (Hub)
--
-- Pagina pubblica aggregatrice per ogni ristorante, accessibile a
-- /{slug}/hub. Sostituisce il bisogno di servizi esterni
-- (LinkSquare-like) mantenendo la coerenza UX con Evulery.
--
-- Tabelle:
--   tenant_hub_settings (1:1) — branding, palette, social, advanced
--   tenant_hub_actions (1:many) — preset + custom links con drag-to-reorder
--
-- Service: 'vetrina_digitale' su Professional + Enterprise.
-- Feature avanzate Enterprise (custom colors, font, white-label,
-- custom links unlimited) gestite via plan tier check nel controller.
-- ============================================================

-- 1. Tabella settings (1:1 con tenants)
CREATE TABLE IF NOT EXISTS `tenant_hub_settings` (
    `tenant_id`        INT UNSIGNED NOT NULL PRIMARY KEY,
    `enabled`          TINYINT(1) NOT NULL DEFAULT 0,
    -- Branding base (Professional+)
    `palette`          VARCHAR(20) NOT NULL DEFAULT 'evulery_green',
    `logo_url`         VARCHAR(255) DEFAULT NULL,
    `cover_url`        VARCHAR(255) DEFAULT NULL,
    `subtitle`         VARCHAR(150) DEFAULT NULL,
    -- Branding avanzato (Enterprise only — NULL altrove)
    `custom_primary`   VARCHAR(7)  DEFAULT NULL,
    `custom_accent`    VARCHAR(7)  DEFAULT NULL,
    `custom_bg`        VARCHAR(7)  DEFAULT NULL,
    `custom_font`      VARCHAR(30) DEFAULT NULL,
    `hide_branding`    TINYINT(1) NOT NULL DEFAULT 0,
    -- Social footer (6 link)
    `instagram_url`    VARCHAR(255) DEFAULT NULL,
    `facebook_url`     VARCHAR(255) DEFAULT NULL,
    `tiktok_url`       VARCHAR(255) DEFAULT NULL,
    `twitter_url`      VARCHAR(255) DEFAULT NULL,
    `youtube_url`      VARCHAR(255) DEFAULT NULL,
    `whatsapp_number`  VARCHAR(30)  DEFAULT NULL,
    -- Meta
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_hub_settings_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabella actions (1:many con tenant_hub_settings via tenant_id)
CREATE TABLE IF NOT EXISTS `tenant_hub_actions` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `action_type`   ENUM('preset', 'custom') NOT NULL,
    -- Preset: identifica l'azione predefinita (URL computato a render-time)
    `preset_key`    VARCHAR(30) DEFAULT NULL,
    -- Custom (Enterprise): label + URL + icona scelti dal ristoratore
    `custom_label`  VARCHAR(100) DEFAULT NULL,
    `custom_url`    VARCHAR(500) DEFAULT NULL,
    `custom_icon`   VARCHAR(40)  DEFAULT NULL,
    -- Common
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant_sort` (`tenant_id`, `sort_order`),
    UNIQUE KEY `uq_tenant_preset` (`tenant_id`, `preset_key`),
    CONSTRAINT `fk_hub_actions_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Service gating
INSERT INTO `services` (`key`, `name`, `description`, `sort_order`, `is_active`)
VALUES ('vetrina_digitale', 'Vetrina Digitale', 'Pagina pubblica aggregatrice del ristorante con QR code stampabile, link in bio per Instagram, biglietti da visita.', 100, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- 4. Attach service to Professional + Enterprise plans
INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id
FROM `plans` p
CROSS JOIN `services` s
WHERE p.name IN ('Professional', 'Enterprise')
  AND s.`key` = 'vetrina_digitale'
ON DUPLICATE KEY UPDATE `plan_id` = VALUES(`plan_id`);
