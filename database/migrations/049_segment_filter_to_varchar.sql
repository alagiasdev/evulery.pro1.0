-- ============================================================
-- Migration 049: email_campaigns.segment_filter ENUM -> VARCHAR(20)
--
-- BUG FIX produzione 2026-05-05: l'invio broadcast con segmento
-- 'birthday_month' (aggiunto nel codice il 2026-04-30 commit 76dcaaf)
-- causava 500 in produzione perche':
--   - MySQL produzione e' in STRICT_TRANS_TABLES
--   - PDO::ERRMODE_EXCEPTION trasforma il warning "Data truncated"
--     in exception
--   - Il valore 'birthday_month' non era nell'ENUM esistente
--
-- E' lo stesso pattern della Lesson #14 in MEMORY.md (state machine
-- che evolve nel codice piu' velocemente del DB schema).
--
-- Soluzione: convertire a VARCHAR(20) per essere future-proof.
-- Aggiungere nuovi segmenti in futuro = solo cambio nel codice,
-- nessuna migration DB.
-- ============================================================

ALTER TABLE `email_campaigns`
    MODIFY COLUMN `segment_filter` VARCHAR(20) NOT NULL DEFAULT 'all';
