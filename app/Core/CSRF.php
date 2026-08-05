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
        $sessionToken = Session::get('_csrf_token', '');
        // Fail-closed: rifiuta token vuoto/null E sessione senza token PRIMA di
        // hash_equals. Altrimenti _csrf='' su una sessione priva di token darebbe
        // hash_equals('', '') === true -> bypass CSRF.
        if ($token === null || $token === '' || $sessionToken === '') {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token) . '">';
    }
}
