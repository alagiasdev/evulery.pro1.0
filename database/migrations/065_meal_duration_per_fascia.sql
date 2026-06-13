-- ====================================================================
-- Durata servizio per fascia + override per giorno (livello 2)
--
-- Oggi la durata tavolo e' unica per tenant (tenants.table_duration,
-- impostazione base in Generali, disponibile per tutti). Qui aggiungiamo
-- il raffinamento: durata diversa per fascia (categoria pasto) e, se
-- voluto, durata alternativa per alcuni giorni della settimana
-- (es. cena 120' tutti i giorni ma sab/dom 90' per far girare i tavoli).
--
-- Gatato dal servizio 'advanced_turns' (Professional + Enterprise).
-- Il gating e' SOLO sulla UI di configurazione: il motore applica
-- sempre i dati salvati (grandfathering al downgrade).
--
-- Retrocompatibile: tutti i campi nullable. Tenant che non tocca nulla
-- -> i campi restano NULL -> si usa table_duration globale, come oggi.
-- ====================================================================

-- 1) Durata per fascia sulle categorie pasto.
--    duration_minutes      = durata base della fascia (tutti i giorni)
--    duration_minutes_alt  = durata alternativa (opzionale)
--    duration_alt_days     = JSON dei giorni in cui vale l'alternativa,
--                            ISO 1=Lun ... 7=Dom (es. [6,7] = sab+dom)
ALTER TABLE meal_categories
    ADD COLUMN duration_minutes     SMALLINT UNSIGNED NULL DEFAULT NULL AFTER end_time,
    ADD COLUMN duration_minutes_alt SMALLINT UNSIGNED NULL DEFAULT NULL AFTER duration_minutes,
    ADD COLUMN duration_alt_days    VARCHAR(20)       NULL DEFAULT NULL AFTER duration_minutes_alt;

-- 2) Snapshot della durata sulla singola prenotazione.
--    Congelato alla creazione: le prenotazioni gia' prese non cambiano
--    durata se il ristoratore modifica le fasce in seguito.
--    NULL per le prenotazioni storiche -> fallback a table_duration nei calcoli.
ALTER TABLE reservations
    ADD COLUMN duration_minutes SMALLINT UNSIGNED NULL DEFAULT NULL AFTER party_size;

-- 3) Nuovo servizio gatato.
INSERT INTO services (`key`, name, description, sort_order, is_active)
VALUES (
    'advanced_turns',
    'Durata turni avanzata',
    'Durata tavolo diversa per fascia (pranzo/aperitivo/cena) e per giorno della settimana. Ottimizza il turnover dei tavoli.',
    101,
    1
);

-- 4) Assegnazione a Professional (id 2) ed Enterprise (id 3). Starter escluso.
INSERT INTO plan_services (plan_id, service_id)
SELECT 2, id FROM services WHERE `key` = 'advanced_turns'
UNION ALL
SELECT 3, id FROM services WHERE `key` = 'advanced_turns';
