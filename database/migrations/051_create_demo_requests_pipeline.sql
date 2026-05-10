-- ============================================================
-- Migration 051: Pipeline lead demo (Mini CRM admin)
--
-- Scopo: persistere ogni richiesta demo dal sito evulery.it,
-- gestire pipeline con stati italiani, assegnazione manuale
-- a reseller (preparato per fase futura), conversione lead→tenant,
-- audit log delle attività.
--
-- Decisioni di design:
-- - status: italiano hard-coded nelle view, chiavi tecniche EN nel DB
-- - assigned_reseller_id: NULL ora (no reseller esistenti),
--   preparato per fase 2 quando ci saranno
-- - activities.type: VARCHAR(30) non ENUM (Lesson #14 in MEMORY:
--   state machines che evolvono fanno male in ENUM)
-- ============================================================

-- Tabella principale richieste demo
CREATE TABLE IF NOT EXISTS `demo_requests` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Dati lead (dal form pubblico)
    `name`                  VARCHAR(100) NOT NULL,
    `restaurant`            VARCHAR(150) NOT NULL,
    `email`                 VARCHAR(150) NOT NULL,
    `phone`                 VARCHAR(30) NOT NULL,
    `message`               TEXT NULL,
    `ip_address`            VARCHAR(45) NULL,
    `referrer`              VARCHAR(500) NULL,
    `utm_source`            VARCHAR(100) NULL,

    -- Pipeline status (chiavi EN, label italiana in view)
    -- new, contacted, demo_scheduled, demo_done, negotiating, customer, lost
    `status`                VARCHAR(30) NOT NULL DEFAULT 'new',
    `status_changed_at`     DATETIME NULL,

    -- Assignment reseller (preparato per fase 2)
    `assigned_reseller_id`  INT UNSIGNED NULL,
    `assigned_at`           DATETIME NULL,
    `assigned_by`           INT UNSIGNED NULL,

    -- Conversion tracking
    `converted_tenant_id`   INT UNSIGNED NULL,
    `converted_at`          DATETIME NULL,

    -- Note + follow-up
    `notes`                 TEXT NULL,
    `last_contact_at`       DATETIME NULL,
    `next_followup_at`      DATETIME NULL,

    -- Audit
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_assigned` (`assigned_reseller_id`),
    KEY `idx_email` (`email`),
    KEY `idx_followup` (`next_followup_at`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabella attività log (timeline storica)
CREATE TABLE IF NOT EXISTS `demo_request_activities` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `demo_request_id`   INT UNSIGNED NOT NULL,

    -- Tipo attività (chiavi EN, label italiana in view)
    -- created, status_changed, assigned, reassigned, note_added,
    -- email_sent, material_sent, contacted, demo_done, converted
    `type`              VARCHAR(30) NOT NULL,
    `description`       VARCHAR(500) NULL,
    `performed_by`      INT UNSIGNED NULL,  -- user id (admin o reseller)
    `metadata`          JSON NULL,           -- es. {"old_status":"new","new_status":"contacted"}
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_lead_time` (`demo_request_id`, `created_at`),
    CONSTRAINT `fk_activity_demo_request`
        FOREIGN KEY (`demo_request_id`) REFERENCES `demo_requests`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
