<?php
/**
 * Worker della coda notifiche (notification_outbox).
 *
 * Spedisce le email accodate da MailService quando MAIL_ASYNC=1. Pensato per
 * girare OGNI MINUTO via cron; internamente fa polling ~5s per ~55s, cosi' le
 * email partono entro pochi secondi senza bisogno di un cron sub-minuto:
 *   * * * * *  /usr/bin/php /path/scripts/process-outbox.php >> /path/storage/logs/outbox.log 2>&1
 *
 * Opzioni:
 *   --once   esegue un solo giro e termina (utile per test / run manuali)
 *
 * Concorrenza: lock flock (single instance). Se un'altra istanza gira, esce.
 * Invio: usa MailService::transmit() (SMTP sincrono), MAI send() -> niente loop.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

// ---- Bootstrap (come gli altri cron) ----
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

// ---- Parametri ----
const OUTBOX_BATCH        = 20;   // righe per giro
const OUTBOX_RUN_SECONDS  = 55;   // durata del processo (sotto il minuto del cron)
const OUTBOX_POLL_SECONDS = 5;    // attesa tra un giro e l'altro
const OUTBOX_BACKOFF_MIN  = 2;    // backoff = attempts * 2 minuti
const OUTBOX_KEEP_DAYS    = 7;    // retention righe 'sent'

$once = in_array('--once', $GLOBALS['argv'] ?? [], true);

// ---- Lock: una sola istanza ----
$lockDir = BASE_PATH . '/storage';
if (!is_dir($lockDir)) { @mkdir($lockDir, 0775, true); }
$lockHandle = fopen($lockDir . '/process-outbox.lock', 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    // Un'altra istanza sta gia' girando: esci senza fare nulla.
    exit(0);
}

$db = Database::getInstance();

// Pulizia righe gia' inviate piu' vecchie della retention (una volta per run).
try {
    $db->prepare("DELETE FROM notification_outbox WHERE status='sent' AND sent_at < DATE_SUB(NOW(), INTERVAL :d DAY)")
       ->execute(['d' => OUTBOX_KEEP_DAYS]);
} catch (\Throwable $e) {
    app_log('process-outbox cleanup error: ' . $e->getMessage(), 'warning');
}

$totSent = 0;
$totFailed = 0;

/**
 * Processa un lotto di email dovute. Ritorna [sent, failed].
 */
$processBatch = function (\PDO $db) {
    $sent = 0; $failed = 0;
    $rows = $db->query(
        "SELECT id, channel, payload, attempts, max_attempts
         FROM notification_outbox
         WHERE status='pending' AND available_at <= NOW()
         ORDER BY id ASC
         LIMIT " . OUTBOX_BATCH
    )->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        // Fase 1: solo email. Il push (channel='push') arriva in Fase 2.
        if ($row['channel'] !== 'email') {
            continue;
        }

        $ok = false;
        $err = null;
        $p = json_decode((string)$row['payload'], true);

        if (is_array($p) && !empty($p['to'])) {
            try {
                $ok = (new MailService())->transmit(
                    $p['to'],
                    $p['subject'] ?? '',
                    $p['html'] ?? '',
                    $p['from_name'] ?? null,
                    $p['reply_to'] ?? null
                );
                if (!$ok) {
                    $err = 'invio SMTP fallito (dettaglio in app_log)';
                }
            } catch (\Throwable $e) {
                $err = $e->getMessage();
            }
        } else {
            $err = 'payload non valido';
        }

        if ($ok) {
            $db->prepare("UPDATE notification_outbox SET status='sent', sent_at=NOW(), last_error=NULL WHERE id=:id")
               ->execute(['id' => $row['id']]);
            $sent++;
        } else {
            $attempts = (int)$row['attempts'] + 1;
            $max = (int)$row['max_attempts'];
            if ($attempts >= $max) {
                $db->prepare("UPDATE notification_outbox SET status='failed', attempts=:a, last_error=:e WHERE id=:id")
                   ->execute(['a' => $attempts, 'e' => $err, 'id' => $row['id']]);
            } else {
                // Resta 'pending' ma rimandata: backoff = attempts * 2 minuti.
                $db->prepare(
                    "UPDATE notification_outbox
                     SET attempts=:a, available_at=DATE_ADD(NOW(), INTERVAL :b MINUTE), last_error=:e
                     WHERE id=:id"
                )->execute(['a' => $attempts, 'b' => $attempts * OUTBOX_BACKOFF_MIN, 'e' => $err, 'id' => $row['id']]);
            }
            $failed++;
        }
    }
    return [$sent, $failed];
};

// ---- Esecuzione: un giro (--once) oppure loop ~55s ----
$deadline = time() + OUTBOX_RUN_SECONDS;
do {
    [$s, $f] = $processBatch($db);
    $totSent += $s;
    $totFailed += $f;

    if ($once || time() >= $deadline) {
        break;
    }
    sleep(OUTBOX_POLL_SECONDS);
} while (true);

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

// Logga solo se c'e' stata attivita': i giri a vuoto (ogni minuto) non scrivono
// nulla, cosi' outbox.log resta pulito e non cresce inutilmente.
if ($totSent > 0 || $totFailed > 0) {
    $line = sprintf('[%s] process-outbox: inviate=%d, fallite=%d', date('Y-m-d H:i:s'), $totSent, $totFailed);
    echo $line . "\n";
    if ($totFailed > 0) {
        app_log($line, 'warning');
    }
}
