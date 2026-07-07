-- Migration 081: allinea le descrizioni dei piani Professional ed Enterprise.
-- Mostrate in admin (Abbonamenti -> Piani). La copy del seed 016 era stale:
--  - Professional citava "supporto prioritario" (che ora e' Enterprise-only);
--  - Enterprise citava "gruppi e catene di ristoranti" (multi-sede non venduta).
-- Le riallineo al reale set di servizi per piano (fonte: plan_services).

UPDATE `plans`
SET `description` = 'Il piano più adottato: reminder, caparra, gestione reputazione, Vetrina Digitale, Marketing & Provenienza, menu multilingua e statistiche.'
WHERE `slug` = 'professional';

UPDATE `plans`
SET `description` = 'La soluzione completa: gestione tavoli e sala, ordini online, accessi staff e supporto prioritario.'
WHERE `slug` = 'enterprise';