-- ============================================================
-- Evulery.Pro 1.0 - Seed Data
-- ============================================================

-- Super Admin User (password: admin123)
-- password_hash generated with password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `users` (`tenant_id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`)
VALUES (NULL, 'admin@evulery.pro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Admin', 'super_admin', 1);

-- Demo Tenant: Trattoria Da Mario
INSERT INTO `tenants` (`slug`, `name`, `email`, `phone`, `address`, `plan`, `plan_price`, `deposit_enabled`, `deposit_amount`, `cancellation_policy`, `table_duration`, `time_step`, `booking_advance_max`, `is_active`)
VALUES (
    'trattoria-da-mario',
    'Trattoria Da Mario',
    'info@trattoriadamario.it',
    '+39 02 1234567',
    'Via Roma 42, 20121 Milano',
    'base',
    49.00,
    0,
    NULL,
    'Cancellazione gratuita fino a 2 ore prima della prenotazione.',
    90,
    30,
    60,
    1
);

-- Owner user for demo tenant (password: owner123)
INSERT INTO `users` (`tenant_id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`)
VALUES (1, 'mario@trattoriadamario.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', 'owner', 1);

-- Time slots for demo tenant (Lunedi-Sabato, cena)
-- Lunedi (0)
INSERT INTO `time_slots` (`tenant_id`, `day_of_week`, `slot_time`, `max_covers`) VALUES
(1, 0, '19:00', 20), (1, 0, '19:30', 25), (1, 0, '20:00', 30),
(1, 0, '20:30', 30), (1, 0, '21:00', 25), (1, 0, '21:30', 18);

-- Martedi (1)
INSERT INTO `time_slots` (`tenant_id`, `day_of_week`, `slot_time`, `max_covers`) VALUES
(1, 1, '19:00', 20), (1, 1, '19:30', 25), (1, 1, '20:00', 30),
(1, 1, '20:30', 30), (1, 1, '21:00', 25), (1, 1, '21:30', 18);

-- Mercoledi (2)
INSERT INTO `time_slots` (`tenant_id`, `day_of_week`, `slot_time`, `max_covers`) VALUES
(1, 2, '19:00', 20), (1, 2, '19:30', 25), (1, 2, '20:00', 30),
(1, 2, '20:30', 30), (1, 2, '21:00', 25), (1, 2, '21:30', 18);

-- Giovedi (3)
INSERT INTO `time_slots` (`tenant_id`, `day_of_week`, `slot_time`, `max_covers`) VALUES
(1, 3, '19:00', 20), (1, 3, '19:30', 25), (1, 3, '20:00', 30),
(1, 3, '20:30', 30), (1, 3, '21:00', 25), (1, 3, '21:30', 18);

-- Venerdi (4)
INSERT INTO `time_slots` (`tenant_id`, `day_of_week`, `slot_time`, `max_covers`) VALUES
(1, 4, '19:00', 25), (1, 4, '19:30', 30), (1, 4, '20:00', 35),
(1, 4, '20:30', 35), (1, 4, '21:00', 30), (1, 4, '21:30', 20);

-- Sabato (5)
INSERT INTO `time_slots` (`tenant_id`, `day_of_week`, `slot_time`, `max_covers`) VALUES
(1, 5, '12:00', 25), (1, 5, '12:30', 30), (1, 5, '13:00', 30),
(1, 5, '13:30', 25),
(1, 5, '19:00', 30), (1, 5, '19:30', 35), (1, 5, '20:00', 40),
(1, 5, '20:30', 40), (1, 5, '21:00', 35), (1, 5, '21:30', 25);

-- Domenica (6) - chiuso (nessuno slot)

-- Demo customers
INSERT INTO `customers` (`tenant_id`, `first_name`, `last_name`, `email`, `phone`, `total_bookings`, `total_noshow`) VALUES
(1, 'Rossi', 'Mario', 'rossi.mario@email.it', '+39 333 1234567', 3, 0),
(1, 'Bianchi', 'Luca', 'luca.bianchi@email.it', '+39 340 7654321', 2, 1),
(1, 'Verdi', 'Anna', 'anna.verdi@email.it', '+39 328 9876543', 1, 0);

-- Demo reservations (for today - adjust date as needed)
INSERT INTO `reservations` (`tenant_id`, `customer_id`, `reservation_date`, `reservation_time`, `party_size`, `status`, `source`) VALUES
(1, 1, CURDATE(), '19:00', 2, 'confirmed', 'widget'),
(1, 2, CURDATE(), '19:30', 4, 'pending', 'widget'),
(1, 3, CURDATE(), '20:00', 6, 'confirmed', 'phone');
