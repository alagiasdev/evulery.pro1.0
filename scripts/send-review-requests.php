<?php
/**
 * Send review request emails to customers after their visit.
 *
 * Run every 15 minutes via Task Scheduler or cron:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\evulery.pro1.0\scripts\send-review-requests.php
 *
 * Logic:
 *   - Find reservations with status "arrived"
 *   - Tenant has review_enabled=1, review_url not empty
 *   - Reservation time + delay_hours < NOW
 *   - No existing review_request for this reservation
 *   - Customer not emailed in last 30 days (per tenant)
 *   - Customer not unsubscribed
 *   - Respect quiet hour
 */

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

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Rome');

require_once BASE_PATH . '/app/Helpers/functions.php';
require_once BASE_PATH . '/app/Helpers/view.php';

use App\Core\Database;
use App\Models\ReviewRequest;
use App\Models\Tenant;
use App\Services\MailService;

$db = Database::getInstance();

// Sync MySQL timezone
$phpTz = date('P');
$db->exec("SET time_zone = '{$phpTz}'");

$tenantModel = new Tenant();
$reviewModel = new ReviewRequest();
$now = date('Y-m-d H:i:s');
$currentHour = (int) date('G');
$sent = 0;
$skipped = 0;
$errors = 0;

app_log("Cron review-requests: starting at {$now}", 'info');
echo "[{$now}] Starting review request send...\n";

// Find eligible reservations:
// - status = arrived
// - tenant.review_enabled = 1
// - tenant.review_url is not empty
// - reservation time + delay < NOW
// - no existing review_request for this reservation
// - customer has email
// - customer not unsubscribed
$stmt = $db->prepare(
    "SELECT r.id AS reservation_id, r.reservation_date, r.reservation_time, r.party_size,
            c.id AS customer_id, c.first_name, c.last_name, c.email AS customer_email,
            c.unsubscribed,
            t.id AS tenant_id, t.name AS tenant_name, t.slug, t.email AS tenant_email,
            t.logo_url, t.review_url, t.review_platform_label,
            t.review_delay_hours, t.review_quiet_hour,
            t.review_filter_enabled, t.review_filter_threshold, t.review_filter_message,
            t.review_email_subject, t.review_email_body, t.review_email_cta
     FROM reservations r
     JOIN customers c ON r.customer_id = c.id
     JOIN tenants t ON r.tenant_id = t.id
     WHERE r.status = 'arrived'
       AND t.is_active = 1
       AND t.review_enabled = 1
       AND t.review_url IS NOT NULL AND t.review_url != ''
       AND c.email IS NOT NULL AND c.email != ''
       AND c.unsubscribed = 0
       AND TIMESTAMP(r.reservation_date, r.reservation_time) < DATE_SUB(NOW(), INTERVAL t.review_delay_hours HOUR)
       AND NOT EXISTS (
           SELECT 1 FROM review_requests rr WHERE rr.reservation_id = r.id
       )
     ORDER BY r.reservation_date, r.reservation_time
     LIMIT 50"
);
$stmt->execute();
$candidates = $stmt->fetchAll();

app_log("Cron review-requests: found " . count($candidates) . " candidates", 'info');
echo "  Found " . count($candidates) . " candidates.\n";

foreach ($candidates as $row) {
    $tenantId = (int) $row['tenant_id'];
    $customerId = (int) $row['customer_id'];
    $reservationId = (int) $row['reservation_id'];
    $label = "{$row['first_name']} {$row['last_name']} ({$row['customer_email']}) — res #{$reservationId}";

    // Service gate
    if (!$tenantModel->canUseService($tenantId, 'review_management')) {
        echo "    [SKIP] {$label} — tenant no review_management service\n";
        $skipped++;
        continue;
    }

    // Quiet hour check
    $quietHour = (int) ($row['review_quiet_hour'] ?? 22);
    if ($quietHour > 0 && $currentHour >= $quietHour) {
        echo "    [SKIP] {$label} — quiet hour ({$currentHour}:00 >= {$quietHour}:00)\n";
        $skipped++;
        continue;
    }

    // Rate limit: max 1 email per 30 days per customer per tenant
    $recentCount = $reviewModel->countRecentByCustomer($customerId, $tenantId, 30);
    if ($recentCount > 0) {
        echo "    [SKIP] {$label} — already emailed in last 30 days\n";
        $skipped++;
        continue;
    }

    // Create review_request + send email
    try {
        $token = bin2hex(random_bytes(32));
        $rrId = $reviewModel->create([
            'tenant_id'      => $tenantId,
            'reservation_id' => $reservationId,
            'customer_id'    => $customerId,
            'token'          => $token,
            'source'         => 'email',
            'sent_at'        => date('Y-m-d H:i:s'),
        ]);

        $reviewRequest = ['id' => $rrId, 'token' => $token];
        $tenant = [
            'id'   => $tenantId,
            'name' => $row['tenant_name'],
            'slug' => $row['slug'],
            'email' => $row['tenant_email'],
            'logo_url' => $row['logo_url'],
            'review_url' => $row['review_url'],
            'review_platform_label' => $row['review_platform_label'],
            'review_filter_enabled' => $row['review_filter_enabled'],
            'review_email_subject' => $row['review_email_subject'],
            'review_email_body' => $row['review_email_body'],
            'review_email_cta' => $row['review_email_cta'],
        ];
        $customer = [
            'id' => $customerId,
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['customer_email'],
        ];
        $reservation = [
            'id' => $reservationId,
            'reservation_date' => $row['reservation_date'],
            'reservation_time' => $row['reservation_time'],
            'party_size' => $row['party_size'],
        ];

        $ok = MailService::sendReviewRequest($reviewRequest, $tenant, $customer, $reservation);

        if ($ok) {
            $sent++;
            app_log("Cron review-requests: [OK] → {$row['customer_email']} — res #{$reservationId}", 'info');
            echo "    [OK] {$label}\n";
        } else {
            $errors++;
            app_log("Cron review-requests: [ERR] mail failed → {$row['customer_email']}", 'error');
            echo "    [ERR] mail failed → {$label}\n";
        }
    } catch (\Throwable $e) {
        $errors++;
        app_log("Cron review-requests: [ERR] exception → {$e->getMessage()}", 'error');
        echo "    [ERR] {$e->getMessage()}\n";
    }
}

// Summary
$summary = "sent: {$sent}, skipped: {$skipped}, errors: {$errors}";
app_log("Cron review-requests: DONE — {$summary}", 'info');
echo "\n[DONE] {$summary}\n";
