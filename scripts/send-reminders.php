<?php
/**
 * Send reservation reminder emails (24h + 2h before).
 *
 * Run every 15 minutes via Task Scheduler or cron:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\evulery.pro1.0\scripts\send-reminders.php
 *
 * Logic:
 *   - 24h reminder: reservation datetime is between NOW+23h and NOW+25h, not already sent
 *   - 2h reminder:  reservation datetime is between NOW+1h30m and NOW+2h30m, not already sent
 */

// Bootstrap the application
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

// Load .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

use App\Core\Database;
use App\Models\Tenant;
use App\Services\MailService;

$db = Database::getInstance();
$tenantModel = new Tenant();
$now = date('Y-m-d H:i:s');
$sent24h = 0;
$sent2h = 0;
$errors = 0;

echo "[" . $now . "] Starting reminder send...\n";

// ============================================================
// 1) 24h REMINDERS
// Prenotazioni confermate tra 23h e 25h da adesso, senza reminder 24h
// ============================================================
$stmt = $db->prepare(
    'SELECT r.id, r.reservation_date, r.reservation_time, r.party_size, r.customer_notes,
            c.first_name, c.last_name, c.email, c.phone,
            t.id AS tenant_id, t.name AS tenant_name, t.slug, t.email AS tenant_email,
            t.phone AS tenant_phone, t.address AS tenant_address
     FROM reservations r
     JOIN customers c ON r.customer_id = c.id
     JOIN tenants t ON r.tenant_id = t.id
     WHERE r.status = "confirmed"
     AND r.reminder_24h_sent_at IS NULL
     AND TIMESTAMP(r.reservation_date, r.reservation_time) BETWEEN
         DATE_ADD(NOW(), INTERVAL 23 HOUR) AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
     AND t.is_active = 1'
);
$stmt->execute();
$reminders24h = $stmt->fetchAll();

echo "  Found " . count($reminders24h) . " reservations for 24h reminder.\n";

foreach ($reminders24h as $row) {
    // Service gate: skip tenants without email_reminder service
    if (!$tenantModel->canUseService((int)$row['tenant_id'], 'email_reminder')) {
        echo "    [SKIP] 24h reminder — tenant '{$row['tenant_name']}' plan does not include email_reminder\n";
        continue;
    }

    $tenant = [
        'name'    => $row['tenant_name'],
        'slug'    => $row['slug'],
        'email'   => $row['tenant_email'],
        'phone'   => $row['tenant_phone'],
        'address' => $row['tenant_address'],
    ];

    $ok = MailService::sendReservationReminder($row, $tenant, '24h');

    if ($ok) {
        $update = $db->prepare('UPDATE reservations SET reminder_24h_sent_at = NOW() WHERE id = :id');
        $update->execute(['id' => $row['id']]);
        $sent24h++;
        echo "    [OK] 24h reminder → {$row['first_name']} {$row['last_name']} ({$row['email']}) - {$row['reservation_date']} {$row['reservation_time']}\n";
    } else {
        $errors++;
        echo "    [ERR] 24h reminder FAILED → {$row['email']} - reservation #{$row['id']}\n";
    }
}

// ============================================================
// 2) 2h REMINDERS
// Prenotazioni confermate tra 1h30m e 2h30m da adesso, senza reminder 2h
// ============================================================
$stmt = $db->prepare(
    'SELECT r.id, r.reservation_date, r.reservation_time, r.party_size, r.customer_notes,
            c.first_name, c.last_name, c.email, c.phone,
            t.id AS tenant_id, t.name AS tenant_name, t.slug, t.email AS tenant_email,
            t.phone AS tenant_phone, t.address AS tenant_address
     FROM reservations r
     JOIN customers c ON r.customer_id = c.id
     JOIN tenants t ON r.tenant_id = t.id
     WHERE r.status = "confirmed"
     AND r.reminder_2h_sent_at IS NULL
     AND TIMESTAMP(r.reservation_date, r.reservation_time) BETWEEN
         DATE_ADD(NOW(), INTERVAL 90 MINUTE) AND DATE_ADD(NOW(), INTERVAL 150 MINUTE)
     AND t.is_active = 1'
);
$stmt->execute();
$reminders2h = $stmt->fetchAll();

echo "  Found " . count($reminders2h) . " reservations for 2h reminder.\n";

foreach ($reminders2h as $row) {
    // Service gate: skip tenants without email_reminder service
    if (!$tenantModel->canUseService((int)$row['tenant_id'], 'email_reminder')) {
        echo "    [SKIP] 2h reminder — tenant '{$row['tenant_name']}' plan does not include email_reminder\n";
        continue;
    }

    $tenant = [
        'name'    => $row['tenant_name'],
        'slug'    => $row['slug'],
        'email'   => $row['tenant_email'],
        'phone'   => $row['tenant_phone'],
        'address' => $row['tenant_address'],
    ];

    $ok = MailService::sendReservationReminder($row, $tenant, '2h');

    if ($ok) {
        $update = $db->prepare('UPDATE reservations SET reminder_2h_sent_at = NOW() WHERE id = :id');
        $update->execute(['id' => $row['id']]);
        $sent2h++;
        echo "    [OK] 2h reminder → {$row['first_name']} {$row['last_name']} ({$row['email']}) - {$row['reservation_date']} {$row['reservation_time']}\n";
    } else {
        $errors++;
        echo "    [ERR] 2h reminder FAILED → {$row['email']} - reservation #{$row['id']}\n";
    }
}

// ============================================================
// Summary
// ============================================================
echo "\n[DONE] 24h sent: {$sent24h}, 2h sent: {$sent2h}, errors: {$errors}\n";