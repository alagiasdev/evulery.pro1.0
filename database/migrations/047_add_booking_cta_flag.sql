-- ============================================================
-- Migration 047: Flag "includi pulsante Prenota ora" nelle email broadcast
--
-- Richiesta clienti: per campagne tipo eventi/feste/promo, vorrebbero
-- aggiungere un pulsante CTA "Prenota ora" nel corpo dell'email che
-- punti al loro booking widget. Per altre campagne (es. "siamo chiusi
-- a Pasqua") il pulsante non serve.
--
-- Soluzione: flag per singola campagna, decide il ristoratore al
-- momento di scrivere l'email (NON globale settings).
-- ============================================================

ALTER TABLE `email_campaigns`
    ADD COLUMN `include_booking_cta` TINYINT(1) NOT NULL DEFAULT 0
        AFTER `body_text`;
