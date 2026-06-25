-- Migration 076: isolamento per-tenant delle iscrizioni push.
--
-- Problema: l'univoco era solo su `endpoint` (globale per browser, perche' la
-- chiave VAPID e' unica per tutta l'app). Con l'impersonation i browser
-- dell'admin venivano registrati sotto i tenant guardati e ne ricevevano le
-- notifiche; inoltre un titolare con piu' ristoranti perdeva l'iscrizione di
-- uno dei due (ping-pong sul tenant_id).
--
-- Fix:
--   1) TRUNCATE: azzera il pregresso inquinato. I device si ri-registrano da
--      soli al primo caricamento della dashboard (re-sync self-heal lato JS),
--      quindi e' indolore.
--   2) Rimuove l'univoco solo-endpoint.
--   3) Univoco composito (tenant_id, endpoint): un device puo' stare
--      correttamente sotto piu' tenant, ciascuno scopato.
--
-- Nota indice: endpoint(190) per restare a 764 byte (4 tenant_id + 760),
-- identico al footprint dell'univoco originale endpoint(191) -> sicuro anche
-- su MySQL con limite 767 byte.

TRUNCATE TABLE `push_subscriptions`;

ALTER TABLE `push_subscriptions`
    DROP INDEX `uq_endpoint`;

ALTER TABLE `push_subscriptions`
    ADD UNIQUE KEY `uq_tenant_endpoint` (`tenant_id`, `endpoint`(190));
