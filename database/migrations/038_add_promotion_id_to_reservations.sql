-- ============================================================
-- Migration 038: Add promotion_id to reservations
-- Tracks which specific promotion generated the discount
-- ============================================================

ALTER TABLE `reservations`
    ADD COLUMN `promotion_id` INT UNSIGNED DEFAULT NULL AFTER `discount_percent`,
    ADD INDEX `idx_promotion` (`promotion_id`),
    ADD CONSTRAINT `fk_reservations_promotion`
        FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`id`) ON DELETE SET NULL;

-- Backfill: try to match existing discounted reservations to promotions
-- Only matches when there's exactly ONE promotion with that discount % for the tenant
UPDATE reservations r
JOIN (
    SELECT tenant_id, discount_percent, MIN(id) AS promo_id
    FROM promotions
    GROUP BY tenant_id, discount_percent
    HAVING COUNT(*) = 1
) single_match ON single_match.tenant_id = r.tenant_id
    AND single_match.discount_percent = r.discount_percent
SET r.promotion_id = single_match.promo_id
WHERE r.discount_percent IS NOT NULL
  AND r.promotion_id IS NULL;
