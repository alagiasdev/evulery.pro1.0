-- ============================================================
-- Evulery.Pro 1.0 - Database Schema
-- Database: evulery_pro
-- Collation: utf8mb4_general_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- 1. TENANTS (Ristoranti)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tenants` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug`                  VARCHAR(100) NOT NULL UNIQUE,
    `name`                  VARCHAR(255) NOT NULL,
    `email`                 VARCHAR(255) NOT NULL,
    `phone`                 VARCHAR(30) DEFAULT NULL,
    `address`               VARCHAR(500) DEFAULT NULL,
    `logo_url`              VARCHAR(500) DEFAULT NULL,
    `custom_domain`         VARCHAR(255) DEFAULT NULL UNIQUE,
    `domain_status`         ENUM('none','dns_pending','linked') NOT NULL DEFAULT 'none',
    `cname_target`          VARCHAR(255) DEFAULT NULL,
    `plan`                  ENUM('base','deposit','custom') NOT NULL DEFAULT 'base',
    `plan_price`            DECIMAL(8,2) NOT NULL DEFAULT 49.00,
    `stripe_account_id`     VARCHAR(255) DEFAULT NULL,
    `deposit_enabled`       TINYINT(1) NOT NULL DEFAULT 0,
    `deposit_amount`        DECIMAL(8,2) DEFAULT NULL,
    `cancellation_policy`   TEXT DEFAULT NULL,
    `table_duration`        INT UNSIGNED NOT NULL DEFAULT 90,
    `time_step`             INT UNSIGNED NOT NULL DEFAULT 30,
    `booking_advance_min`   INT UNSIGNED NOT NULL DEFAULT 0,
    `booking_advance_max`   INT UNSIGNED NOT NULL DEFAULT 60,
    `timezone`              VARCHAR(50) NOT NULL DEFAULT 'Europe/Rome',
    `is_active`             TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_slug` (`slug`),
    INDEX `idx_custom_domain` (`custom_domain`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 2. USERS (Utenti dashboard)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`         INT UNSIGNED DEFAULT NULL,
    `email`             VARCHAR(255) NOT NULL UNIQUE,
    `password_hash`     VARCHAR(255) NOT NULL,
    `first_name`        VARCHAR(100) NOT NULL,
    `last_name`         VARCHAR(100) NOT NULL,
    `role`              ENUM('super_admin','owner','staff') NOT NULL DEFAULT 'owner',
    `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at`     DATETIME DEFAULT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_tenant_id` (`tenant_id`),
    INDEX `idx_email` (`email`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 3. TIME_SLOTS (Fasce orarie per giorno)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `time_slots` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `day_of_week`   TINYINT UNSIGNED NOT NULL COMMENT '0=Lun, 6=Dom',
    `slot_time`     TIME NOT NULL,
    `max_covers`    INT UNSIGNED NOT NULL DEFAULT 20,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,

    UNIQUE KEY `uk_tenant_day_time` (`tenant_id`, `day_of_week`, `slot_time`),
    INDEX `idx_tenant_id` (`tenant_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 4. SLOT_OVERRIDES (Override per date specifiche)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `slot_overrides` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `override_date`     DATE NOT NULL,
    `slot_time`         TIME DEFAULT NULL COMMENT 'NULL = giorno intero',
    `max_covers`        INT UNSIGNED DEFAULT NULL,
    `is_closed`         TINYINT(1) NOT NULL DEFAULT 0,
    `note`              VARCHAR(255) DEFAULT NULL,

    INDEX `idx_tenant_date` (`tenant_id`, `override_date`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 5. CUSTOMERS (Clienti prenotazioni)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `first_name`        VARCHAR(100) NOT NULL,
    `last_name`         VARCHAR(100) NOT NULL,
    `email`             VARCHAR(255) NOT NULL,
    `phone`             VARCHAR(30) NOT NULL,
    `total_bookings`    INT UNSIGNED NOT NULL DEFAULT 0,
    `total_noshow`      INT UNSIGNED NOT NULL DEFAULT 0,
    `notes`             TEXT DEFAULT NULL,
    `is_blocked`        TINYINT(1) NOT NULL DEFAULT 0,
    `blocked_at`        DATETIME DEFAULT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_tenant_email` (`tenant_id`, `email`),
    INDEX `idx_tenant_id` (`tenant_id`),
    INDEX `idx_phone` (`phone`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 6. RESERVATIONS (Prenotazioni)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservations` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`             INT UNSIGNED NOT NULL,
    `customer_id`           INT UNSIGNED NOT NULL,
    `reservation_date`      DATE NOT NULL,
    `reservation_time`      TIME NOT NULL,
    `party_size`            TINYINT UNSIGNED NOT NULL,
    `status`                ENUM('confirmed','pending','arrived','noshow','cancelled') NOT NULL DEFAULT 'pending',
    `deposit_required`      TINYINT(1) NOT NULL DEFAULT 0,
    `deposit_amount`        DECIMAL(8,2) DEFAULT NULL,
    `deposit_paid`          TINYINT(1) NOT NULL DEFAULT 0,
    `stripe_payment_id`     VARCHAR(255) DEFAULT NULL,
    `internal_notes`        TEXT DEFAULT NULL,
    `cancellation_reason`   VARCHAR(500) DEFAULT NULL,
    `cancelled_at`          DATETIME DEFAULT NULL,
    `source`                ENUM('widget','dashboard','phone','walkin') NOT NULL DEFAULT 'widget',
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_tenant_date` (`tenant_id`, `reservation_date`),
    INDEX `idx_tenant_status` (`tenant_id`, `status`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_date_time` (`reservation_date`, `reservation_time`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 7. RESERVATION_LOGS (Audit trail)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservation_logs` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reservation_id`    INT UNSIGNED NOT NULL,
    `old_status`        VARCHAR(20) DEFAULT NULL,
    `new_status`        VARCHAR(20) NOT NULL,
    `changed_by`        INT UNSIGNED DEFAULT NULL,
    `note`              VARCHAR(500) DEFAULT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_reservation_id` (`reservation_id`),
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 8. TENANT_SETTINGS (Config key-value)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tenant_settings` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `setting_key`       VARCHAR(100) NOT NULL,
    `setting_value`     TEXT DEFAULT NULL,

    UNIQUE KEY `uk_tenant_key` (`tenant_id`, `setting_key`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 9. SUBSCRIPTIONS (Abbonamenti SaaS)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id`                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`                 INT UNSIGNED NOT NULL,
    `stripe_subscription_id`    VARCHAR(255) DEFAULT NULL,
    `plan`                      ENUM('base','deposit','custom') NOT NULL DEFAULT 'base',
    `price`                     DECIMAL(8,2) NOT NULL,
    `status`                    ENUM('active','past_due','cancelled','trialing') NOT NULL DEFAULT 'active',
    `current_period_start`      DATE DEFAULT NULL,
    `current_period_end`        DATE DEFAULT NULL,
    `created_at`                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_tenant_id` (`tenant_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- 10. PASSWORD_RESETS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `token`         VARCHAR(64) NOT NULL UNIQUE,
    `expires_at`    DATETIME NOT NULL,
    `used`          TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_token` (`token`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
