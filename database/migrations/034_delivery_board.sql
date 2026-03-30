-- 034: Delivery Board per fattorini
-- Token univoco + PIN per accesso board consegne

ALTER TABLE tenants
    ADD COLUMN delivery_board_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER delivery_description,
    ADD COLUMN delivery_board_token VARCHAR(20) DEFAULT NULL AFTER delivery_board_enabled,
    ADD COLUMN delivery_board_pin VARCHAR(10) DEFAULT NULL AFTER delivery_board_token;

CREATE UNIQUE INDEX idx_delivery_board_token ON tenants(delivery_board_token);
