-- Migration 021: Add last_booking_at to customers for inactive segment filter
-- Used by BroadcastService to filter "inactive" customers (no visit in X days)

ALTER TABLE customers
    ADD COLUMN last_booking_at DATETIME DEFAULT NULL AFTER total_noshow;

-- Populate from existing reservation data
UPDATE customers c
SET c.last_booking_at = (
    SELECT MAX(r.reservation_date)
    FROM reservations r
    WHERE r.customer_id = c.id
      AND r.status IN ('confirmed', 'arrived')
);
