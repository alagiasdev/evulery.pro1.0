<?php
/**
 * Send queued email broadcast campaigns via TurboSMTP.
 *
 * Run every 5 minutes via Task Scheduler or cron:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\evulery.pro1.0\scripts\send-broadcasts.php
 *
 * Logic:
 *   - Picks up campaigns with status='queued'
 *   - Sends emails in batches of 50 with 100ms delay between each
 *   - Updates recipient and campaign statuses
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
use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Services\BroadcastService;

$db = Database::getInstance();

// Sync MySQL timezone with PHP timezone
$phpTz = date('P');
$db->exec("SET time_zone = '{$phpTz}'");

$campaignModel = new EmailCampaign();
$tenantModel = new Tenant();
$now = date('Y-m-d H:i:s');
$totalSent = 0;
$totalFailed = 0;

app_log("Cron broadcast: starting at {$now}", 'info');
echo "[{$now}] Starting broadcast send...\n";

// Find queued campaigns
$campaigns = $campaignModel->findQueued();
echo "  Found " . count($campaigns) . " queued campaign(s).\n";

if (empty($campaigns)) {
    echo "[DONE] Nothing to send.\n";
    exit(0);
}

// Create a single mailer instance (reused for all emails)
$mailer = BroadcastService::createBroadcastMailer();

foreach ($campaigns as $campaign) {
    $campaignId = (int)$campaign['id'];
    $tenantId = (int)$campaign['tenant_id'];
    $subject = $campaign['subject'];

    echo "\n  Campaign #{$campaignId}: \"{$subject}\"\n";

    // Load tenant
    $stmt = $db->prepare('SELECT * FROM tenants WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $tenantId]);
    $tenant = $stmt->fetch();

    if (!$tenant) {
        echo "    [SKIP] Tenant #{$tenantId} not found or inactive.\n";
        $campaignModel->updateStatus($campaignId, 'failed');
        continue;
    }

    // Service gate check
    if (!$tenantModel->canUseService($tenantId, 'email_broadcast')) {
        echo "    [SKIP] Tenant '{$tenant['name']}' plan does not include email_broadcast.\n";
        $campaignModel->updateStatus($campaignId, 'failed');
        continue;
    }

    // Mark as sending
    $campaignModel->updateStatus($campaignId, 'sending');

    $sent = 0;
    $failed = 0;

    // Process recipients in batches of 50
    while (true) {
        $recipients = $campaignModel->getPendingRecipients($campaignId, 50);
        if (empty($recipients)) break;

        foreach ($recipients as $recipient) {
            $recipientId = (int)$recipient['id'];
            $email = $recipient['email'];

            // Generate unsubscribe token and URL
            $token = BroadcastService::generateUnsubscribeToken($tenantId, $email);
            $unsubscribeUrl = rtrim(env('APP_URL', ''), '/') . '/email/unsubscribe/' . $token;

            // Build HTML
            $html = BroadcastService::buildEmailHtml($campaign['body_text'], $tenant, $unsubscribeUrl);

            // Send
            $ok = BroadcastService::sendOne(
                $mailer,
                $email,
                $subject,
                $html,
                $tenant['name'],
                $tenant['email'] ?? null
            );

            if ($ok) {
                $campaignModel->updateRecipientStatus($recipientId, 'sent');
                $sent++;
                echo "    [OK] {$email}\n";
            } else {
                $campaignModel->updateRecipientStatus($recipientId, 'failed');
                $failed++;
                echo "    [ERR] {$email}\n";
            }

            // Rate limiting: 100ms between emails (10/sec max)
            usleep(100000);
        }
    }

    // Update campaign counts and status
    $campaignModel->updateCounts($campaignId, $sent, $failed);

    // Mark as sent
    $db->prepare('UPDATE email_campaigns SET status = :status, sent_at = NOW() WHERE id = :id')
       ->execute(['status' => 'sent', 'id' => $campaignId]);

    echo "    Completed: {$sent} sent, {$failed} failed.\n";

    $totalSent += $sent;
    $totalFailed += $failed;
}

echo "\n[DONE] Total sent: {$totalSent}, total failed: {$totalFailed}\n";
