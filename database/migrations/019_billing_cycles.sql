-- Migration 019: Billing cycles (semestrale/annuale) + sconto extra per subscription
-- Modello: prezzo mensile mostrato, pagamento semestrale (5 mesi su 6) o annuale (10 mesi su 12)

-- -----------------------------------------------------------
-- 1. ALTER PLANS — mesi da pagare per ciclo
-- -----------------------------------------------------------
ALTER TABLE `plans`
    ADD COLUMN `billing_months_semi`   TINYINT UNSIGNED NOT NULL DEFAULT 5  COMMENT 'Mesi da pagare su 6 (default 5 = 1 mese gratis)' AFTER `price`,
    ADD COLUMN `billing_months_annual` TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Mesi da pagare su 12 (default 10 = 2 mesi gratis)' AFTER `billing_months_semi`;

-- -----------------------------------------------------------
-- 2. ALTER SUBSCRIPTIONS — ciclo + sconto extra
-- -----------------------------------------------------------
ALTER TABLE `subscriptions`
    ADD COLUMN `billing_cycle`   ENUM('semiannual','annual') NOT NULL DEFAULT 'annual' COMMENT 'Ciclo di fatturazione' AFTER `price`,
    ADD COLUMN `extra_discount`  DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Sconto extra % applicato dall''admin' AFTER `billing_cycle`;
