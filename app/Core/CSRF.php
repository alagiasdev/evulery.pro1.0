<?php

namespace App\Core;

class CSRF
{
    public static function generate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set('_csrf_token', $token);
        return $token;
    }

    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            return self::generate();
        }
        return Session::get('_csrf_token');
    }

    public static function validate(?string $token): bool
    {
        if ($token === null) {
            return false;
        }
        return hash_equals(Session::get('_csrf_token', ''), $token);
    }

    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token) . '">';
    }
}
