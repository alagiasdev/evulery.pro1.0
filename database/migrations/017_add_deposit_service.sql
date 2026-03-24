-- Add deposit service to catalog
INSERT INTO services (`key`, name, description, sort_order, is_active)
VALUES ('deposit', 'Caparra', 'Richiesta caparra/deposito sulle prenotazioni', 6, 1);

-- Associate deposit service with Professional and Enterprise plans
-- (adjust plan IDs if different in your setup)
INSERT INTO plan_services (plan_id, service_id)
SELECT p.id, s.id
FROM plans p, services s
WHERE s.`key` = 'deposit'
AND p.slug IN ('professional', 'enterprise');
