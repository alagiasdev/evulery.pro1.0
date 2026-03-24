-- Rename 'statistics' service label to 'Statistiche Clienti'
UPDATE services SET name = 'Statistiche Clienti', description = 'Statistiche e analisi della clientela' WHERE `key` = 'statistics';

-- Add analytics service to catalog (future development)
INSERT INTO services (`key`, name, description, sort_order, is_active)
VALUES ('analytics', 'Analytics', 'Cruscotto analytics avanzato: trend prenotazioni, heatmap, revenue', 13, 1);

-- Associate analytics with Professional and Enterprise plans
INSERT INTO plan_services (plan_id, service_id)
SELECT p.id, s.id
FROM plans p, services s
WHERE s.`key` = 'analytics'
AND p.slug IN ('professional', 'enterprise');
