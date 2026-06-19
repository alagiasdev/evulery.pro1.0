-- Migration 071: Campagne salvate (Marketing > Genera link)
--
-- Salva i link tracciati generati dal ristoratore, per evitare duplicati,
-- standardizzare i nomi campagna e mostrare le performance per campagna
-- (join su channel + utm_campaign con reservations).
--
-- destination: dove punta il link (hub/booking/menu/order).
-- channel: canale canonico derivato (AttributionService), usato per il match
--          con reservations.channel nel conteggio prenotazioni.

CREATE TABLE IF NOT EXISTS `marketing_links` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `destination`  ENUM('hub','booking','menu','order') NOT NULL DEFAULT 'hub',
    `utm_source`   VARCHAR(100) NOT NULL,
    `utm_medium`   VARCHAR(60)  DEFAULT NULL,
    `utm_campaign` VARCHAR(120) DEFAULT NULL,
    `channel`      VARCHAR(40)  NOT NULL,
    `url`          VARCHAR(500) NOT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_tenant` (`tenant_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
