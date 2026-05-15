-- ============================================================
-- Migration 055: Caparra "Carta a garanzia" (modello impronta carta)
--
-- Quarto tipo di caparra: il cliente salva la carta (Stripe SetupIntent)
-- senza alcun addebito. Il ristoratore può addebitare una penale solo
-- in caso di mancata presentazione (no-show).
--
-- Retrocompatibilità: i tipi esistenti (info/link/stripe) non cambiano.
-- Le prenotazioni esistenti hanno guarantee_status = 'none'.
-- ============================================================

-- deposit_type: da ENUM a VARCHAR per poter aggiungere 'guarantee'
-- senza il rischio "Data truncated" (lezione #14: stati che evolvono → VARCHAR).
ALTER TABLE `tenants`
    MODIFY COLUMN `deposit_type` VARCHAR(20) NOT NULL DEFAULT 'info';

-- Stato della garanzia su carta per la singola prenotazione:
--   none     = nessuna garanzia (prenotazione senza carta a garanzia)
--   pending  = in attesa che il cliente salvi la carta
--   secured  = carta salvata, garanzia attiva
--   charged  = penale addebitata sulla carta salvata
--   waived   = garanzia chiusa senza addebito (cliente presentato / scelta ristoratore)
ALTER TABLE `reservations`
    ADD COLUMN `guarantee_status` VARCHAR(20) NOT NULL DEFAULT 'none' AFTER `stripe_payment_id`,
    ADD COLUMN `stripe_customer_id` VARCHAR(255) DEFAULT NULL AFTER `guarantee_status`,
    ADD COLUMN `stripe_payment_method_id` VARCHAR(255) DEFAULT NULL AFTER `stripe_customer_id`,
    ADD COLUMN `stripe_setup_intent_id` VARCHAR(255) DEFAULT NULL AFTER `stripe_payment_method_id`,
    ADD COLUMN `guarantee_charged_at` DATETIME DEFAULT NULL AFTER `stripe_setup_intent_id`,
    ADD COLUMN `guarantee_charged_amount` DECIMAL(8,2) DEFAULT NULL AFTER `guarantee_charged_at`;
