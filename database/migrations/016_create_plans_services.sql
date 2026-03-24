-- Migration 016: Plans & Services dynamic management
-- Replaces ENUM-based plan system with dynamic plans + service catalog

-- -----------------------------------------------------------
-- 1. SERVICES CATALOG
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`           VARCHAR(50) NOT NULL UNIQUE,
    `name`          VARCHAR(100) NOT NULL,
    `description`   VARCHAR(500) DEFAULT NULL,
    `sort_order`    INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default services
INSERT INTO `services` (`key`, `name`, `description`, `sort_order`) VALUES
    ('booking_widget',      'Widget prenotazione',      'Pagina pubblica di prenotazione con calendario e slot', 1),
    ('dashboard',           'Dashboard',                'Pannello gestione ristorante', 2),
    ('email_confirmation',  'Email conferma',           'Email automatica di conferma prenotazione al cliente', 3),
    ('digital_menu',        'Menù digitale',            'Pagina menù pubblica + QR code', 4),
    ('promotions',          'Promozioni',               'Sconti su fasce orarie', 5),
    ('email_reminder',      'Email reminder',           'Promemoria automatico il giorno prima della prenotazione', 6),
    ('export_csv',          'Export CSV',               'Esportazione prenotazioni in formato CSV', 7),
    ('statistics',          'Statistiche',              'Report e analytics avanzati', 8),
    ('priority_support',    'Supporto prioritario',     'Risposta entro 4 ore', 9),
    ('multi_location',      'Multi-sede',               'Gestione di più sedi sotto lo stesso account', 10),
    ('api_access',          'API',                      'Accesso API REST', 11),
    ('custom_domain',       'Dominio personalizzato',   'Il ristorante usa il suo dominio (prenotazioni.mioristorante.it)', 12);

-- -----------------------------------------------------------
-- 2. PLANS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plans` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL,
    `slug`          VARCHAR(100) NOT NULL UNIQUE,
    `description`   VARCHAR(500) DEFAULT NULL,
    `price`         DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `color`         VARCHAR(7) NOT NULL DEFAULT '#1565C0',
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `is_default`    TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order`    INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default plans
INSERT INTO `plans` (`name`, `slug`, `description`, `price`, `color`, `is_default`, `sort_order`) VALUES
    ('Starter',       'starter',       'Piano base con prenotazioni illimitate e funzionalità essenziali', 29.00, '#1565C0', 1, 1),
    ('Professional',  'professional',  'Per ristoranti che vogliono il massimo: reminder, statistiche e supporto prioritario', 59.00, '#7B1FA2', 0, 2),
    ('Enterprise',    'enterprise',    'Soluzione completa per gruppi e catene di ristoranti', 99.00, '#E65100', 0, 3);

-- -----------------------------------------------------------
-- 3. PLAN_SERVICES (pivot)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plan_services` (
    `plan_id`       INT UNSIGNED NOT NULL,
    `service_id`    INT UNSIGNED NOT NULL,
    PRIMARY KEY (`plan_id`, `service_id`),
    FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed plan-service associations
-- Starter: booking_widget, dashboard, email_confirmation, digital_menu, promotions
INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id FROM plans p, services s
WHERE p.slug = 'starter' AND s.`key` IN ('booking_widget','dashboard','email_confirmation','digital_menu','promotions');

-- Professional: all of Starter + email_reminder, export_csv, statistics, priority_support
INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id FROM plans p, services s
WHERE p.slug = 'professional' AND s.`key` IN ('booking_widget','dashboard','email_confirmation','digital_menu','promotions','email_reminder','export_csv','statistics','priority_support');

-- Enterprise: all services
INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id FROM plans p, services s
WHERE p.slug = 'enterprise';

-- -----------------------------------------------------------
-- 4. ALTER SUBSCRIPTIONS — add plan_id, credits
-- -----------------------------------------------------------
ALTER TABLE `subscriptions`
    ADD COLUMN `plan_id`        INT UNSIGNED DEFAULT NULL AFTER `plan`,
    ADD COLUMN `email_credits`  INT NOT NULL DEFAULT 0 AFTER `price`,
    ADD COLUMN `sms_credits`    INT NOT NULL DEFAULT 0 AFTER `email_credits`,
    ADD INDEX `idx_plan_id` (`plan_id`);

-- Migrate existing ENUM values to plan_id
UPDATE `subscriptions` s
    JOIN `plans` p ON p.slug = CASE s.plan WHEN 'base' THEN 'starter' WHEN 'deposit' THEN 'professional' WHEN 'custom' THEN 'enterprise' END
SET s.plan_id = p.id;

-- -----------------------------------------------------------
-- 5. ALTER TENANTS — add plan_id reference
-- -----------------------------------------------------------
ALTER TABLE `tenants`
    ADD COLUMN `plan_id` INT UNSIGNED DEFAULT NULL AFTER `plan_price`;

-- Migrate existing tenants to plan_id (base → starter)
UPDATE `tenants` t
    JOIN `plans` p ON p.slug = CASE t.plan WHEN 'base' THEN 'starter' WHEN 'deposit' THEN 'professional' WHEN 'custom' THEN 'enterprise' END
SET t.plan_id = p.id;
