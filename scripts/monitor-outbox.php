<?php
/**
 * Monitor della coda notifiche: avvisa se il worker sembra FERMO.
 *
 * Conta le email 'pending' GIA' DOVUTE (available_at <= NOW()) accodate da oltre
 * STUCK_MINUTES. Un worker sano le avrebbe inviate, o marcate 'failed' dopo i
 * retry (~20 min), ben prima: quindi se ce ne sono e' un segnale che il cron
 * process-outbox.php non sta girando. In tal caso manda un alert a SUPPORT_EMAIL.
 *
 * Cron consigliato (ogni 15 min):
 *   *\/15 * * * * /opt/cpanel/ea-php83/root/usr/bin/php /home/vpsevlrqrit/evulery/scripts/monitor-outbox.php >> /home/vpsevlrqrit/evulery/storage/logs/outbox.log 2>&1
 *
 * Opzioni:
 *   --dry-run   rileva e riporta SENZA inviare l'alert (per test)
 *
 * L'alert e' inviato in SINCRONO (transmit, MAI accodato) -> arriva anche se la
 * coda e' bloccata. Anti-spam: non rimanda l'alert se l'ultimo e' < SILENCE.
 * Limite noto: se a essere giu' e' l'SMTP stesso, anche l'alert fallisce (per
 * quel caso serve un heartbeat esterno, passo successivo).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

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
use App\Services\MailService;

const STUCK_MINUTES   = 60;   // pending dovute da oltre 60 min = worker probabilmente fermo
const SILENCE_MINUTES = 60;   // non rimandare l'alert prima di 60 min

$dryRun = in_array('--dry-run', $GLOBALS['argv'] ?? [], true);

$db = Database::getInstance();
$row = $db->query(
    "SELECT COUNT(*) AS n, MIN(created_at) AS oldest
     FROM notification_outbox
     WHERE status='pending' AND available_at <= NOW()
       AND created_at < DATE_SUB(NOW(), INTERVAL " . STUCK_MINUTES . " MINUTE)"
)->fetch(\PDO::FETCH_ASSOC);

$n = (int)($row['n'] ?? 0);

// Tutto ok: nessuna riga bloccata -> esci in silenzio (niente rumore nel log).
if ($n === 0) {
    if ($dryRun) echo "monitor-outbox (dry-run): nessuna email bloccata. OK.\n";
    exit(0);
}

$oldest = (string)($row['oldest'] ?? '?');

if ($dryRun) {
    echo sprintf("monitor-outbox (dry-run): ALERT -> %d email pending da >%dmin (più vecchia: %s). Non invio (dry-run).\n",
        $n, STUCK_MINUTES, $oldest);
    exit(0);
}

// Anti-spam: non rimandare l'alert se gia' inviato da meno di SILENCE_MINUTES.
$stateDir = BASE_PATH . '/storage/cache';
if (!is_dir($stateDir)) { @mkdir($stateDir, 0775, true); }
$stateFile = $stateDir . '/outbox-monitor.last';
$last = is_file($stateFile) ? (int)trim((string)@file_get_contents($stateFile)) : 0;

if ($last > 0 && (time() - $last) < SILENCE_MINUTES * 60) {
    echo sprintf("[%s] monitor-outbox: %d email bloccate da >%dmin, alert silenziato (ultimo %d min fa)\n",
        date('Y-m-d H:i:s'), $n, STUCK_MINUTES, (int)((time() - $last) / 60));
    exit(0);
}

$to = env('SUPPORT_EMAIL', 'info@evulery.it');
$subject = "[Evulery] Coda email ferma: {$n} in attesa da oltre " . STUCK_MINUTES . " min";
$html = "<p>Il worker della coda email (<code>process-outbox.php</code>) potrebbe essere <strong>fermo</strong>.</p>"
      . "<p><strong>{$n}</strong> email risultano in coda (<code>pending</code>, gi&agrave; dovute) da oltre " . STUCK_MINUTES . " minuti.<br>"
      . "Pi&ugrave; vecchia accodata il: <strong>" . htmlspecialchars($oldest) . "</strong>.</p>"
      . "<p>Verificare il cron <code>process-outbox.php</code> e il log <code>storage/logs/outbox.log</code>.</p>";

// Invio SINCRONO (transmit): NON accodare, deve partire anche con coda bloccata.
$sent = (new MailService())->transmit($to, $subject, $html);

if ($sent) {
    @file_put_contents($stateFile, (string)time());
}

$msg = sprintf("[%s] monitor-outbox: ALERT %d email bloccate da >%dmin, mail a %s = %s",
    date('Y-m-d H:i:s'), $n, STUCK_MINUTES, $to, $sent ? 'inviata' : 'FALLITA');
echo $msg . "\n";
app_log($msg, 'warning');
