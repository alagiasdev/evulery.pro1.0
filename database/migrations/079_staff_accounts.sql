-- ============================================================
-- Migration 079: Accessi Staff (collaboratori del ristoratore)
--
-- Servizio gatato `staff_accounts` → assegnato a Enterprise.
-- Colonna `tenants.max_staff` = limite per-tenant (NULL = default di piano,
-- gestito in codice: Enterprise = 3, altri = 0).
--
-- Additivo e INERTE: nessun utente con role='staff' esiste finché l'owner
-- non lo crea, quindi zero impatto sui ristoratori già attivi.
-- ============================================================

-- Servizio nel catalogo
INSERT INTO `services` (`key`, `name`, `description`, `sort_order`, `is_active`)
VALUES ('staff_accounts', 'Accessi Staff', 'Account limitati per i collaboratori del ristorante', 16, 1);

-- Associa il servizio al solo piano Enterprise (aggiungere Professional = 1 riga)
INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id
FROM `plans` p, `services` s
WHERE s.`key` = 'staff_accounts' AND p.slug = 'enterprise';

-- Limite collaboratori per-tenant (override del default di piano)
ALTER TABLE `tenants`
    ADD COLUMN `max_staff` TINYINT UNSIGNED NULL DEFAULT NULL;
