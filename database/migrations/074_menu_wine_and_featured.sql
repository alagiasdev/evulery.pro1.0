-- Migration 074: Carta dei vini integrata + etichetta sezione "in evidenza" editabile
--
-- Tutto additivo e retrocompatibile:
--  - menu_categories.is_wine : marca una categoria come "Vini" (render riga-vino).
--  - menu_items.price_bottle  : prezzo alla bottiglia (price esistente = calice/prezzo piatto).
--  - menu_items.price reso NULL-abile : un vino puo' avere solo la bottiglia (price = calice vuoto).
--    I piatti continuano a richiedere il prezzo via validazione server (regola applicativa, non DB).
--  - tenants.menu_featured_label : titolo editabile del blocco "in evidenza" (default "Piatti del giorno").

ALTER TABLE `menu_categories`
    ADD COLUMN `is_wine` TINYINT(1) NOT NULL DEFAULT 0 AFTER `icon`;

ALTER TABLE `menu_items`
    ADD COLUMN `price_bottle` DECIMAL(8,2) NULL DEFAULT NULL AFTER `price`;

ALTER TABLE `menu_items`
    MODIFY COLUMN `price` DECIMAL(8,2) NULL DEFAULT NULL;

ALTER TABLE `tenants`
    ADD COLUMN `menu_featured_label` VARCHAR(40) NULL DEFAULT NULL AFTER `menu_tagline`;
