-- ====================================================================
-- reservations.cancelled_by: traccia CHI ha annullato la prenotazione
--
-- Oggi (pre-migration) sappiamo solo CHE una reservation e' cancelled
-- (status='cancelled' + cancelled_at) ma NON CHI l'ha cancellata.
-- L'informazione e' tracciata solo come testo libero in reservation_logs.
--
-- Aggiungiamo un enum esplicito per:
-- - mostrare label in dashboard ("Annullata dal cliente" vs "...ristoratore")
-- - analytics future (% cancellazioni cliente vs ristoratore vs sistema)
-- - filtri dashboard ("mostra solo cancellazioni cliente di questa settimana")
--
-- Valori:
-- - customer : cliente via magic link, API, widget pubblico
-- - staff    : ristoratore in dashboard
-- - system   : webhook Stripe (pagamento scaduto, carta non registrata, ecc.)
-- - NULL     : reservation non cancelled (status != 'cancelled')
--
-- Il campo cancellation_reason VARCHAR(500) esiste gia' nella tabella,
-- non viene toccato qui. Riusato per il futuro "modale ibrido cancellazione"
-- (vedi TODO.md "Modale ibrido cancellazione/rifiuto prenotazioni").
-- ====================================================================

ALTER TABLE reservations
    ADD COLUMN cancelled_by ENUM('customer', 'staff', 'system') NULL DEFAULT NULL
        AFTER cancelled_at;
