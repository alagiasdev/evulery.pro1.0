-- Migration 023: Add booking_instructions to tenants (shown on confirmation page + email)
ALTER TABLE tenants
    ADD COLUMN booking_instructions TEXT DEFAULT NULL AFTER cancellation_policy;
