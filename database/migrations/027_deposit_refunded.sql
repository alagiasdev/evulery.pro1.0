ALTER TABLE reservations
    ADD COLUMN deposit_refunded TINYINT(1) NOT NULL DEFAULT 0 AFTER deposit_paid;
