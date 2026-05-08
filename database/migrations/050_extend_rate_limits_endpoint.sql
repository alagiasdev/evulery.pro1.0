-- ============================================================
-- Migration 050: rate_limits.endpoint VARCHAR(10) -> VARCHAR(50)
--
-- BUG FIX 2026-05-08: errore 1406 "Data too long for column 'endpoint'"
-- causato da DashboardRateLimitMiddleware che usa la chiave
-- 'DASHBOARD_POST' (14 char) sulla colonna VARCHAR(10).
--
-- Bug latente storicamente perche' XAMPP MySQL era lasco e troncava
-- silenziosamente. Dopo il commit c472b16 (2026-05-05) lo strict mode
-- e' SEMPRE attivo (Lesson #16) e ora il truncate diventa exception.
--
-- Stesso pattern della Lesson #15 in MEMORY.md: VARCHAR troppo corto
-- per chiavi di rate limiting causa bug silenziosi (in non-strict)
-- o crash (in strict).
--
-- Soluzione: estendere a VARCHAR(50). Costo storage trascurabile in
-- InnoDB (allocato per uso effettivo). Permette chiavi leggibili tipo
-- 'DASHBOARD_POST', 'customer_lookup', 'login_attempt' senza dover
-- inventare abbreviazioni fragili tipo 'cust_lkp'.
--
-- L'indice composito idx_ip_endpoint_time resta valido (MySQL gestisce
-- la modifica in trasparenza).
-- ============================================================

ALTER TABLE `rate_limits`
    MODIFY COLUMN `endpoint` VARCHAR(50) NOT NULL;
