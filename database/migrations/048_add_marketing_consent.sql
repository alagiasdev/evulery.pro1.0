-- ============================================================
-- Migration 048: GDPR marketing consent (opt-in esplicito)
--
-- Aggiunge il consenso esplicito al marketing su customers, sostituendo
-- il modello "opt-out implicito" precedente (campo unsubscribed) con un
-- modello GDPR-compliant a 3 stati:
--   NULL = consenso mai chiesto (cliente legacy o nuovo non ancora alla
--          step 4 del widget)
--   1    = consenso esplicito acquisito (timestamp + source per audit)
--   0    = consenso esplicitamente negato o revocato
--
-- Backfill clienti esistenti:
-- Dato che oggi non ci sono clienti reali in produzione, ma in dev sì,
-- migrare in modo conservativo:
--   - subscribed (unsubscribed=0)   → consenso 1, source 'legacy_pre_gdpr'
--   - unsubscribed (unsubscribed=1) → consenso 0, source 'legacy_unsubscribed'
-- Audit timestamp: prendiamo updated_at o unsubscribed_at se presente.
-- ============================================================

ALTER TABLE `customers`
    ADD COLUMN `marketing_consent` TINYINT(1) DEFAULT NULL
        AFTER `unsubscribed_at`,
    ADD COLUMN `marketing_consent_at` DATETIME DEFAULT NULL
        AFTER `marketing_consent`,
    ADD COLUMN `marketing_consent_source` VARCHAR(50) DEFAULT NULL
        AFTER `marketing_consent_at`;

-- Backfill: clienti già iscritti → consenso esplicito legacy
UPDATE `customers`
SET `marketing_consent` = 1,
    `marketing_consent_at` = COALESCE(`updated_at`, NOW()),
    `marketing_consent_source` = 'legacy_pre_gdpr'
WHERE `unsubscribed` = 0
  AND `marketing_consent` IS NULL;

-- Backfill: clienti unsubscribed → consenso negato
UPDATE `customers`
SET `marketing_consent` = 0,
    `marketing_consent_at` = COALESCE(`unsubscribed_at`, `updated_at`, NOW()),
    `marketing_consent_source` = 'legacy_unsubscribed'
WHERE `unsubscribed` = 1
  AND `marketing_consent` IS NULL;
