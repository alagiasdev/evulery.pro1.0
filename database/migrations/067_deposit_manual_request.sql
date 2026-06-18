-- Migration 067: caparra richiesta manualmente dal ristoratore in accettazione
-- Flag che distingue una caparra attivata A POSTERIORI (su una prenotazione
-- pending gia' accettata, sotto la soglia automatica) da quella del widget.
-- Il webhook checkout.session.expired NON deve auto-cancellare queste
-- prenotazioni: il ristoratore le ha gia' accettate, se il cliente tarda a
-- pagare la caparra decide lui come gestirle.
ALTER TABLE reservations
    ADD COLUMN deposit_manual_request TINYINT(1) NOT NULL DEFAULT 0 AFTER deposit_required;
