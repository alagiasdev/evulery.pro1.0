-- Migration 073: destinazione "site" per i link tracciati (campagne verso il
-- sito del cliente, dove c'e' il widget embeddato con tracciamento).
-- Aggiunge 'site' all'enum di marketing_links.destination.

ALTER TABLE `marketing_links`
    MODIFY `destination` ENUM('hub','booking','menu','order','site') NOT NULL DEFAULT 'hub';
