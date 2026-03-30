-- 035: Campo source su customers per tracciare origine (booking, ordering, import, manual)
ALTER TABLE customers
    ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'booking' AFTER unsubscribed_at;
