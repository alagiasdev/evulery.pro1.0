-- ============================================================
-- Migration 057: Capacità tavolo min/max (tavoli elastici)
--
-- Aggiunge `min_capacity` alla tabella `restaurant_tables` per
-- supportare tavoli elastici (es. un quadrato per 4 può ospitare
-- anche 1, 2, 3 persone). La colonna `capacity` esistente
-- continua a rappresentare il massimo.
--
-- Backfill: per tutti i tavoli esistenti `min_capacity = capacity`
-- (comportamento identico a oggi, zero impatto sui tenant
-- preesistenti). Il ristoratore allarga la forbice quando vuole.
--
-- Regola dell'auto-assegnatore (post-migration):
--   tavolo valido per party P  ⇔  min_capacity ≤ P ≤ capacity
-- ============================================================

ALTER TABLE `restaurant_tables`
    ADD COLUMN `min_capacity` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `capacity`;

-- Backfill: min_capacity = 1 per i tavoli esistenti.
-- Riproduce ESATTAMENTE il comportamento pre-migration: il vecchio
-- auto-assegnatore controllava solo `capacity >= partySize` (no lower
-- bound) → in pratica ogni tavolo era già implicitamente "1 → cap".
-- Il ristoratore può alzare min_capacity quando vuole (es. tavolo da
-- 8 dove non accetta singoli).
UPDATE `restaurant_tables` SET `min_capacity` = 1;
