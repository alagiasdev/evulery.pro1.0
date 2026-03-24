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
 * Check if current tenant's plan includes a service.
 */
function tenant_can(string $serviceKey): bool
{
    $t = tenant();
    if (!$t) {
        return false;
    }
    return (new \App\Models\Tenant())->canUseService((int)$t['id'], $serviceKey);
}

/**
 * Gate a service: if tenant can't use it, flash warning and redirect to dashboard.
 * Returns true if blocked (caller should return immediately).
 */
function gate_service(string $serviceKey, ?string $redirectUrl = null): bool
{
    if (!tenant_can($serviceKey)) {
        flash('warning', 'Questa funzionalità non è inclusa nel tuo piano. Contatta il supporto per un upgrade.');
        \App\Core\Response::redirect($redirectUrl ?? url('dashboard'));
        return true;
    }
    return false;
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
    $ts = strtotime($date);

    // Fast path: nessun token testuale → niente da tradurre
    if (!preg_match('/[DlMF]/', $format)) {
        return date($format, $ts);
    }

    $dayIdx = (int)date('N', $ts) - 1; // 0=Lun, 6=Dom
    $monIdx = (int)date('n', $ts) - 1;  // 0=Gen, 11=Dic

    static $days     = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    static $daysFull = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
    static $months     = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
    static $monthsFull = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];

    // Sostituisci i token testuali con placeholder sicuri prima di date()
    // poi rimpiazza i placeholder con i nomi italiani (evita collisioni strtr)
    $swaps = [];
    $fmt = $format;

    if (strpos($fmt, 'l') !== false) {
        $fmt = str_replace('l', '##_1_##', $fmt);
        $swaps['##_1_##'] = $daysFull[$dayIdx];
    }
    if (strpos($fmt, 'D') !== false) {
        $fmt = str_replace('D', '##_2_##', $fmt);
        $swaps['##_2_##'] = $days[$dayIdx];
    }
    if (strpos($fmt, 'F') !== false) {
        $fmt = str_replace('F', '##_3_##', $fmt);
        $swaps['##_3_##'] = $monthsFull[$monIdx];
    }
    if (strpos($fmt, 'M') !== false) {
        $fmt = str_replace('M', '##_4_##', $fmt);
        $swaps['##_4_##'] = $months[$monIdx];
    }

    $result = date($fmt, $ts);
    return strtr($result, $swaps);
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
 * Translate user role to Italian
 */
function role_label(string $role): string
{
    return match (strtolower($role)) {
        'owner'       => 'Proprietario',
        'admin'       => 'Amministratore',
        'superadmin'  => 'Super Admin',
        'staff'       => 'Staff',
        'manager'     => 'Manager',
        default       => ucfirst($role),
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
 * Generate or retrieve CSP nonce for this request
 */
function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
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
