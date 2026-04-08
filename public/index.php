<?php

/**
 * Evulery.Pro 1.0 - Front Controller
 * All requests are routed through this file.
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

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

// Error handling
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

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
