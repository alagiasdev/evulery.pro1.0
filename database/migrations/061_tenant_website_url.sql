-- ====================================================================
-- tenants.website_url — sito web esterno del ristoratore
--
-- Usato nel footer di ricevute stampate, email, ecc. Distinto da
-- `custom_domain` (che e' il dominio che PUNTA al widget Evulery).
-- Qui invece e' il sito vetrina del ristoratore (es. trattoriamario.it).
-- NULL = fallback automatico al widget Evulery (dash.evulery.it/{slug}).
-- ====================================================================

ALTER TABLE tenants
    ADD COLUMN website_url VARCHAR(255) DEFAULT NULL AFTER address;
