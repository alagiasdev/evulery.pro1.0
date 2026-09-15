-- Impostazioni globali dell'applicazione, in chiave/valore.
--
-- E' la prima tabella di configurazione NON legata a un tenant: finora
-- esistevano solo tenant_settings e tenant_hub_settings, che sono
-- per-ristorante. Serve al checkout con bonifico: coordinate bancarie,
-- dicitura fiscale, marca da bollo e giorni di tolleranza.
--
-- I tre numeri fiscali stanno qui e non nel codice perche' sono valori che
-- si scoprono sbagliati usandoli: la tolleranza dipende da ogni quanto si
-- guarda l'home banking, il bollo da cosa dice il commercialista. Devono
-- potersi cambiare senza un deploy.

CREATE TABLE IF NOT EXISTS `app_settings` (
    `setting_key`   VARCHAR(64) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valori di partenza. L'ON DUPLICATE e' un no-op voluto: se la migration
-- venisse rieseguita non deve sovrascrivere l'IBAN gia' inserito.
INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
    ('bank_holder',          ''),
    ('bank_iban',            ''),
    ('bank_name',            ''),
    ('bank_bic',             ''),
    ('tax_notice',           'Operazione esente IVA - regime forfettario (L. 190/2014, art. 1, commi 54-89)'),
    ('stamp_duty_amount',    '2.00'),
    ('stamp_duty_threshold', '77.47'),
    ('grace_days',           '10'),
    ('billing_email_from',   '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
