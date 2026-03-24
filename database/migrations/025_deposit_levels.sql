-- Migration 025: Add deposit levels (info/link/stripe) fields to tenants
ALTER TABLE tenants
    ADD COLUMN deposit_type ENUM('info','link','stripe') NOT NULL DEFAULT 'info' AFTER deposit_mode,
    ADD COLUMN deposit_bank_info TEXT DEFAULT NULL AFTER deposit_type,
    ADD COLUMN deposit_payment_link VARCHAR(500) DEFAULT NULL AFTER deposit_bank_info,
    ADD COLUMN stripe_sk VARBINARY(512) DEFAULT NULL AFTER deposit_payment_link,
    ADD COLUMN stripe_pk VARCHAR(255) DEFAULT NULL AFTER stripe_sk,
    ADD COLUMN stripe_wh_secret VARBINARY(512) DEFAULT NULL AFTER stripe_pk;
