-- ====================================================================
-- Soft delete per riders
--
-- Il ristoratore puo' "archiviare" un rider (toggle is_active=0).
-- Ora aggiungiamo un terzo stato: "eliminato definitivamente",
-- gestito via deleted_at NULL/NOT NULL.
--
-- Soft delete e non hard delete per preservare il riferimento negli
-- ordini storici: con FK ON DELETE SET NULL avremmo perso il nome
-- del rider sugli ordini consegnati mesi prima. Lo storico ordini
-- continua a fare JOIN su riders.id e a mostrare il nome del rider.
--
-- Tabella riders e' piccola per natura (5-10 righe per ristorante
-- lifetime), quindi nessun rischio di crescita patologica.
-- ====================================================================

ALTER TABLE riders
    ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
    ADD INDEX idx_riders_deleted (deleted_at);
