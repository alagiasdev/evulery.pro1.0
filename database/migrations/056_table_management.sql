-- ============================================================
-- Migration 056: Gestione Tavoli (Fase 1)
--
-- Sala virtuale: il ristoratore definisce i tavoli per area,
-- li ordina per priorità, e il sistema assegna automaticamente
-- un tavolo a ogni prenotazione (non bloccante).
--
-- Servizio gatato `table_management` → assegnato a Enterprise.
-- Le colonne shape / position_x / position_y sono create da
-- subito ma usate solo in Fase 2 (mappa visuale).
-- ============================================================

-- Tavoli del ristorante
CREATE TABLE `restaurant_tables` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `name`          VARCHAR(60)  NOT NULL,
    `capacity`      TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `priority`      INT UNSIGNED NOT NULL DEFAULT 0,
    `area`          VARCHAR(60)  DEFAULT NULL,
    `shape`         VARCHAR(10)  NOT NULL DEFAULT 'square',
    `position_x`    INT DEFAULT NULL,
    `position_y`    INT DEFAULT NULL,
    `internal_note` VARCHAR(255) DEFAULT NULL,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rt_tenant` (`tenant_id`),
    CONSTRAINT `fk_rt_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tavoli assegnati a una prenotazione (multi-riga per le combinazioni)
CREATE TABLE `reservation_tables` (
    `reservation_id` INT UNSIGNED NOT NULL,
    `table_id`       INT UNSIGNED NOT NULL,
    `is_auto`        TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`reservation_id`, `table_id`),
    KEY `idx_restab_table` (`table_id`),
    CONSTRAINT `fk_restab_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_restab_table` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coppie di tavoli combinabili per gruppi grandi
CREATE TABLE `table_combinations` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `table_a_id`  INT UNSIGNED NOT NULL,
    `table_b_id`  INT UNSIGNED NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_combo_pair` (`table_a_id`, `table_b_id`),
    KEY `idx_combo_tenant` (`tenant_id`),
    CONSTRAINT `fk_combo_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_combo_table_a` FOREIGN KEY (`table_a_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_combo_table_b` FOREIGN KEY (`table_b_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Impostazioni auto-assegnazione per tenant
ALTER TABLE `tenants`
    ADD COLUMN `table_auto_assign` TINYINT(1) NOT NULL DEFAULT 1 AFTER `deposit_days`,
    ADD COLUMN `table_turnover_buffer` SMALLINT UNSIGNED NOT NULL DEFAULT 15 AFTER `table_auto_assign`;

-- Servizio gatato `table_management` nel catalogo
INSERT INTO `services` (`key`, `name`, `description`, `sort_order`, `is_active`)
VALUES ('table_management', 'Gestione Tavoli', 'Sala virtuale e assegnazione automatica dei tavoli', 15, 1);

-- Associa il servizio al piano Enterprise
INSERT INTO `plan_services` (`plan_id`, `service_id`)
SELECT p.id, s.id
FROM `plans` p, `services` s
WHERE s.`key` = 'table_management' AND p.slug = 'enterprise';
