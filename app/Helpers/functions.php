<?php

/**
 * Get environment variable with optional default
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}

/**
 * Return a Bootstrap Icons-compatible SVG inline, for pages that don't
 * load the full Bootstrap Icons font (auth pages). Same visual shape.
 * Scales with font-size via width/height 1em; color via currentColor.
 */
function bi_icon(string $name, string $class = 'icon'): string
{
    static $svgs = [
        'envelope' => '<path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/>',
        'envelope-open' => '<path d="M8.47 1.318a1 1 0 0 0-.94 0l-6 3.2A1 1 0 0 0 1 5.4v.817l5.75 3.45L8 8.917l1.25.75L15 6.217V5.4a1 1 0 0 0-.53-.882l-6-3.2ZM15 7.383l-4.778 2.867L15 13.117v-5.734Zm-.035 6.88L8 10.082l-6.965 4.18A1 1 0 0 0 2 15h12a1 1 0 0 0 .965-.738ZM1 13.116l4.778-2.867L1 7.383v5.734ZM7.059.435a2 2 0 0 1 1.882 0l6 3.2A2 2 0 0 1 16 5.4V14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V5.4a2 2 0 0 1 1.059-1.765l6-3.2Z"/>',
        'lock' => '<path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>',
        'eye' => '<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>',
        'eye-slash' => '<path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709z"/><path d="m10.79 12.912-.387-.387-.77-.77-.822-.823-1.326-1.326-.822-.822-.823-.823-.77-.77-.387-.388L2.354 1.646l.708-.708 12 12-.708.708-3.564-3.564zM1 1l14 14"/>',
        'box-arrow-in-right' => '<path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/><path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>',
        'send' => '<path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11zM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493z"/>',
        'arrow-left' => '<path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>',
        'shield-lock' => '<path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/><path d="M9.5 6.5a1.5 1.5 0 0 1-1 1.415l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99a1.5 1.5 0 1 1 2-1.415z"/>',
        'check-circle' => '<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>',
        'calendar-check' => '<path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>',
    ];
    $path = $svgs[$name] ?? '';
    $cls = htmlspecialchars($class, ENT_QUOTES);
    return '<svg class="' . $cls . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">' . $path . '</svg>';
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
 * Traduce lo stato ordine in etichetta italiana.
 */
function order_status_label(string $status): string
{
    return match ($status) {
        'pending'   => 'In attesa',
        'accepted'  => 'Accettato',
        'preparing' => 'In preparazione',
        'ready'     => 'Pronto',
        'completed' => 'Completato',
        'cancelled' => 'Annullato',
        'rejected'  => 'Rifiutato',
        default     => ucfirst($status),
    };
}

/**
 * Badge Bootstrap per stato ordine.
 */
function order_status_badge(string $status): string
{
    $label = order_status_label($status);
    $class = match ($status) {
        'pending'   => 'bg-warning text-dark',
        'accepted'  => 'bg-info text-dark',
        'preparing' => 'bg-primary',
        'ready'     => 'bg-success',
        'completed' => 'bg-secondary',
        'cancelled' => 'bg-danger',
        'rejected'  => 'bg-dark',
        default     => 'bg-secondary',
    };
    return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
}

/**
 * Traduce il tipo ordine in italiano.
 */
function order_type_label(string $type): string
{
    return match ($type) {
        'takeaway' => 'Asporto',
        'delivery' => 'Consegna',
        default    => ucfirst($type),
    };
}

/**
 * Review feedback status → Italian label.
 */
function review_status_label(string $status): string
{
    return match ($status) {
        'new'     => 'Nuovo',
        'read'    => 'Letto',
        'replied' => 'Risposto',
        default   => ucfirst($status),
    };
}

/**
 * Review feedback status → Bootstrap badge.
 */
function review_status_badge(string $status): string
{
    $class = match ($status) {
        'new'     => 'bg-primary',
        'read'    => 'bg-secondary',
        'replied' => 'bg-success',
        default   => 'bg-secondary',
    };
    return '<span class="badge ' . $class . '">' . review_status_label($status) . '</span>';
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

/**
 * Encrypt a value using AES-256-GCM.
 * Returns base64-encoded string: iv:tag:ciphertext
 */
function encrypt_value(string $plaintext): string
{
    $key = base64_decode(env('APP_ENCRYPTION_KEY', ''));
    if (strlen($key) < 16) {
        throw new \RuntimeException('APP_ENCRYPTION_KEY non configurata o troppo corta.');
    }
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ciphertext === false) {
        throw new \RuntimeException('Errore crittografia.');
    }
    return base64_encode($iv . $tag . $ciphertext);
}

/**
 * Decrypt a value encrypted with encrypt_value().
 * Returns null on failure.
 */
function decrypt_value(string $encrypted): ?string
{
    $key = base64_decode(env('APP_ENCRYPTION_KEY', ''));
    if (strlen($key) < 16) {
        return null;
    }
    $data = base64_decode($encrypted, true);
    if ($data === false || strlen($data) < 28) { // 12 iv + 16 tag + min 1 byte
        return null;
    }
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $ciphertext = substr($data, 28);
    $result = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $result === false ? null : $result;
}
