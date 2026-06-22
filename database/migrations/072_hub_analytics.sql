-- Migration 072: Statistiche Vetrina Digitale (Hub) — visite + click pulsanti
--
-- Completa il funnel marketing: campagna (utm) -> VISITE Hub -> CLICK pulsanti
-- -> prenotazioni (gia' tracciate via reservations.channel/via_hub).
-- Entrambe le tabelle salvano l'UTM in arrivo cosi' tutto si affetta per
-- canale + campagna (incrocio col tool Genera link).

CREATE TABLE IF NOT EXISTS `hub_visits` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `channel`      VARCHAR(40)  NOT NULL DEFAULT 'hub',
    `utm_source`   VARCHAR(100) DEFAULT NULL,
    `utm_medium`   VARCHAR(60)  DEFAULT NULL,
    `utm_campaign` VARCHAR(120) DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_tenant_date` (`tenant_id`, `created_at`),
    INDEX `idx_tenant_channel` (`tenant_id`, `channel`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `hub_clicks` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`    INT UNSIGNED NOT NULL,
    -- identificatore pulsante: preset_key (es. 'booking','menu','whatsapp'),
    -- 'custom:<id>', 'hero:<key>' o 'social:<rete>'
    `ref`          VARCHAR(60)  NOT NULL,
    `button_type`  ENUM('hero','preset','custom','social') NOT NULL DEFAULT 'preset',
    `label`        VARCHAR(120) DEFAULT NULL,
    `channel`      VARCHAR(40)  NOT NULL DEFAULT 'hub',
    `utm_source`   VARCHAR(100) DEFAULT NULL,
    `utm_medium`   VARCHAR(60)  DEFAULT NULL,
    `utm_campaign` VARCHAR(120) DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_tenant_date` (`tenant_id`, `created_at`),
    INDEX `idx_tenant_channel` (`tenant_id`, `channel`),
    INDEX `idx_tenant_ref` (`tenant_id`, `ref`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
