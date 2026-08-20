-- "Ricordami": token persistente per restare loggati oltre la sessione.
-- Pattern selector/validator: nel DB si salva solo l'HASH del validator.
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED NOT NULL,
    `selector`       VARCHAR(24) NOT NULL,
    `validator_hash` CHAR(64) NOT NULL,
    `expires_at`     DATETIME NOT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_selector` (`selector`),
    KEY `idx_user` (`user_id`),
    KEY `idx_expires` (`expires_at`),
    CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
