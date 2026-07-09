-- Migration 083: backfill dei contatori denormalizzati dei clienti, allineandoli
-- alle prenotazioni reali (correzione del drift dei vecchi +1/-1).
-- Stesse definizioni di Customer::recomputeStats:
--   total_bookings = prenotazioni confermate + arrivate
--   total_noshow   = prenotazioni no-show
-- Una-tantum su tutti i clienti; d'ora in poi i contatori si auto-ricalcolano
-- a ogni creazione/cambio stato/eliminazione di prenotazione.

UPDATE customers c SET
    c.total_bookings = (
        SELECT COUNT(*) FROM reservations r
        WHERE r.customer_id = c.id AND r.status IN ('confirmed','arrived')
    ),
    c.total_noshow = (
        SELECT COUNT(*) FROM reservations r
        WHERE r.customer_id = c.id AND r.status = 'noshow'
    );
