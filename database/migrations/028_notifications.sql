-- 028: Sistema notifiche 3 livelli (email + campanella + push)

-- Toggle email notifica al ristoratore (disponibile per tutti i piani)
ALTER TABLE tenants
    ADD COLUMN notify_new_reservation TINYINT(1) NOT NULL DEFAULT 1 AFTER promo_widget_only,
    ADD COLUMN notify_cancellation TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_new_reservation;

-- Tabella notifiche (campanella dashboard)
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT DEFAULT NULL,
    data JSON DEFAULT NULL,
    read_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_read (tenant_id, read_at),
    INDEX idx_tenant_created (tenant_id, created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabella subscriptions push (per device)
CREATE TABLE push_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_endpoint (endpoint(191)),
    INDEX idx_tenant_user (tenant_id, user_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggiungere servizio push_notifications
INSERT INTO services (`key`, `name`, `description`, `sort_order`) VALUES
('push_notifications', 'Notifiche in tempo reale', 'Campanella dashboard e notifiche push nel browser', 20);

-- Associare ai piani Professional e Enterprise
INSERT INTO plan_services (plan_id, service_id)
SELECT p.id, s.id FROM plans p, services s
WHERE p.slug IN ('professional', 'enterprise') AND s.`key` = 'push_notifications';
