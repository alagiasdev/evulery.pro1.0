-- Migration: Add customer_notes column to reservations
-- Stores notes provided by the customer during booking

ALTER TABLE `reservations` ADD COLUMN `customer_notes` TEXT DEFAULT NULL AFTER `internal_notes`;