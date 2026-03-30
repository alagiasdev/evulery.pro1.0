-- ============================================================
-- FASE 22: Online Ordering System (Takeaway + Delivery)
-- ============================================================

-- 1A. Tabella ordini
CREATE TABLE IF NOT EXISTS orders (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         INT UNSIGNED NOT NULL,
    customer_id       INT UNSIGNED DEFAULT NULL,
    order_number      VARCHAR(20) NOT NULL,
    order_type        ENUM('takeaway','delivery') NOT NULL DEFAULT 'takeaway',
    status            ENUM('pending','accepted','preparing','ready','completed','cancelled','rejected') NOT NULL DEFAULT 'pending',
    pickup_time       DATETIME DEFAULT NULL,
    subtotal          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    promo_id          INT UNSIGNED DEFAULT NULL,
    total             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method    ENUM('cash','stripe') NOT NULL DEFAULT 'cash',
    payment_status    ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    stripe_session_id VARCHAR(255) DEFAULT NULL,
    customer_name     VARCHAR(100) NOT NULL,
    customer_phone    VARCHAR(30) NOT NULL,
    customer_email    VARCHAR(255) DEFAULT NULL,
    delivery_address  VARCHAR(500) DEFAULT NULL,
    delivery_cap      VARCHAR(10) DEFAULT NULL,
    delivery_notes    VARCHAR(500) DEFAULT NULL,
    delivery_fee      DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    notes             TEXT DEFAULT NULL,
    rejected_reason   VARCHAR(500) DEFAULT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_date (tenant_id, created_at),
    INDEX idx_order_number (tenant_id, order_number),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1B. Tabella righe ordine (snapshot prezzi)
CREATE TABLE IF NOT EXISTS order_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED NOT NULL,
    menu_item_id  INT UNSIGNED DEFAULT NULL,
    item_name     VARCHAR(150) NOT NULL,
    quantity      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price    DECIMAL(8,2) NOT NULL,
    notes         VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1C. Campi aggiuntivi menu_items (is_orderable esiste già da migration 012)
ALTER TABLE menu_items
    ADD COLUMN prep_minutes SMALLINT UNSIGNED DEFAULT NULL AFTER is_orderable,
    ADD COLUMN max_daily_qty INT UNSIGNED DEFAULT NULL AFTER prep_minutes;

-- 1D. Zone di consegna (delivery)
CREATE TABLE IF NOT EXISTS delivery_zones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    postal_codes JSON NOT NULL COMMENT '["20121","20122","20123"]',
    fee         DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    min_amount  DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1E. Impostazioni ordering su tenants
ALTER TABLE tenants
    ADD COLUMN ordering_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER menu_enabled,
    ADD COLUMN ordering_mode ENUM('takeaway','delivery','both') NOT NULL DEFAULT 'takeaway' AFTER ordering_enabled,
    ADD COLUMN ordering_prep_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER ordering_mode,
    ADD COLUMN ordering_min_amount DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER ordering_prep_minutes,
    ADD COLUMN ordering_max_per_slot SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER ordering_min_amount,
    ADD COLUMN ordering_hours JSON DEFAULT NULL AFTER ordering_max_per_slot,
    ADD COLUMN ordering_payment_methods VARCHAR(50) NOT NULL DEFAULT 'cash' AFTER ordering_hours,
    ADD COLUMN ordering_pickup_interval SMALLINT UNSIGNED NOT NULL DEFAULT 15 AFTER ordering_payment_methods,
    ADD COLUMN ordering_auto_accept TINYINT(1) NOT NULL DEFAULT 0 AFTER ordering_pickup_interval,
    ADD COLUMN delivery_mode ENUM('simple','zones') NOT NULL DEFAULT 'simple' AFTER ordering_auto_accept,
    ADD COLUMN delivery_fee DECIMAL(6,2) NOT NULL DEFAULT 0.00 AFTER delivery_mode,
    ADD COLUMN delivery_min_amount DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER delivery_fee,
    ADD COLUMN delivery_description VARCHAR(500) DEFAULT NULL AFTER delivery_min_amount;

-- 1F. Servizio online_ordering
INSERT INTO services (`key`, name, description, sort_order, is_active)
SELECT 'online_ordering', 'Ordini online', 'Asporto e delivery con gestione ordini e pagamento', 13, 1
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM services WHERE `key` = 'online_ordering');

-- 1G. Associa a Professional + Enterprise
INSERT INTO plan_services (plan_id, service_id)
SELECT p.id, s.id
FROM plans p, services s
WHERE s.`key` = 'online_ordering'
  AND p.slug IN ('professional', 'enterprise')
  AND NOT EXISTS (
    SELECT 1 FROM plan_services ps WHERE ps.plan_id = p.id AND ps.service_id = s.id
  );
