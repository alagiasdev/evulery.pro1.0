-- Migration 077: coda asincrona per notifiche (email ora, push in Fase 2).
--
-- Scopo: togliere l'invio SMTP (e in futuro il push) dal percorso della
-- richiesta web. Le email vengono accodate qui e spedite da un worker cron,
-- cosi' la creazione prenotazione/ordine torna a ~300ms invece di ~2s, e una
-- degradazione SMTP non blocca piu' l'utente (retry in background).
--
-- channel: 'email' (Fase 1) | 'push' (Fase 2) — la tabella e' generica.
-- payload: JSON con il contenuto gia' renderizzato.
--   email -> {to, subject, html, from_name, reply_to}
-- status:  'pending' | 'sent' | 'failed'  (VARCHAR, non ENUM: lezione #14)
-- available_at: quando la riga e' eleggibile (per il backoff sui retry).
-- Lock di concorrenza: gestito dal worker via flock (single instance), niente
-- lock a livello DB -> indipendente dalla versione MySQL.

CREATE TABLE `notification_outbox` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `channel`      VARCHAR(16)      NOT NULL DEFAULT 'email',
    `tenant_id`    INT UNSIGNED     DEFAULT NULL,
    `payload`      MEDIUMTEXT       NOT NULL,
    `status`       VARCHAR(12)      NOT NULL DEFAULT 'pending',
    `attempts`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    `available_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_error`   TEXT             DEFAULT NULL,
    `created_at`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `sent_at`      DATETIME         DEFAULT NULL,
    INDEX `idx_due` (`status`, `available_at`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
