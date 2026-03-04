<?php

/**
 * Get environment variable with optional default
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}

/**
 * Generate full URL from path
 */
function url(string $path = ''): string
{
    $base = rtrim(env('APP_URL', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Generate asset URL
 */
function asset(string $path): string
{
    $cleanPath = ltrim($path, '/');
    $filePath = BASE_PATH . '/public/assets/' . $cleanPath;
    $version = file_exists($filePath) ? filemtime($filePath) : '';
    return url('assets/' . $cleanPath) . ($version ? '?v=' . $version : '');
}

/**
 * Escape output for HTML
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get old form input value (after validation failure)
 */
function old(string $key, string $default = ''): string
{
    return e(\App\Core\Session::getFlash('old_input')[$key] ?? $default);
}

/**
 * Generate CSRF hidden field
 */
function csrf_field(): string
{
    return \App\Core\CSRF::field();
}

/**
 * Get CSRF token
 */
function csrf_token(): string
{
    return \App\Core\CSRF::token();
}

/**
 * Get current authenticated user
 */
function auth(): ?array
{
    return \App\Core\Auth::user();
}

/**
 * Get current tenant
 */
function tenant(): ?array
{
    return \App\Core\TenantResolver::current();
}

/**
 * Flash a session message
 */
function flash(string $type, string $message): void
{
    \App\Core\Session::flash('alert_type', $type);
    \App\Core\Session::flash('alert_message', $message);
}

/**
 * Format date to Italian
 */
function format_date(string $date, string $format = 'd/m/Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Format time
 */
function format_time(string $time): string
{
    return date('H:i', strtotime($time));
}

/**
 * Translate reservation status to Italian
 */
function status_label(string $status): string
{
    return match ($status) {
        'confirmed'  => 'Confermata',
        'pending'    => 'In attesa',
        'arrived'    => 'Arrivato',
        'noshow'     => 'No-show',
        'cancelled'  => 'Annullata',
        default      => $status,
    };
}

/**
 * Get Bootstrap badge class for reservation status
 */
function status_badge(string $status): string
{
    return match ($status) {
        'confirmed'  => 'bg-success',
        'pending'    => 'bg-warning text-dark',
        'arrived'    => 'bg-primary',
        'noshow'     => 'bg-danger',
        'cancelled'  => 'bg-secondary',
        default      => 'bg-light text-dark',
    };
}

/**
 * Generate a URL-friendly slug
 */
function slugify(string $text): string
{
    $text = mb_strtolower($text);
    $text = preg_replace('/[àáâãäå]/u', 'a', $text);
    $text = preg_replace('/[èéêë]/u', 'e', $text);
    $text = preg_replace('/[ìíîï]/u', 'i', $text);
    $text = preg_replace('/[òóôõö]/u', 'o', $text);
    $text = preg_replace('/[ùúûü]/u', 'u', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Log message to storage/logs
 */
function app_log(string $message, string $level = 'info'): void
{
    $logFile = BASE_PATH . '/storage/logs/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
