-- Migration 078: email NULLable su customers + conversione '' -> NULL.
--
-- Bug (live): uk_tenant_email = UNIQUE(tenant_id, email) con `email NOT NULL`
-- impedisce piu' di un cliente senza email per tenant. L'import CSV di righe
-- solo-telefono inserisce email='' -> la 2a riga collide:
--   SQLSTATE[23000] 1062 Duplicate entry '<tenant>-' for key 'uk_tenant_email'.
--
-- Fix: email NULLable. In MySQL il vincolo UNIQUE ammette piu' valori NULL
-- (NULL != NULL), quindi i clienti senza email coesistono, mentre la deduplica
-- sulle email reali resta intatta. Il codice (Customer::createImported /
-- findOrCreate) salva NULL al posto di '' per le email mancanti.

ALTER TABLE `customers`
    MODIFY COLUMN `email` VARCHAR(255) NULL DEFAULT NULL;

-- Normalizza i record gia' esistenti con email vuota (al massimo 1 per tenant,
-- proprio per via del vincolo univoco) a NULL, per coerenza col nuovo modello.
UPDATE `customers` SET `email` = NULL WHERE `email` = '';
