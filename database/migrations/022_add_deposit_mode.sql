-- Migration 022: Add deposit_mode to tenants (per_table or per_person)
ALTER TABLE tenants
    ADD COLUMN deposit_mode ENUM('per_table','per_person') NOT NULL DEFAULT 'per_table' AFTER deposit_amount;
