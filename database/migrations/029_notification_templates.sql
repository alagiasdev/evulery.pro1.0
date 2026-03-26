-- 029: Template personalizzabili per notifiche push

ALTER TABLE tenants
    ADD COLUMN notif_title_new_reservation VARCHAR(255) DEFAULT NULL AFTER notify_cancellation,
    ADD COLUMN notif_body_new_reservation VARCHAR(500) DEFAULT NULL AFTER notif_title_new_reservation,
    ADD COLUMN notif_title_cancellation VARCHAR(255) DEFAULT NULL AFTER notif_body_new_reservation,
    ADD COLUMN notif_body_cancellation VARCHAR(500) DEFAULT NULL AFTER notif_title_cancellation,
    ADD COLUMN notif_title_deposit VARCHAR(255) DEFAULT NULL AFTER notif_body_cancellation,
    ADD COLUMN notif_body_deposit VARCHAR(500) DEFAULT NULL AFTER notif_title_deposit;
