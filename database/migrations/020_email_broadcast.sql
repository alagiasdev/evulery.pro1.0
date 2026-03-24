-- Migration 020: Email Broadcast Feature (FASE 15)
-- Comunicazioni email ai clienti con sistema crediti

-- Colonne unsubscribe su customers (GDPR)
ALTER TABLE `customers`
    ADD COLUMN `unsubscribed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_blocked`,
    ADD COLUMN `unsubscribed_at` DATETIME DEFAULT NULL AFTER `unsubscribed`;

-- Saldo crediti email su tenants
ALTER TABLE `tenants`
    ADD COLUMN `email_credits_balance` INT UNSIGNED NOT NULL DEFAULT 0;

-- Campagne email
CREATE TABLE IF NOT EXISTS `email_campaigns` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body_text` TEXT NOT NULL,
    `segment_filter` ENUM('all','nuovo','occasionale','abituale','vip','inactive') NOT NULL DEFAULT 'all',
    `inactive_days` INT UNSIGNED DEFAULT NULL COMMENT 'Per segmento inactive: giorni da ultima prenotazione',
    `total_recipients` INT UNSIGNED NOT NULL DEFAULT 0,
    `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `credits_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('draft','queued','sending','sent','failed') NOT NULL DEFAULT 'draft',
    `created_by` INT UNSIGNED DEFAULT NULL,
    `queued_at` DATETIME DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant_status` (`tenant_id`, `status`),
    INDEX `idx_status_queued` (`status`, `queued_at`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Destinatari singoli (tracking invio)
CREATE TABLE IF NOT EXISTS `email_campaign_recipients` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT UNSIGNED NOT NULL,
    `customer_id` INT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `sent_at` DATETIME DEFAULT NULL,
    INDEX `idx_campaign_status` (`campaign_id`, `status`),
    FOREIGN KEY (`campaign_id`) REFERENCES `email_campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Transazioni crediti (storico assegnazioni e utilizzi)
CREATE TABLE IF NOT EXISTS `email_credit_transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `amount` INT NOT NULL COMMENT 'Positivo = assegnazione, negativo = utilizzo',
    `type` ENUM('assignment','usage','refund') NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `campaign_id` INT UNSIGNED DEFAULT NULL,
    `assigned_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tenant` (`tenant_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`campaign_id`) REFERENCES `email_campaigns`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabella unsubscribe con token (per link pubblico GDPR)
CREATE TABLE IF NOT EXISTS `email_unsubscribes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `unsubscribed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_email` (`tenant_id`, `email`),
    UNIQUE KEY `uk_token` (`token`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Servizio email_broadcast nel catalogo
INSERT INTO `services` (`key`, `name`, `description`, `sort_order`, `is_active`)
VALUES ('email_broadcast', 'Email Marketing', 'Invio comunicazioni email ai clienti del ristorante', 12, 1);

-- Associare a piani Professional + Enterprise
INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id FROM `plans` p, `services` s
WHERE p.slug IN ('professional', 'enterprise') AND s.key = 'email_broadcast';
