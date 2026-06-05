-- ====================================================================
-- Notifiche audio (Fase notifiche sonore brandizzate)
--
-- Aggiunge a `tenants` i campi per il controllo del suono per ogni evento.
-- Strategia: 1 master toggle + 1 volume globale + 1 toggle per evento.
-- Default tutti a ON: per chi non apre i settings, l'audio funziona subito
-- al primo evento e si scopre la feature naturalmente.
-- ====================================================================

ALTER TABLE tenants
    ADD COLUMN notification_sound_enabled       TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_cancellation,
    ADD COLUMN notification_sound_volume        TINYINT UNSIGNED NOT NULL DEFAULT 70 AFTER notification_sound_enabled,
    ADD COLUMN sound_on_new_reservation         TINYINT(1) NOT NULL DEFAULT 1 AFTER notification_sound_volume,
    ADD COLUMN sound_on_cancellation            TINYINT(1) NOT NULL DEFAULT 1 AFTER sound_on_new_reservation,
    ADD COLUMN sound_on_deposit_received        TINYINT(1) NOT NULL DEFAULT 1 AFTER sound_on_cancellation,
    ADD COLUMN sound_on_new_order               TINYINT(1) NOT NULL DEFAULT 1 AFTER sound_on_deposit_received,
    ADD COLUMN sound_on_new_feedback            TINYINT(1) NOT NULL DEFAULT 1 AFTER sound_on_new_order;
