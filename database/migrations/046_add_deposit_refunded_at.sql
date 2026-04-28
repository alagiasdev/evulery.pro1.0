-- ============================================================
-- Migration 046: Aggiunge timestamp di rimborso caparra
--
-- Prima si tracciava solo il flag deposit_refunded (sì/no). I clienti
-- (ristoratori) hanno chiesto di sapere QUANDO è stato fatto il rimborso
-- (per registrazione contabile, audit interno, comunicazione cliente).
--
-- Il rimborso oggi è MANUALE (admin clicca "Segna come rimborsata"),
-- quindi il timestamp coincide col momento del click.
--
-- Le righe storiche con deposit_refunded=1 e deposit_refunded_at=NULL
-- restano leggibili: la UI mostrerà "rimborsata" senza data (onesto:
-- non sappiamo davvero quando sono state rimborsate).
-- ============================================================

ALTER TABLE `reservations`
    ADD COLUMN `deposit_refunded_at` DATETIME DEFAULT NULL
        AFTER `deposit_refunded`;