-- ============================================================
-- Migration 053: Ordini ricarica crediti email dal reseller
--
-- Workflow:
--  1) Reseller crea una richiesta da /reseller/credits/create per
--     uno dei suoi clienti (Tenant.acquired_by_reseller_id = reseller.id)
--  2) Admin la vede in /admin/credit-requests, approva o rifiuta
--  3) Se approva: Tenant::addCredits() + email al reseller
--     Se rifiuta: email al reseller con motivo
--
-- status: pending / approved / rejected (VARCHAR, Lesson #14)
-- ============================================================

CREATE TABLE IF NOT EXISTS `credit_recharge_requests` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `reseller_id`         INT UNSIGNED NOT NULL,
    `credits_requested`   INT UNSIGNED NOT NULL,
    `notes_reseller`      TEXT NULL,
    `status`              VARCHAR(20) NOT NULL DEFAULT 'pending',
    `notes_admin`         TEXT NULL,
    `processed_by`        INT UNSIGNED NULL,
    `processed_at`        DATETIME NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status`           (`status`),
    INDEX `idx_reseller_status`  (`reseller_id`, `status`),
    INDEX `idx_tenant`           (`tenant_id`),
    CONSTRAINT `fk_credit_req_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_credit_req_reseller`
        FOREIGN KEY (`reseller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_credit_req_processed_by`
        FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
