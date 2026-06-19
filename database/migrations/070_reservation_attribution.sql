-- Migration 070: Provenienza prenotazioni (attribuzione marketing)
--
-- Capire da quale canale arrivano le prenotazioni (Meta, Google, TikTok,
-- Instagram, Hub/QR, generico) per dirottare il budget pubblicitario.
--
-- 1) Colonne UTM + canale derivato + flag "passato dall'Hub" su reservations.
--    NULL = nessuna info (prenotazione diretta / telefono / walk-in).
--    La CATTURA e' libera per tutti (innocua, invisibile); il VALORE (report +
--    generatore link) e' gated dalla service key 'marketing' (Pro+Ent).
-- 2) Service 'marketing' su Professional + Enterprise.

ALTER TABLE `reservations`
    ADD COLUMN `utm_source`   VARCHAR(100) DEFAULT NULL AFTER `source`,
    ADD COLUMN `utm_medium`   VARCHAR(60)  DEFAULT NULL AFTER `utm_source`,
    ADD COLUMN `utm_campaign` VARCHAR(120) DEFAULT NULL AFTER `utm_medium`,
    ADD COLUMN `channel`      VARCHAR(40)  DEFAULT NULL AFTER `utm_campaign`,
    ADD COLUMN `via_hub`      TINYINT(1)   NOT NULL DEFAULT 0 AFTER `channel`,
    ADD INDEX `idx_tenant_channel` (`tenant_id`, `channel`);

INSERT INTO `services` (`key`, `name`, `description`, `sort_order`, `is_active`)
VALUES ('marketing', 'Marketing & Provenienza', 'Capire da quale canale arrivano le prenotazioni (Meta, Google, TikTok, Hub...) e generare link tracciati per le campagne.', 110, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id
FROM `plans` p
CROSS JOIN `services` s
WHERE p.name IN ('Professional', 'Enterprise')
  AND s.`key` = 'marketing'
ON DUPLICATE KEY UPDATE `plan_id` = VALUES(`plan_id`);
