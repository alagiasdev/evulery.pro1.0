-- ============================================================
-- Migration 044: Promozioni — campo descrizione
--
-- Aggiunge un campo opzionale `description` per descrivere la
-- promozione (es. "Sconto sul totale escluso vini pregiati") che
-- viene mostrato nella pagina pubblica /{slug}/promo.
-- ============================================================

ALTER TABLE `promotions`
    ADD COLUMN `description` VARCHAR(280) DEFAULT NULL
        AFTER `name`;
