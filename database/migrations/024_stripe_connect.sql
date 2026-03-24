-- Migration 024: Add Stripe Connect fields to tenants
ALTER TABLE tenants
    ADD COLUMN stripe_connect_status ENUM('none','pending','active','revoked') NOT NULL DEFAULT 'none' AFTER stripe_account_id,
    ADD COLUMN stripe_connect_at DATETIME DEFAULT NULL AFTER stripe_connect_status;
