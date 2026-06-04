-- ============================================================
-- Migration 058: Flag disponibilità tavolo (Fasi B + E)
--
-- Fase B — "Disponibile per prenotazioni online" (is_bookable_online):
--   Tavolo escluso dall'auto-assegnazione dell'algoritmo e dal banner
--   coperti del widget pubblico. Resta visibile in mappa sala e
--   assegnabile manualmente dal ristoratore. Caso d'uso: "tavolo jolly"
--   di scorta da accorpare ad altri quando serve.
--
-- Fase E — "Blocca tavolo" (is_blocked + block_reason):
--   Tavolo temporaneamente fuori uso (sedia rotta, riservato, lavori).
--   Escluso da TUTTO: auto-assegnazione, assegnazione manuale, capacità
--   widget. Il `block_reason` è opzionale, visibile come tooltip sul
--   tavolo bloccato.
--
-- Backfill:
--   is_bookable_online = 1  → tutti i tavoli esistenti restano online
--   is_blocked         = 0  → nessuno bloccato
--   block_reason       = NULL
--
-- Le due flag sono INDIPENDENTI: un tavolo può essere "solo manuale"
-- (is_bookable_online=0) E anche "bloccato" temporaneamente (is_blocked=1).
-- ============================================================

ALTER TABLE `restaurant_tables`
    ADD COLUMN `is_bookable_online` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`,
    ADD COLUMN `is_blocked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_bookable_online`,
    ADD COLUMN `block_reason` VARCHAR(255) DEFAULT NULL AFTER `is_blocked`;

-- Backfill esplicito (i DEFAULT della colonna lo coprono già per i record
-- esistenti, ma lasciamo l'UPDATE per chiarezza intent e idempotenza).
UPDATE `restaurant_tables`
   SET `is_bookable_online` = 1,
       `is_blocked`         = 0,
       `block_reason`       = NULL
 WHERE `is_bookable_online` IS NULL
    OR `is_blocked`         IS NULL;
