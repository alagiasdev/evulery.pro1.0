<?php

namespace App\Core;

class Session
{
    private const IDLE_TIMEOUT = 1800;     // 30 minutes
    private const ABSOLUTE_TIMEOUT = 28800; // 8 hours

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly'  => true,
                'samesite'  => 'Lax',
            ]);
            session_start();

            // Idle timeout: destroy session after 30 min of inactivity
            if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > self::IDLE_TIMEOUT) {
                self::destroy();
                session_start();
                return;
            }
            $_SESSION['_last_activity'] = time();

            // Absolute timeout: destroy session after 8 hours regardless of activity
            if (isset($_SESSION['_created_at']) && (time() - $_SESSION['_created_at']) > self::ABSOLUTE_TIMEOUT) {
                self::destroy();
                session_start();
                return;
            }
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
