-- Migration 030: Pet Friendly & Kids Friendly badges
ALTER TABLE tenants
    ADD COLUMN pet_friendly TINYINT(1) NOT NULL DEFAULT 0 AFTER promo_widget_only,
    ADD COLUMN kids_friendly TINYINT(1) NOT NULL DEFAULT 0 AFTER pet_friendly;
