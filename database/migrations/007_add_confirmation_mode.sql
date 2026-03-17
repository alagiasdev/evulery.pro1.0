-- Add confirmation_mode to tenants
-- 'auto' = widget bookings are immediately confirmed (current behavior)
-- 'manual' = widget bookings go to pending, owner must confirm
ALTER TABLE tenants ADD COLUMN confirmation_mode ENUM('auto','manual') NOT NULL DEFAULT 'auto' AFTER cancellation_policy;