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

    // ─── Impersonation ───────────────────────────────────────

    public static function startImpersonation(int $targetUserId): void
    {
        // Save original admin session
        Session::set('original_admin_id', Session::get('user_id'));
        Session::set('original_admin_name', Session::get('user_name'));
        Session::set('impersonating', true);

        // Load target user
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $targetUserId]);
        $user = $stmt->fetch();

        if (!$user) {
            // Target user disappeared — rollback impersonation flags
            Session::remove('original_admin_id');
            Session::remove('original_admin_name');
            Session::remove('impersonating');
            return;
        }

        // Overwrite session with target user
        Session::set('user_id', $user['id']);
        Session::set('tenant_id', $user['tenant_id']);
        Session::set('user_role', $user['role']);
        Session::set('user_email', $user['email']);
        Session::set('user_name', $user['first_name'] . ' ' . $user['last_name']);
    }

    public static function stopImpersonation(): void
    {
        $adminId = Session::get('original_admin_id');

        // Reload admin from DB
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $adminId]);
        $admin = $stmt->fetch();

        if (!$admin) {
            // Original admin was deleted — force full logout
            Session::destroy();
            return;
        }

        // Restore admin session
        Session::set('user_id', $admin['id']);
        Session::set('tenant_id', $admin['tenant_id']);
        Session::set('user_role', $admin['role']);
        Session::set('user_email', $admin['email']);
        Session::set('user_name', $admin['first_name'] . ' ' . $admin['last_name']);

        // Clean up impersonation keys
        Session::remove('original_admin_id');
        Session::remove('original_admin_name');
        Session::remove('impersonating');
    }

    public static function isImpersonating(): bool
    {
        return Session::get('impersonating', false) === true;
    }

    public static function originalAdminId(): ?int
    {
        return Session::get('original_admin_id');
    }
}
