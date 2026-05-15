-- ============================================================
-- Migration 054: Caparra condizionale per fascia oraria e giorni
--
-- Permette al ristoratore di applicare la caparra solo in:
--  - determinate fasce orarie (es. solo Cena) → flag per meal_category
--  - determinati giorni della settimana (es. solo weekend) → set su tenants
--
-- Retrocompatibilità: i default replicano il comportamento attuale
-- (caparra sempre attiva su tutte le categorie e tutti i giorni), quindi
-- i ristoranti con caparra già abilitata non cambiano comportamento.
-- ============================================================

-- Flag per-categoria: la caparra si applica alle prenotazioni di questa fascia?
ALTER TABLE `meal_categories`
    ADD COLUMN `deposit_required` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`;

-- Giorni della settimana in cui la caparra è attiva (ISO-8601: 1=lun ... 7=dom).
-- CSV. Default = tutti i 7 giorni.
ALTER TABLE `tenants`
    ADD COLUMN `deposit_days` VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7' AFTER `deposit_min_party_size`;
