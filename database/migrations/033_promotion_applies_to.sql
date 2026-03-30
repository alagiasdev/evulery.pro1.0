-- Migration 033: Add applies_to field to promotions
-- Allows scoping promotions to reservations only, orders only, or both

ALTER TABLE promotions
    ADD COLUMN applies_to ENUM('all','reservations','orders') NOT NULL DEFAULT 'all' AFTER is_active;
