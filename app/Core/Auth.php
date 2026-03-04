<?php

namespace App\Core;

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        Session::regenerate();

        Session::set('user_id', $user['id']);
        Session::set('tenant_id', $user['tenant_id']);
        Session::set('user_role', $user['role']);
        Session::set('user_email', $user['email']);
        Session::set('user_name', $user['first_name'] . ' ' . $user['last_name']);

        // Update last login
        $stmt = $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $user['id']]);

        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        return [
            'id'        => Session::get('user_id'),
            'tenant_id' => Session::get('tenant_id'),
            'role'      => Session::get('user_role'),
            'email'     => Session::get('user_email'),
            'name'      => Session::get('user_name'),
        ];
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function tenantId(): ?int
    {
        return Session::get('tenant_id');
    }

    public static function role(): ?string
    {
        return Session::get('user_role');
    }

    public static function isLoggedIn(): bool
    {
        return Session::has('user_id');
    }

    public static function isSuperAdmin(): bool
    {
        return Session::get('user_role') === 'super_admin';
    }

    public static function isOwner(): bool
    {
        return Session::get('user_role') === 'owner';
    }

    public static function belongsToTenant(int $tenantId): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }
        return self::tenantId() === $tenantId;
    }
}
