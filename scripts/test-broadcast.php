<?php
/**
 * Test broadcast script — call via browser or CLI to diagnose issues.
 * DELETE THIS FILE after debugging!
 */
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== BROADCAST DIAGNOSTIC ===\n\n";

// 1. Check SMTP config
echo "1. SMTP CONFIG:\n";
$smtpVars = ['BROADCAST_SMTP_HOST', 'BROADCAST_SMTP_PORT', 'BROADCAST_SMTP_USERNAME', 'BROADCAST_SMTP_ENCRYPTION', 'BROADCAST_SMTP_FROM', 'BROADCAST_SMTP_FROM_NAME'];
foreach ($smtpVars as $v) {
    $val = env($v, '');
    echo "   {$v} = " . ($val ? $val : '[MISSING]') . "\n";
}
$hasPwd = !empty(env('BROADCAST_SMTP_PASSWORD', ''));
echo "   BROADCAST_SMTP_PASSWORD = " . ($hasPwd ? '[SET]' : '[MISSING]') . "\n";

// 2. Check queued campaigns
echo "\n2. QUEUED CAMPAIGNS:\n";
$db = App\Core\Database::getInstance();
$campaigns = $db->query("SELECT id, tenant_id, subject, status, total_recipients, sent_count, failed_count, created_at FROM email_campaigns ORDER BY id DESC LIMIT 10")->fetchAll();
if (empty($campaigns)) {
    echo "   No campaigns found.\n";
} else {
    foreach ($campaigns as $c) {
        echo "   #{$c['id']} tenant={$c['tenant_id']} status={$c['status']} subj=\"{$c['subject']}\" total={$c['total_recipients']} sent={$c['sent_count']} failed={$c['failed_count']} created={$c['created_at']}\n";
    }
}

// 3. Check pending recipients for queued campaigns
$queued = $db->query("SELECT id FROM email_campaigns WHERE status = 'queued'")->fetchAll();
echo "\n3. PENDING RECIPIENTS:\n";
if (empty($queued)) {
    echo "   No queued campaigns.\n";
} else {
    foreach ($queued as $q) {
        $pending = $db->prepare("SELECT COUNT(*) FROM email_campaign_recipients WHERE campaign_id = :cid AND status = 'pending'");
        $pending->execute(['cid' => $q['id']]);
        $count = $pending->fetchColumn();
        echo "   Campaign #{$q['id']}: {$count} pending recipients\n";
    }
}

// 4. Service gate check
echo "\n4. SERVICE GATE:\n";
$tenantModel = new App\Models\Tenant();
$tenants = $db->query("SELECT id, name FROM tenants WHERE is_active = 1")->fetchAll();
foreach ($tenants as $t) {
    $canBroadcast = $tenantModel->canUseService((int)$t['id'], 'email_broadcast');
    echo "   Tenant #{$t['id']} ({$t['name']}): email_broadcast = " . ($canBroadcast ? 'YES' : 'NO') . "\n";
}

// 5. Mailer creation test (no connection attempt — avoids timeout)
echo "\n5. MAILER TEST:\n";
try {
    $mailer = App\Services\BroadcastService::createBroadcastMailer();
    echo "   Mailer created OK.\n";
    echo "   Host: " . $mailer->Host . "\n";
    echo "   Port: " . $mailer->Port . "\n";
    echo "   SMTPSecure: " . $mailer->SMTPSecure . "\n";
    echo "   Username: " . $mailer->Username . "\n";
    echo "   From: " . $mailer->From . "\n";
} catch (\Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END ===\n";
