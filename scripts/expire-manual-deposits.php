<?php
/**
 * Annulla le prenotazioni con CAPARRA RICHIESTA MANUALMENTE (gruppi) non
 * completata entro la finestra configurata dal ristoratore.
 *
 * Run ogni ~5-15 minuti via Task Scheduler / cron:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\evulery.pro1.0\scripts\expire-manual-deposits.php
 *
 * Si applica SOLO a:
 *   - caparra richiesta a mano (deposit_manual_request = 1) ancora 'pending'
 *   - metodo a conferma automatica: Stripe (deposit_paid = 0) o carta a
 *     garanzia (guarantee_status = 'pending')
 *   - tenant con deposit_manual_window_minutes valorizzato (NULL = nessuna scadenza)
 *   - scaduta la finestra: deposit_requested_at + window < NOW()
 * NON tocca: widget pubblico (30 min via Stripe), bonifico/link (conferma manuale).
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Rome');
require_once BASE_PATH . '/app/Helpers/functions.php';
require_once BASE_PATH . '/app/Helpers/view.php';

use App\Core\Database;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Services\MailService;

$db = Database::getInstance();
$db->exec("SET time_zone = '" . date('P') . "'");

$now = date('Y-m-d H:i:s');
echo "[{$now}] Scadenza caparre manuali — start\n";
app_log("Cron expire-manual-deposits: start {$now}", 'info');

$stmt = $db->prepare(
    'SELECT r.id, r.booking_number, r.reservation_date, r.reservation_time, r.party_size,
            r.status, r.guarantee_status, r.deposit_paid, r.deposit_requested_at,
            c.first_name, c.last_name, c.email,
            t.id AS tenant_id, t.name AS tenant_name, t.slug, t.email AS tenant_email,
            t.deposit_type, t.deposit_manual_window_minutes
     FROM reservations r
     JOIN customers c ON r.customer_id = c.id
     JOIN tenants t   ON r.tenant_id = t.id
     WHERE r.deposit_manual_request = 1
       AND r.status = "pending"
       AND r.deposit_requested_at IS NOT NULL
       AND t.is_active = 1
       AND t.deposit_manual_window_minutes IS NOT NULL
       AND t.deposit_type IN ("stripe", "guarantee")
       AND (
            (t.deposit_type = "stripe"    AND r.deposit_paid = 0)
         OR (t.deposit_type = "guarantee" AND r.guarantee_status = "pending")
       )
       AND DATE_ADD(r.deposit_requested_at, INTERVAL t.deposit_manual_window_minutes MINUTE) < NOW()'
);
$stmt->execute();
$rows = $stmt->fetchAll();

echo "  Trovate " . count($rows) . " prenotazioni scadute.\n";

$reservationModel = new Reservation();
$logModel = new ReservationLog();
$cancelled = 0;
$errors = 0;

foreach ($rows as $row) {
    try {
        $reservationModel->updateStatus((int)$row['id'], 'cancelled', 'system');
        $logModel->create((int)$row['id'], 'pending', 'cancelled', null, 'Caparra non completata entro la finestra');

        $tenant = [
            'name'  => $row['tenant_name'],
            'slug'  => $row['slug'],
            'email' => $row['tenant_email'],
        ];
        MailService::sendReservationDepositExpired($row, $tenant);

        $cancelled++;
        app_log("Cron expire-manual-deposits: [OK] annullata #{$row['id']} ({$row['tenant_name']})", 'info');
        echo "    [OK] annullata #{$row['id']} — {$row['first_name']} {$row['last_name']}\n";
    } catch (\Throwable $e) {
        $errors++;
        app_log("Cron expire-manual-deposits: [ERR] #{$row['id']} — " . $e->getMessage(), 'error');
        echo "    [ERR] #{$row['id']} — " . $e->getMessage() . "\n";
    }
}

echo "[DONE] annullate: {$cancelled}, errori: {$errors}\n";
app_log("Cron expire-manual-deposits: done — annullate {$cancelled}, errori {$errors}", 'info');
