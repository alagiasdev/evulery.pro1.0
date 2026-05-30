<?php
/**
 * Auto-migration script — applica file SQL in database/migrations/ in ordine.
 *
 * Usage:
 *   php scripts/migrate.php                  Applica tutte le migration pending
 *   php scripts/migrate.php --status         Mostra applied/pending, non esegue nulla
 *   php scripts/migrate.php --dry-run        Mostra cosa applicherebbe, non tocca il DB
 *   php scripts/migrate.php --target=057     Applica fino al filename con "057" incluso
 *   php scripts/migrate.php --force-mark=057 Marca una migration come applicata SENZA eseguirla
 *   php scripts/migrate.php --force-mark-all Marca TUTTE le pending come applicate (bootstrap)
 *   php scripts/migrate.php --force-mark-all --yes  Salta la conferma interattiva
 *
 * Pattern:
 *   - Tabella `migrations(filename, applied_at, duration_ms, checksum)` traccia lo stato
 *   - File scansionati in ordine alfanumerico da database/migrations/*.sql
 *   - Forward-only: niente rollback automatico (DDL MySQL non e' transactional)
 *   - GET_LOCK per evitare run concorrenti
 *   - Idempotente: rilanciandolo non succede nulla se gia' tutto applicato
 *
 * Exit codes:
 *   0 = success
 *   1 = errore di esecuzione SQL (migration parzialmente applicata, va sistemata a mano)
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

use App\Core\Database;

// ============================================================================
// CLI args parsing
// ============================================================================
$args = parseArgs($GLOBALS['argv'] ?? []);

if (isset($args['help'])) {
    echo file_get_contents(__FILE__, false, null, 0, 1800);
    exit(0);
}

// ============================================================================
// Connessione + lock
// ============================================================================
try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    out("ERR  Connessione DB fallita: " . $e->getMessage(), 'red');
    exit(2);
}

if (!acquireLock($pdo)) {
    out("ERR  Un'altra istanza di migrate sta gia' girando (lock 'evulery_migrate' busy).", 'red');
    out("     Se sei sicuro che non sia cosi', riprova tra 10 secondi.", 'gray');
    exit(2);
}

try {
    ensureMigrationsTable($pdo);

    $migrationsDir = BASE_PATH . '/database/migrations';
    $allFiles = scanMigrationFiles($migrationsDir);
    $applied  = fetchAppliedSet($pdo);
    $pending  = array_values(array_filter($allFiles, fn($f) => !isset($applied[$f])));

    // ========================================================================
    // Comando: --status
    // ========================================================================
    if (isset($args['status'])) {
        out("=== Migration status ===", 'cyan');
        out(sprintf("Directory: %s", $migrationsDir));
        out(sprintf("Applied:   %d / %d", count($applied), count($allFiles)));
        out(sprintf("Pending:   %d", count($pending)));
        if (count($pending) > 0) {
            out("\nPending:", 'yellow');
            foreach ($pending as $f) out("  - $f");
        }
        if (count($applied) > 0) {
            $rows = $pdo->query("SELECT filename, applied_at, duration_ms FROM migrations ORDER BY applied_at DESC LIMIT 5")->fetchAll();
            out("\nUltime 5 applicate:", 'green');
            foreach ($rows as $r) out(sprintf("  ✓ %-50s @ %s  (%dms)", $r['filename'], $r['applied_at'], $r['duration_ms']));
        }
        releaseLock($pdo);
        exit(0);
    }

    // ========================================================================
    // Comando: --force-mark=NNN  (marca una migration senza eseguirla)
    // ========================================================================
    if (isset($args['force-mark']) && $args['force-mark'] !== true) {
        $needle = (string)$args['force-mark'];
        $match  = array_values(array_filter($allFiles, fn($f) => str_contains($f, $needle)));
        if (count($match) === 0) {
            out("ERR  Nessun file matcha '$needle' in $migrationsDir", 'red');
            releaseLock($pdo);
            exit(3);
        }
        if (count($match) > 1) {
            out("ERR  Match ambiguo per '$needle': " . implode(', ', $match), 'red');
            releaseLock($pdo);
            exit(3);
        }
        $f = $match[0];
        if (isset($applied[$f])) {
            out("INFO $f gia' applicata. Nessuna azione.", 'gray');
        } else {
            markApplied($pdo, $migrationsDir . '/' . $f, $f, 0);
            out("✓    $f marcata come applicata (NON eseguita).", 'green');
        }
        releaseLock($pdo);
        exit(0);
    }

    // ========================================================================
    // Comando: --force-mark-all  (bootstrap: marca tutte le pending)
    // ========================================================================
    if (isset($args['force-mark-all'])) {
        if (count($pending) === 0) {
            out("INFO Nessuna migration pending. Niente da marcare.", 'gray');
            releaseLock($pdo);
            exit(0);
        }
        out(sprintf("Sto per marcare %d migration come applicate SENZA eseguirle.", count($pending)), 'yellow');
        out("Usare solo se il DB e' GIA' allineato con questi file (es. DB locale gia' popolato a mano).", 'gray');
        if (!isset($args['yes']) && !confirm("Procedo?")) {
            out("Annullato.", 'gray');
            releaseLock($pdo);
            exit(0);
        }
        foreach ($pending as $f) {
            markApplied($pdo, $migrationsDir . '/' . $f, $f, 0);
            out("✓    $f marcata", 'green');
        }
        out(sprintf("\nFatto. %d migration marcate.", count($pending)), 'cyan');
        releaseLock($pdo);
        exit(0);
    }

    // ========================================================================
    // Comando default / --dry-run / --target=NNN
    // ========================================================================
    if (count($pending) === 0) {
        out("✓ Tutto allineato. Nessuna migration da applicare.", 'green');
        releaseLock($pdo);
        exit(0);
    }

    // Filtro --target
    $targetFilter = null;
    if (isset($args['target']) && $args['target'] !== true) {
        $needle = (string)$args['target'];
        $matchIdx = null;
        foreach ($pending as $i => $f) {
            if (str_contains($f, $needle)) { $matchIdx = $i; break; }
        }
        if ($matchIdx === null) {
            out("ERR  Target '$needle' non trovato tra le pending.", 'red');
            releaseLock($pdo);
            exit(3);
        }
        $pending = array_slice($pending, 0, $matchIdx + 1);
    }

    $dryRun = isset($args['dry-run']);

    out(sprintf("=== %s — %d migration da %s ===", $dryRun ? 'DRY RUN' : 'MIGRATE', count($pending), $dryRun ? 'simulare' : 'applicare'), 'cyan');

    $okCount = 0;
    foreach ($pending as $f) {
        $path = $migrationsDir . '/' . $f;
        $size = filesize($path);
        if ($dryRun) {
            out(sprintf("WOULD APPLY  %-55s (%s bytes)", $f, number_format($size)), 'yellow');
            $okCount++;
            continue;
        }

        out(sprintf("→ %s ...", $f), 'cyan');
        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            out(sprintf("  ✗ File vuoto o illeggibile, ABORT.", $f), 'red');
            releaseLock($pdo);
            exit(1);
        }

        $t0 = microtime(true);
        try {
            // PDO::exec supporta multi-statement nativamente su MySQL.
            // Importante: se uno statement intermedio fallisce, gli statement
            // precedenti restano applicati (DDL non-transactional in MySQL).
            // NB: le migration sono DDL+INSERT, niente SELECT che lasci result-set
            // pendenti. Se in futuro servisse, drainare con PDOStatement::nextRowset()
            // dopo $pdo->query() — non $pdo->exec() che non ritorna Statement.
            $pdo->exec($sql);
        } catch (Throwable $e) {
            $ms = (int)((microtime(true) - $t0) * 1000);
            out(sprintf("  ✗ ERRORE dopo %dms: %s", $ms, $e->getMessage()), 'red');
            out("  IMPORTANTE: la migration potrebbe essere stata applicata PARZIALMENTE.", 'red');
            out("  MySQL non supporta DDL transactional. Verifica lo stato del DB a mano.", 'gray');
            out("  Dopo la fix, rilancia (l_idempotenza dello script salta le precedenti).", 'gray');
            releaseLock($pdo);
            exit(1);
        }
        $ms = (int)((microtime(true) - $t0) * 1000);
        markApplied($pdo, $path, $f, $ms);
        out(sprintf("  ✓ applicata in %dms", $ms), 'green');
        $okCount++;
    }

    out("");
    if ($dryRun) {
        out(sprintf("DRY RUN ok. %d migration sarebbero state applicate. Niente toccato.", $okCount), 'cyan');
    } else {
        out(sprintf("✓ Fatto. %d migration applicate.", $okCount), 'green');
    }

    releaseLock($pdo);
    exit(0);

} catch (Throwable $e) {
    out("ERR  Eccezione non gestita: " . $e->getMessage(), 'red');
    out($e->getTraceAsString(), 'gray');
    releaseLock($pdo);
    exit(2);
}

// ============================================================================
// Helpers
// ============================================================================

function parseArgs(array $argv): array
{
    $out = [];
    foreach (array_slice($argv, 1) as $a) {
        if (str_starts_with($a, '--')) {
            $a = substr($a, 2);
            if (str_contains($a, '=')) {
                [$k, $v] = explode('=', $a, 2);
                $out[$k] = $v;
            } else {
                $out[$a] = true;
            }
        }
    }
    return $out;
}

function ensureMigrationsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            filename     VARCHAR(255) NOT NULL PRIMARY KEY,
            applied_at   DATETIME     NOT NULL,
            duration_ms  INT UNSIGNED NOT NULL DEFAULT 0,
            checksum     VARCHAR(64)  DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
}

function scanMigrationFiles(string $dir): array
{
    if (!is_dir($dir)) {
        out("ERR  Directory migration non trovata: $dir", 'red');
        exit(2);
    }
    $files = glob($dir . '/*.sql');
    $files = array_map('basename', $files);
    sort($files, SORT_STRING);
    return $files;
}

function fetchAppliedSet(PDO $pdo): array
{
    $rows = $pdo->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
    return array_flip($rows);
}

function markApplied(PDO $pdo, string $path, string $filename, int $durationMs): void
{
    $checksum = is_file($path) ? hash_file('sha256', $path) : null;
    $stmt = $pdo->prepare("
        INSERT INTO migrations (filename, applied_at, duration_ms, checksum)
        VALUES (?, NOW(), ?, ?)
    ");
    $stmt->execute([$filename, $durationMs, $checksum]);
}

function acquireLock(PDO $pdo): bool
{
    // GET_LOCK ritorna 1 se acquisito, 0 se timeout, NULL se errore
    $r = $pdo->query("SELECT GET_LOCK('evulery_migrate', 5)")->fetchColumn();
    return $r === '1' || $r === 1;
}

function releaseLock(PDO $pdo): void
{
    try { $pdo->query("SELECT RELEASE_LOCK('evulery_migrate')"); } catch (Throwable $e) { /* ignore */ }
}

function out(string $msg, ?string $color = null): void
{
    static $colors = [
        'red' => "\033[31m", 'green' => "\033[32m", 'yellow' => "\033[33m",
        'cyan' => "\033[36m", 'gray' => "\033[90m", 'reset' => "\033[0m",
    ];
    // Disabilita colori su Windows cmd legacy (non li interpreta)
    $useColor = $color !== null && (PHP_OS_FAMILY !== 'Windows' || getenv('ANSICON') || getenv('WT_SESSION'));
    if ($useColor) echo $colors[$color] . $msg . $colors['reset'] . PHP_EOL;
    else          echo $msg . PHP_EOL;
}

function confirm(string $question): bool
{
    echo $question . ' (s/n) ';
    $line = trim((string)fgets(STDIN));
    return in_array(strtolower($line), ['s', 'si', 'y', 'yes'], true);
}