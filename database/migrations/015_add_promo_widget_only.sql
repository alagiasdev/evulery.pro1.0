-- Migration 015: Add promo_widget_only toggle to tenants
-- When enabled, promotions/discounts apply only to widget bookings (not dashboard/phone/walkin)

ALTER TABLE tenants ADD COLUMN promo_widget_only TINYINT(1) NOT NULL DEFAULT 0 AFTER menu_enabled;
