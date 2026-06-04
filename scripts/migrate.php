<?php
/**
 * Auto-migration script — wrapper CLI di App\Services\Migrator.
 *
 * Usage:
 *   php scripts/migrate.php                  Applica tutte le migration pending
 *   php scripts/migrate.php --status         Mostra applied/pending, non esegue nulla
 *   php scripts/migrate.php --target=NNN     Applica fino al filename contenente NNN
 *   php scripts/migrate.php --force-mark-all Marca TUTTE le pending come applicate (bootstrap)
 *   php scripts/migrate.php --force-mark-all --yes  Salta la conferma interattiva
 *
 * Per applicare migration dal browser invece di CLI:
 *   visita https://dash.evulery.it/admin/migrations
 *
 * Exit codes:
 *   0 = success
 *   1 = errore di esecuzione SQL (migration parzialmente applicata)
 *   2 = errore di setup (lock, connessione, file mancanti)
 *   3 = errore argomenti CLI
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

// Carica .env (stesso pattern degli altri script CLI)
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Rome');
require_once BASE_PATH . '/app/Helpers/functions.php';

use App\Services\Migrator;

// ============================================================================
// CLI args
// ============================================================================
$args = [];
foreach (array_slice($GLOBALS['argv'] ?? [], 1) as $a) {
    if (str_starts_with($a, '--')) {
        $a = substr($a, 2);
        if (str_contains($a, '=')) {
            [$k, $v] = explode('=', $a, 2);
            $args[$k] = $v;
        } else {
            $args[$a] = true;
        }
    }
}

try {
    $migrator = new Migrator();
} catch (Throwable $e) {
    out("ERR  Connessione DB fallita: " . $e->getMessage(), 'red');
    exit(2);
}

// ============================================================================
// Comando: --status
// ============================================================================
if (isset($args['status'])) {
    $s = $migrator->status();
    out("=== Migration status ===", 'cyan');
    out(sprintf("Applied: %d / %d  ·  Pending: %d", count($s['applied']), $s['total'], count($s['pending'])));
    if (!empty($s['pending'])) {
        out("\nPending:", 'yellow');
        foreach ($s['pending'] as $f) out("  - $f");
    }
    if (!empty($s['recent'])) {
        out("\nUltime applicate:", 'green');
        foreach ($s['recent'] as $r) out(sprintf("  ✓ %-50s @ %s (%dms)", $r['filename'], $r['applied_at'], $r['duration_ms']));
    }
    exit(0);
}

// ============================================================================
// Comando: --force-mark-all
// ============================================================================
if (isset($args['force-mark-all'])) {
    $s = $migrator->status();
    $nPending = count($s['pending']);
    if ($nPending === 0) {
        out("INFO Nessuna migration pending.", 'gray');
        exit(0);
    }
    out(sprintf("Sto per marcare %d migration come applicate SENZA eseguirle.", $nPending), 'yellow');
    if (!isset($args['yes'])) {
        echo "Procedo? (s/n) ";
        $line = trim((string)fgets(STDIN));
        if (!in_array(strtolower($line), ['s', 'si', 'y', 'yes'], true)) {
            out("Annullato.", 'gray');
            exit(0);
        }
    }
    $r = $migrator->forceMarkAll();
    if ($r['success']) {
        out(sprintf("✓ Fatto. %d migration marcate.", $r['marked']), 'green');
        exit(0);
    }
    out("ERR " . $r['error'], 'red');
    exit(2);
}

// ============================================================================
// Comando default: applica pending (o fino al target)
// ============================================================================
$target = (isset($args['target']) && $args['target'] !== true) ? (string)$args['target'] : null;

$s = $migrator->status();
$pending = $s['pending'];
if ($target !== null) {
    $idx = null;
    foreach ($pending as $i => $f) {
        if (str_contains($f, $target)) { $idx = $i; break; }
    }
    if ($idx === null) {
        out("ERR  Target '$target' non trovato tra le pending.", 'red');
        exit(3);
    }
    $pending = array_slice($pending, 0, $idx + 1);
}

if (empty($pending)) {
    out("✓ Tutto allineato. Nessuna migration da applicare.", 'green');
    exit(0);
}

out(sprintf("=== MIGRATE — %d migration da applicare ===", count($pending)), 'cyan');
$result = $migrator->applyPending($target);

foreach ($result['applied'] as $a) {
    out(sprintf("  ✓ %s applicata in %dms", $a['filename'], $a['duration_ms']), 'green');
}

if (!$result['success']) {
    if ($result['error_file']) {
        out(sprintf("  ✗ ERRORE su %s: %s", $result['error_file'], $result['error']), 'red');
        out("  IMPORTANTE: MySQL non supporta DDL transactional. La migration potrebbe", 'red');
        out("  essere stata applicata PARZIALMENTE. Verifica lo stato del DB a mano.", 'gray');
        exit(1);
    }
    out("  ✗ " . $result['error'], 'red');
    exit(2);
}

out(sprintf("\n✓ Fatto. %d migration applicate.", count($result['applied'])), 'green');
exit(0);

// ============================================================================
function out(string $msg, ?string $color = null): void
{
    static $colors = [
        'red' => "\033[31m", 'green' => "\033[32m", 'yellow' => "\033[33m",
        'cyan' => "\033[36m", 'gray' => "\033[90m", 'reset' => "\033[0m",
    ];
    $useColor = $color !== null && (PHP_OS_FAMILY !== 'Windows' || getenv('ANSICON') || getenv('WT_SESSION'));
    echo ($useColor ? $colors[$color] : '') . $msg . ($useColor ? $colors['reset'] : '') . PHP_EOL;
}
