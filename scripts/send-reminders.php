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

// Set timezone (must match web app)
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Rome');

// Load helpers for app_log()
require_once BASE_PATH . '/app/Helpers/functions.php';
require_once BASE_PATH . '/app/Helpers/view.php';

use App\Core\Database;
use App\Models\Tenant;
use App\Services\MailService;

$db = Database::getInstance();

// Sync MySQL timezone with PHP timezone
$phpTz = date('P'); // e.g. "+02:00"
$db->exec("SET time_zone = '{$phpTz}'");

$tenantModel = new Tenant();
$now = date('Y-m-d H:i:s');
$sent24h = 0;
$sent2h = 0;
$errors = 0;

app_log("Cron reminder: starting at {$now}", 'info');
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

app_log("Cron reminder: found " . count($reminders24h) . " reservations for 24h reminder", 'info');
echo "  Found " . count($reminders24h) . " reservations for 24h reminder.\n";

foreach ($reminders24h as $row) {
    // Service gate: skip tenants without email_reminder service
    if (!$tenantModel->canUseService((int)$row['tenant_id'], 'email_reminder')) {
        app_log("Cron reminder: [SKIP] 24h — tenant '{$row['tenant_name']}' no email_reminder service", 'info');
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
        app_log("Cron reminder: [OK] 24h → {$row['email']} - res #{$row['id']} ({$row['reservation_date']} {$row['reservation_time']})", 'info');
        echo "    [OK] 24h reminder → {$row['first_name']} {$row['last_name']} ({$row['email']}) - {$row['reservation_date']} {$row['reservation_time']}\n";
    } else {
        $errors++;
        app_log("Cron reminder: [ERR] 24h FAILED → {$row['email']} - res #{$row['id']}", 'error');
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

app_log("Cron reminder: found " . count($reminders2h) . " reservations for 2h reminder", 'info');
echo "  Found " . count($reminders2h) . " reservations for 2h reminder.\n";

foreach ($reminders2h as $row) {
    // Service gate: skip tenants without email_reminder service
    if (!$tenantModel->canUseService((int)$row['tenant_id'], 'email_reminder')) {
        app_log("Cron reminder: [SKIP] 2h — tenant '{$row['tenant_name']}' no email_reminder service", 'info');
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
        app_log("Cron reminder: [OK] 2h → {$row['email']} - res #{$row['id']} ({$row['reservation_date']} {$row['reservation_time']})", 'info');
        echo "    [OK] 2h reminder → {$row['first_name']} {$row['last_name']} ({$row['email']}) - {$row['reservation_date']} {$row['reservation_time']}\n";
    } else {
        $errors++;
        app_log("Cron reminder: [ERR] 2h FAILED → {$row['email']} - res #{$row['id']}", 'error');
        echo "    [ERR] 2h reminder FAILED → {$row['email']} - reservation #{$row['id']}\n";
    }
}

// ============================================================
// Summary
// ============================================================
$summary = "24h sent: {$sent24h}, 2h sent: {$sent2h}, errors: {$errors}";
app_log("Cron reminder: DONE — {$summary}", 'info');
echo "\n[DONE] {$summary}\n";