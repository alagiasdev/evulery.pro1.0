-- Migration 002: Add 'altro' to reservations.source ENUM
-- Required for FASE 16: Dashboard Prenotazione Rapida

ALTER TABLE `reservations`
    MODIFY COLUMN `source` ENUM('widget','dashboard','phone','walkin','altro') NOT NULL DEFAULT 'widget';
