-- Onboarding ristoratore: card guidata "Completa la configurazione" in dashboard.
-- Mostrata SOLO ai tenant nuovi (onboarding_completed_at NULL e creati da < 30 giorni).

ALTER TABLE tenants
  ADD COLUMN general_configured TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN onboarding_completed_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN onboarding_collapsed TINYINT(1) NOT NULL DEFAULT 0;

-- Grandfathering: i tenant GIA' esistenti al momento della migration non vedono
-- l'onboarding (sono gia' operativi). I tenant creati dopo partono con i default
-- (onboarding_completed_at NULL -> card visibile finche' non completano/nascondono).
UPDATE tenants SET onboarding_completed_at = NOW(), general_configured = 1;
