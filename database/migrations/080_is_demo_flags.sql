-- Migration 080: flag is_demo su tenants e customers per i dati demo/vetrina.
--
-- Sostituisce l'allowlist per-slug del DemoSeeder con un flag a livello DB,
-- robusto a riuso-slug / errore umano / refactor.
--
-- REGOLA D'ORO (sicurezza): la pulizia demo cancella SOLO customers con
-- is_demo=1. Un cliente reale ha is_demo=0 (default) -> NON puo' mai essere
-- cancellato dal seeder, nemmeno se un tenant viene marcato demo per errore.
--
-- Entrambe le colonne DEFAULT 0 -> zero impatto sui tenant/clienti esistenti.

ALTER TABLE `tenants`
    ADD COLUMN `is_demo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

ALTER TABLE `customers`
    ADD COLUMN `is_demo` TINYINT(1) NOT NULL DEFAULT 0;

-- Marca i tenant vetrina esistenti.
UPDATE `tenants` SET `is_demo` = 1 WHERE `slug` IN ('trattoria-genovese', 'trattoria-da-mario');

-- Migra i clienti demo gia' seminati (marker email storico @demo.evulery.local)
-- al nuovo flag, cosi' la prima pulizia post-upgrade li riconosce.
UPDATE `customers` SET `is_demo` = 1 WHERE `email` LIKE '%@demo.evulery.local';
