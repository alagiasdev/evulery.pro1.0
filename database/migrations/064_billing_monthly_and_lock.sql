-- ====================================================================
-- Billing: ciclo mensile + lock prezzo per Early Adopter (36 mesi)
--
-- Modifiche:
-- 1. subscriptions.billing_cycle accetta anche 'monthly' (oltre a
--    'semiannual' e 'annual'). Per i nuovi clienti che vogliono pagare
--    mensilmente senza sconto.
--
-- 2. subscriptions.locked_price_until DATE NULL: data fino alla quale
--    il prezzo della subscription e' "bloccato". Quando il listino base
--    aumentera' (es. dal 1 ottobre 2026: 49->69, 79->109, 129->169),
--    le subscription con locked_price_until > NOW() manterranno il
--    prezzo vecchio. Usato per il programma Early Adopter: chi firma
--    entro il 30/09/2026 ha il prezzo bloccato per 36 mesi.
--
-- 3. plans.price_semiannual DECIMAL e plans.price_annual DECIMAL:
--    permettono di settare prezzi semestrali/annuali in cifre tonde
--    (€279, €449, €729, €490, €790, €1290) non derivati da formule
--    integer. Se NULL, fallback al calcolo basato su billing_months_*.
-- ====================================================================

ALTER TABLE subscriptions
    MODIFY COLUMN billing_cycle ENUM('monthly', 'semiannual', 'annual') NOT NULL DEFAULT 'annual',
    ADD COLUMN locked_price_until DATE NULL DEFAULT NULL AFTER extra_discount;

ALTER TABLE plans
    ADD COLUMN price_semiannual DECIMAL(8,2) NULL DEFAULT NULL AFTER price,
    ADD COLUMN price_annual DECIMAL(8,2) NULL DEFAULT NULL AFTER price_semiannual;

-- Allinea i prezzi al listino di lancio (valido fino al 30/09/2026)
UPDATE plans SET price = 49.00,  price_semiannual = 279.00, price_annual = 490.00  WHERE slug = 'starter';
UPDATE plans SET price = 79.00,  price_semiannual = 449.00, price_annual = 790.00  WHERE slug = 'professional';
UPDATE plans SET price = 129.00, price_semiannual = 729.00, price_annual = 1290.00 WHERE slug = 'enterprise';
