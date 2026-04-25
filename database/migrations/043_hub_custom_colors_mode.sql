-- ============================================================
-- Migration 043: Vetrina Digitale — modalità colori esplicita
--
-- Aggiunge:
--   custom_colors_enabled — flag tutto-o-niente (TINYINT)
--     0 = usa palette preset (custom_* ignorati)
--     1 = usa colori custom (preset ignorato)
--   custom_dark — 4° colore custom per gradiente cover/CTA
--
-- Fixa:
--   - Bug "una volta che imposto custom non torno più al preset"
--   - Bug "il gradiente miscela primary custom con dark del preset"
-- ============================================================

ALTER TABLE `tenant_hub_settings`
    ADD COLUMN `custom_colors_enabled` TINYINT(1) NOT NULL DEFAULT 0
        AFTER `custom_bg`,
    ADD COLUMN `custom_dark` VARCHAR(7) DEFAULT NULL
        AFTER `custom_bg`;
