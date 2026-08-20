<?php

namespace App\Core;

/**
 * "Ricordami": login persistente sicuro con pattern selector/validator.
 *
 * Cookie = "selector:validator". Nel DB si salva selector (in chiaro, indicizzato)
 * + HASH del validator. Alla validazione: lookup per selector, confronto a tempo
 * costante dell'hash del validator, poi ROTAZIONE (nuovo validator). Se il selector
 * esiste ma il validator non combacia => possibile furto del cookie => si invalidano
 * TUTTI i token dell'utente (difesa). Scadenza 30 giorni. Cookie HttpOnly/Secure/Lax.
 *
 * NON tocca l'autenticazione a password: e' un secondo fattore di comodita' che
 * ristabilisce la sessione. Invalidato al logout e al cambio password.
 *
 * Tutte le operazioni DB sono protette da try/catch: il "Ricordami" e' un optional
 * e non deve MAI rompere login/dashboard (es. se la migration 086 non e' ancora
 * applicata in prod, degrada silenziosamente).
 */
class RememberMe
{
    private const COOKIE   = 'evulery_remember';
    private const TTL_DAYS = 30;

    /** Emette un nuovo token e imposta il cookie (chiamato al login con "Ricordami"). */
    public static function issue(int $userId): void
    {
        try {
            $selector  = bin2hex(random_bytes(9));   // 18 char
            $validator = bin2hex(random_bytes(32));  // 64 char
            $expires   = date('Y-m-d H:i:s', time() + self::TTL_DAYS * 86400);

            Database::getInstance()->prepare(
                'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at)
                 VALUES (:u, :s, :v, :e)'
            )->execute([
                'u' => $userId,
                's' => $selector,
                'v' => hash('sha256', $validator),
                'e' => $expires,
            ]);

            self::setCookie($selector . ':' . $validator, self::TTL_DAYS * 86400);
        } catch (\Throwable $e) {
            app_log('RememberMe issue fallito (migration 086?): ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * Prova a ristabilire la sessione dal cookie. True se l'utente e' stato loggato.
     * Ruota il validator ad ogni uso riuscito.
     */
    public static function attemptFromCookie(): bool
    {
        $raw = $_COOKIE[self::COOKIE] ?? '';
        if ($raw === '' || !str_contains($raw, ':')) {
            return false;
        }
        [$selector, $validator] = explode(':', $raw, 2);
        if ($selector === '' || $validator === '') {
            self::clearCookie();
            return false;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT * FROM remember_tokens WHERE selector = :s LIMIT 1');
            $stmt->execute(['s' => $selector]);
            $row = $stmt->fetch();

            if (!$row) {
                self::clearCookie();
                return false;
            }
            if (strtotime((string) $row['expires_at']) < time()) {
                $db->prepare('DELETE FROM remember_tokens WHERE id = :id')->execute(['id' => $row['id']]);
                self::clearCookie();
                return false;
            }
            // Confronto a tempo costante. Mismatch = possibile furto -> invalida tutto l'utente.
            if (!hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
                self::clearAllForUser((int) $row['user_id']);
                self::clearCookie();
                return false;
            }

            if (!Auth::loginById((int) $row['user_id'])) {
                $db->prepare('DELETE FROM remember_tokens WHERE id = :id')->execute(['id' => $row['id']]);
                self::clearCookie();
                return false;
            }

            // Rotazione: nuovo validator + rinnovo scadenza (stesso selector).
            $newValidator = bin2hex(random_bytes(32));
            $newExpires   = date('Y-m-d H:i:s', time() + self::TTL_DAYS * 86400);
            $db->prepare('UPDATE remember_tokens SET validator_hash = :v, expires_at = :e WHERE id = :id')
               ->execute(['v' => hash('sha256', $newValidator), 'e' => $newExpires, 'id' => $row['id']]);
            self::setCookie($selector . ':' . $newValidator, self::TTL_DAYS * 86400);

            return true;
        } catch (\Throwable $e) {
            app_log('RememberMe auto-login fallito (migration 086?): ' . $e->getMessage(), 'warning');
            return false;
        }
    }

    /** Elimina il token corrente (DB + cookie). Chiamato al logout. */
    public static function clear(): void
    {
        $raw = $_COOKIE[self::COOKIE] ?? '';
        if ($raw !== '' && str_contains($raw, ':')) {
            [$selector] = explode(':', $raw, 2);
            if ($selector !== '') {
                try {
                    Database::getInstance()->prepare('DELETE FROM remember_tokens WHERE selector = :s')
                        ->execute(['s' => $selector]);
                } catch (\Throwable $e) {
                    // ignora: il cookie viene comunque cancellato sotto
                }
            }
        }
        self::clearCookie();
    }

    /** Invalida TUTTI i token di un utente (cambio password, furto sospetto). */
    public static function clearAllForUser(int $userId): void
    {
        try {
            Database::getInstance()->prepare('DELETE FROM remember_tokens WHERE user_id = :u')
                ->execute(['u' => $userId]);
        } catch (\Throwable $e) {
            app_log('RememberMe clearAllForUser fallito (migration 086?): ' . $e->getMessage(), 'warning');
        }
    }

    private static function setCookie(string $value, int $maxAgeSeconds): void
    {
        setcookie(self::COOKIE, $value, [
            'expires'  => time() + $maxAgeSeconds,
            'path'     => '/',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE] = $value; // coerenza entro la richiesta corrente
    }

    private static function clearCookie(): void
    {
        setcookie(self::COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::COOKIE]);
    }

    private static function isHttps(): bool
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
}
