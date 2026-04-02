-- ============================================================
-- FASE 23: Reputation Management
-- ============================================================

-- 1A. Tabella review_requests
CREATE TABLE IF NOT EXISTS review_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT UNSIGNED NOT NULL,
    reservation_id  INT UNSIGNED DEFAULT NULL,
    customer_id     INT UNSIGNED DEFAULT NULL,
    token           VARCHAR(64) NOT NULL,
    source          ENUM('email','embed','qr','nfc') NOT NULL DEFAULT 'email',
    sent_at         DATETIME DEFAULT NULL,
    opened_at       DATETIME DEFAULT NULL,
    clicked_at      DATETIME DEFAULT NULL,
    rating          TINYINT UNSIGNED DEFAULT NULL,
    feedback_text   TEXT DEFAULT NULL,
    feedback_reply  TEXT DEFAULT NULL,
    feedback_status ENUM('new','read','replied') DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (token),
    INDEX idx_tenant_created (tenant_id, created_at),
    INDEX idx_tenant_feedback (tenant_id, feedback_status),
    INDEX idx_reservation (reservation_id),
    INDEX idx_customer_tenant (customer_id, tenant_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1B. Colonne su tenants per impostazioni recensioni
ALTER TABLE tenants
    ADD COLUMN review_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER delivery_board_pin,
    ADD COLUMN review_url VARCHAR(500) DEFAULT NULL AFTER review_enabled,
    ADD COLUMN review_platform_label VARCHAR(50) DEFAULT NULL AFTER review_url,
    ADD COLUMN review_delay_hours TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER review_platform_label,
    ADD COLUMN review_quiet_hour TINYINT UNSIGNED DEFAULT 22 AFTER review_delay_hours,
    ADD COLUMN review_filter_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER review_quiet_hour,
    ADD COLUMN review_filter_threshold TINYINT UNSIGNED NOT NULL DEFAULT 4 AFTER review_filter_enabled,
    ADD COLUMN review_filter_message VARCHAR(255) DEFAULT 'Ci dispiace! Dicci cosa possiamo migliorare' AFTER review_filter_threshold,
    ADD COLUMN review_email_subject VARCHAR(255) DEFAULT 'Come è andata da {ristorante}?' AFTER review_filter_message,
    ADD COLUMN review_email_body TEXT DEFAULT NULL AFTER review_email_subject,
    ADD COLUMN review_email_cta VARCHAR(100) DEFAULT 'Lascia una recensione' AFTER review_email_body;

-- 1C. Servizio review_management
INSERT INTO services (`key`, name, description, sort_order, is_active)
SELECT 'review_management', 'Gestione reputazione', 'Richieste recensioni automatiche post-visita e dashboard feedback', 14, 1
FROM dual WHERE NOT EXISTS (SELECT 1 FROM services WHERE `key` = 'review_management');

-- 1D. Associa a Professional + Enterprise
INSERT INTO plan_services (plan_id, service_id)
SELECT p.id, s.id FROM plans p, services s
WHERE s.`key` = 'review_management' AND p.slug IN ('professional', 'enterprise')
AND NOT EXISTS (SELECT 1 FROM plan_services ps WHERE ps.plan_id = p.id AND ps.service_id = s.id);
