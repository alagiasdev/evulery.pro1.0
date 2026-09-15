-- Dati di fatturazione del ristorante, per la fattura dell'abbonamento.
--
-- PERCHE' NON SI RIUSA tenants.address: e' un VARCHAR(500) libero, pensato per
-- mostrare "dove si trova il ristorante" sulle pagine pubbliche. Una fattura
-- ha bisogno di via, citta', CAP e provincia SEPARATI, e per giunta la sede
-- legale puo' non coincidere con quella del locale.
--
-- Il codice fiscale c'e' perche' per le ditte individuali NON coincide con la
-- partita IVA. SDI e PEC sono alternativi: ne basta uno dei due, ed e' la
-- validazione a farlo rispettare (non il database, che deve restare permissivo
-- per non bloccare i dati inseriti dall'area admin).

ALTER TABLE `tenants`
    ADD COLUMN `billing_name`     VARCHAR(160) DEFAULT NULL COMMENT 'Ragione sociale come da visura' AFTER `address`,
    ADD COLUMN `billing_vat`      VARCHAR(13)  DEFAULT NULL COMMENT 'Partita IVA, 11 cifre' AFTER `billing_name`,
    ADD COLUMN `billing_tax_code` VARCHAR(16)  DEFAULT NULL COMMENT 'Codice fiscale, se diverso dalla P.IVA' AFTER `billing_vat`,
    ADD COLUMN `billing_address`  VARCHAR(160) DEFAULT NULL COMMENT 'Sede legale: via e civico' AFTER `billing_tax_code`,
    ADD COLUMN `billing_city`     VARCHAR(80)  DEFAULT NULL AFTER `billing_address`,
    ADD COLUMN `billing_zip`      VARCHAR(5)   DEFAULT NULL AFTER `billing_city`,
    ADD COLUMN `billing_province` VARCHAR(2)   DEFAULT NULL AFTER `billing_zip`,
    ADD COLUMN `billing_sdi`      VARCHAR(7)   DEFAULT NULL COMMENT 'Codice destinatario SDI' AFTER `billing_province`,
    ADD COLUMN `billing_pec`      VARCHAR(160) DEFAULT NULL COMMENT 'Alternativa allo SDI' AFTER `billing_sdi`;
