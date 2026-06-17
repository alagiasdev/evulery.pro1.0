<?php

namespace App\Core;

class Session
{
    private const IDLE_TIMEOUT = 28800;     // 8 ore — copre l'intera giornata di servizio
                                            // senza re-login forzato; resta una protezione
                                            // se la postazione viene lasciata incustodita.
    private const ABSOLUTE_TIMEOUT = 43200; // 12 ore — copre il turno di lavoro tipico
                                            // del ristoratore (es. 10:00-22:00) evitando
                                            // un re-login obbligato a meta' giornata.

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Sessione "saltava" dopo ~30 min nonostante i timeout applicativi:
            // su hosting Linux la pulizia dei file di sessione e' tipicamente
            // gestita da un CRON DI SISTEMA a durata fissa (~24-30 min) sulla
            // save_path condivisa, che IGNORA il gc_maxlifetime impostato qui.
            // Soluzione: usare una save_path DEDICATA all'app — quei file non
            // vengono toccati dal cron di sistema e la durata e' governata solo
            // dai nostri timeout. Fallback sicuro: se la dir non e' scrivibile,
            // non cambiamo nulla (meglio il vecchio comportamento che sessioni rotte).
            if (defined('BASE_PATH')) {
                $sessionPath = BASE_PATH . '/storage/sessions';
                if (!is_dir($sessionPath)) {
                    @mkdir($sessionPath, 0700, true);
                }
                if (is_dir($sessionPath) && is_writable($sessionPath)) {
                    session_save_path($sessionPath);
                    // Su questa dir non gira il cron di sistema: garantiamo un
                    // minimo di GC interno di PHP cosi' i file vecchi non si
                    // accumulano all'infinito.
                    ini_set('session.gc_probability', '1');
                    ini_set('session.gc_divisor', '100');
                }
            }
            // Allinea comunque il GC al limite assoluto (utile dove il save_path
            // di default e' privato, es. XAMPP locale).
            ini_set('session.gc_maxlifetime', (string)self::ABSOLUTE_TIMEOUT);

            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly'  => true,
                'samesite'  => 'Lax',
            ]);
            session_start();

            // Idle timeout: clear session data after 30 min of inactivity (without destroying cookie)
            // PRESERVA il CSRF token: l'utente che torna dopo > 30 min al form login NON deve
            // ricevere CSRF FAIL al submit (il token non è dato sensibile, è solo anti-CSRF)
            if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > self::IDLE_TIMEOUT) {
                $preservedCsrf = $_SESSION['_csrf_token'] ?? null;
                $_SESSION = [];
                session_regenerate_id(true);
                if ($preservedCsrf !== null) {
                    $_SESSION['_csrf_token'] = $preservedCsrf;
                }
            }

            // Absolute timeout: clear session data after 8 hours regardless of activity
            // Stesso preserve del CSRF token (vedi sopra)
            if (isset($_SESSION['_created_at']) && (time() - $_SESSION['_created_at']) > self::ABSOLUTE_TIMEOUT) {
                $preservedCsrf = $_SESSION['_csrf_token'] ?? null;
                $_SESSION = [];
                session_regenerate_id(true);
                if ($preservedCsrf !== null) {
                    $_SESSION['_csrf_token'] = $preservedCsrf;
                }
            }

            $_SESSION['_last_activity'] = time();
            if (!isset($_SESSION['_created_at'])) {
                $_SESSION['_created_at'] = time();
            }
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
