-- Migration 036: Customer extended fields (birthday, last_visit, tags)
-- Supports enhanced CSV import from Plateform and other systems

ALTER TABLE customers
    ADD COLUMN birthday DATE DEFAULT NULL AFTER phone,
    ADD COLUMN last_visit DATE DEFAULT NULL AFTER birthday,
    ADD COLUMN tags JSON DEFAULT NULL AFTER last_visit;
