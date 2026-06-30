<?php

/**
 * Evulery.Pro 1.0 - Front Controller
 * All requests are routed through this file.
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Logger di prima linea per i fatal "fantasma": cattura gli errori che NON
// arrivano all'exception handler dell'app (memoria esaurita, timeout, parse
// error, anche un autoload corrotto), che altrimenti danno 500 senza traccia.
// E' autonomo (scrive da solo, senza dipendere dalle classi) e registrato
// PRIMA dell'autoload, cosi' intercetta anche i fatal di bootstrap.
// mem_peak e' la spia chiave: se vicino al memory_limit -> OOM.
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e === null || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        return;
    }
    $line = sprintf(
        "[%s] [FATAL type=%d] %s in %s:%d | uri=%s method=%s mem_peak=%.1fMB remote=%s\n",
        date('Y-m-d H:i:s'),
        $e['type'],
        $e['message'],
        $e['file'],
        $e['line'],
        $_SERVER['REQUEST_URI'] ?? '-',
        $_SERVER['REQUEST_METHOD'] ?? '-',
        memory_get_peak_usage(true) / 1048576,
        $_SERVER['REMOTE_ADDR'] ?? '-'
    );
    @file_put_contents(BASE_PATH . '/storage/logs/fatal-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
});

// Load Composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Load .env file
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// CORS preflight for API (landing page cross-origin requests)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' && str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = ['https://evulery.it', 'https://www.evulery.it', 'http://localhost'];
    if (in_array($origin, $allowed)) {
        header("Access-Control-Allow-Origin: {$origin}");
    }
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

// Error handling. APP_DEBUG e' una stringa "true"/"false" in .env, va castato
// in bool — env() ritorna la stringa come-e', e "false" e' truthy in PHP.
$appDebug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
if ($appDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    // In prod: cattura warning/error tramite handler ma sopprime visualizzazione.
    // Notice/Deprecated/Strict esclusi per non spammare i log con rumore legacy.
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
    ini_set('display_errors', '0');
}

// PHP warning/error handler: cattura warning runtime che prima erano persi
// nel log di sistema. Rispetta il @-suppression operator (error_reporting()
// torna 0 durante l'errore se chiamante usa @file_get_contents ecc.).
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false; // errore @-soppresso o filtrato da error_reporting → lascia stare
    }
    $labels = [
        E_WARNING           => 'php_warning',
        E_USER_WARNING      => 'php_warning',
        E_RECOVERABLE_ERROR => 'php_error',
        E_USER_ERROR        => 'php_error',
        E_CORE_WARNING      => 'php_warning',
        E_COMPILE_WARNING   => 'php_warning',
    ];
    $label = $labels[$severity] ?? 'php';
    app_log("[{$label}] {$message} in {$file}:{$line}", 'warning');
    return true; // intercettato, non chiamare default handler
});

// Set error handler
set_exception_handler(function (\Throwable $e) {
    app_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'error');
    \App\Core\Response::serverError($e->getMessage());
});

// Boot and run application
try {
    $app = new \App\Core\App();
    $app->boot();
    $app->run();
} catch (\Throwable $e) {
    app_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'error');
    \App\Core\Response::serverError($e->getMessage());
}
