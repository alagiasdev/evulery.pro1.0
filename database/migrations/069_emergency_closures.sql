-- Migration 069: Chiusura straordinaria (emergenze: allagamenti, guasti, ecc.)
-- Diversa dalle "Chiusure e Ferie" programmate: qui si gestiscono anche le
-- prenotazioni gia' prese e si avvisano i clienti via email.
--
-- 1) Nuovo stato 'suspended' sulle prenotazioni: per la modalita' "Sospendi"
--    (imprevisto incerto) la prenotazione NON viene cancellata ma messa in
--    sospeso; i coperti restano occupati finche' non si decide. Alla
--    riapertura torna allo stato precedente, alla chiusura definitiva viene
--    annullata.
-- 2) emergency_closures: l'evento chiusura (periodo, ambito, modalita',
--    messaggio, stato). Tiene gli id degli slot_overrides creati per il blocco
--    cosi' la riapertura rimuove esattamente quelli (senza toccare le ferie).
-- 3) emergency_closure_items: snapshot per-prenotazione (stato precedente) per
--    poter ripristinare correttamente alla riapertura.

ALTER TABLE `reservations`
    MODIFY `status` ENUM('confirmed','pending','arrived','noshow','cancelled','suspended') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS `emergency_closures` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`             INT UNSIGNED NOT NULL,
    `date_from`             DATE NOT NULL,
    `date_to`               DATE NOT NULL,
    `time_from`             TIME DEFAULT NULL,
    `time_to`               TIME DEFAULT NULL,
    `scope_label`           VARCHAR(80) NOT NULL DEFAULT 'Giorno intero',
    `mode`                  ENUM('cancel','suspend') NOT NULL,
    `message`               TEXT DEFAULT NULL,
    `status`                ENUM('active','resolved') NOT NULL DEFAULT 'active',
    `resolution`            ENUM('reopened','closed') DEFAULT NULL,
    `blocked_override_ids`  TEXT DEFAULT NULL,
    `affected_count`        INT NOT NULL DEFAULT 0,
    `created_by`            INT UNSIGNED DEFAULT NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `resolved_at`           DATETIME DEFAULT NULL,

    INDEX `idx_tenant_status` (`tenant_id`, `status`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `emergency_closure_items` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `closure_id`       INT UNSIGNED NOT NULL,
    `reservation_id`   INT UNSIGNED NOT NULL,
    `previous_status`  VARCHAR(20) NOT NULL,

    INDEX `idx_closure` (`closure_id`),
    INDEX `idx_reservation` (`reservation_id`),
    FOREIGN KEY (`closure_id`) REFERENCES `emergency_closures`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
