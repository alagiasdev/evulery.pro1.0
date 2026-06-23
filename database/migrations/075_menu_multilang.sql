-- Migration 075: Menu multilingua (manuale, scalabile a N lingue)
--
-- Architettura: tabella traduzioni generica (entity_type/entity_id/lang/field/value)
-- con fallback all'italiano se manca una traduzione. L'italiano resta il testo
-- "base" nelle colonne originali (menu_categories.name/description,
-- menu_items.name/description, tenants.menu_tagline/menu_featured_label).
-- Aggiungere domani DE/FR/ES = solo righe in piu' + lingua nello switcher: zero schema.
--
-- Gated dalla service key 'menu_multilang' (Professional + Enterprise).

CREATE TABLE IF NOT EXISTS `menu_translations` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `entity_type` VARCHAR(20)  NOT NULL COMMENT "'category' | 'item' | 'tenant'",
    `entity_id`   INT UNSIGNED NOT NULL,
    `lang`        VARCHAR(5)   NOT NULL COMMENT "es. 'en', 'de', 'fr'",
    `field`       VARCHAR(40)  NOT NULL COMMENT "'name' | 'description' | 'tagline' | 'featured_label'",
    `value`       TEXT         NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_translation` (`tenant_id`, `entity_type`, `entity_id`, `lang`, `field`),
    INDEX `idx_lookup` (`tenant_id`, `entity_type`, `lang`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Lingue attive per tenant (CSV, 'it' sempre presente come base). Es. "it,en".
ALTER TABLE `tenants`
    ADD COLUMN `menu_languages` VARCHAR(100) NOT NULL DEFAULT 'it' AFTER `menu_featured_label`;

-- Servizio gated
INSERT INTO `services` (`key`, `name`, `description`, `sort_order`, `is_active`)
VALUES ('menu_multilang', 'Menu multilingua', 'Offri il menu digitale in più lingue (es. Inglese) con switcher automatico per i clienti stranieri.', 120, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id
FROM `plans` p
CROSS JOIN `services` s
WHERE p.name IN ('Professional', 'Enterprise')
  AND s.`key` = 'menu_multilang'
ON DUPLICATE KEY UPDATE `plan_id` = VALUES(`plan_id`);
