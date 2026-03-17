-- Add reminder tracking columns to reservations
ALTER TABLE `reservations`
    ADD COLUMN `reminder_24h_sent_at` DATETIME DEFAULT NULL AFTER `source`,
    ADD COLUMN `reminder_2h_sent_at` DATETIME DEFAULT NULL AFTER `reminder_24h_sent_at`;