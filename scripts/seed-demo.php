<?php
/**
 * Popola/rigenera i DATI DEMO di un tenant vetrina (default: trattoria-genovese).
 *
 * Uso (locale):
 *   C:\xampp\php\php.exe scripts/seed-demo.php [slug] [--clean]
 *
 * Rolling: rigenera clienti + prenotazioni con date RELATIVE a oggi. I clienti
 * demo sono marcati (@demo.evulery.local): il refresh cancella solo quelli,
 * mai dati reali. Il setup (tavoli/menu/slot) e' idempotente. Vedi DemoSeeder.
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

// Carica .env (stesso pattern degli altri script CLI)
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            putenv(trim($k) . '=' . trim($v));
            $_ENV[trim($k)] = trim($v);
        }
    }
}
require_once BASE_PATH . '/app/Helpers/functions.php';

$slug = 'trattoria-genovese';
$cleanOnly = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--clean') {
        $cleanOnly = true;
    } elseif (strpos($arg, '--') !== 0) {
        $slug = $arg;
    }
}

try {
    $res = (new \App\Services\DemoSeeder())->run($slug, $cleanOnly);
    echo "OK\n" . json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "ERRORE: " . $e->getMessage() . "\n");
    exit(1);
}
