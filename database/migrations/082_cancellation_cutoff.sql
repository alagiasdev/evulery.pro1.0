-- Migration 082: limite orario per la cancellazione autonoma del cliente.
-- Ore di anticipo minime rispetto all'orario della prenotazione entro cui il
-- cliente puo' annullare da solo tramite il magic link ricevuto via email.
-- 0 = nessun limite (default, comportamento pre-esistente).
-- Valori previsti dalla UI: 0, 2, 4, 6, 12, 24.

ALTER TABLE `tenants`
    ADD COLUMN `cancellation_cutoff_hours` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `cancellation_policy`;