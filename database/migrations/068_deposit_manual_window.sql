-- Migration 068: finestra di scadenza per la caparra richiesta manualmente (gruppi)
-- tenants.deposit_manual_window_minutes: minuti entro cui il cliente deve
-- completare la caparra (Stripe / carta a garanzia) richiesta a mano dal
-- ristoratore. NULL = nessuna scadenza (gestione manuale). Default 120 (2 ore).
-- NON si applica al widget pubblico (30 min fissi) ne' a bonifico/link
-- (conferma manuale del ristoratore -> nessun auto-annullamento).
-- reservations.deposit_requested_at: istante in cui la caparra e' stata
-- richiesta manualmente; riferimento per il cron di scadenza.
ALTER TABLE tenants
    ADD COLUMN deposit_manual_window_minutes SMALLINT UNSIGNED DEFAULT 120 AFTER deposit_min_party_size;
ALTER TABLE reservations
    ADD COLUMN deposit_requested_at DATETIME DEFAULT NULL AFTER deposit_manual_request;
