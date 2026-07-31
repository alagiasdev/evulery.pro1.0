-- ============================================================
-- Migration 084: numero d'ordine atomico per-tenant + UNIQUE
-- Elimina la race di Order::getNextOrderNumber (MAX+1 senza lock, colonna non
-- univoca) allineando gli ordini al pattern gia' usato per le prenotazioni
-- (tenant_booking_counters + LAST_INSERT_ID, vedi migration 040).
--
-- PREREQUISITO PROD: nessun duplicato (tenant_id, order_number). Verificare con:
--   SELECT tenant_id, order_number, COUNT(*) c FROM orders
--   GROUP BY tenant_id, order_number HAVING c > 1;
-- Se restituisce righe, NON applicare finche' non sono state de-duplicate:
-- l'ADD UNIQUE fallirebbe. (In locale: 0 duplicati verificati.)
-- ============================================================

-- Contatore per-tenant: ultimo numero d'ordine allocato.
CREATE TABLE IF NOT EXISTS `tenant_order_counters` (
    `tenant_id`   INT UNSIGNED NOT NULL PRIMARY KEY,
    `last_number` INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `fk_toc_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed col MAX numerico attuale per tenant (parte numerica dopo "ORD-").
-- I tenant senza ordini non ricevono riga: verra' creata al primo ordine
-- (INSERT IGNORE lato codice), esattamente come per le prenotazioni.
INSERT INTO `tenant_order_counters` (`tenant_id`, `last_number`)
SELECT tenant_id, MAX(CAST(SUBSTRING(order_number, 5) AS UNSIGNED))
FROM `orders`
GROUP BY tenant_id
ON DUPLICATE KEY UPDATE `last_number` = VALUES(`last_number`);

-- Vincolo di unicita': da qui in avanti un eventuale duplicato FALLISCE
-- (INSERT catturabile) invece di passare in silenzio.
ALTER TABLE `orders`
    ADD UNIQUE KEY `uq_tenant_order_number` (`tenant_id`, `order_number`);
