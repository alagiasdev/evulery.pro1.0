<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class AuditLog
{
    // Event constants
    public const LOGIN_SUCCESS   = 'login_success';
    public const LOGIN_FAILED    = 'login_failed';
    public const LOGOUT          = 'logout';
    public const PASSWORD_RESET_REQUEST = 'password_reset_request';
    public const PASSWORD_RESET_DONE    = 'password_reset_done';
    public const TENANT_CREATED  = 'tenant_created';
    public const TENANT_TOGGLED  = 'tenant_toggled';
    public const SETTINGS_UPDATED = 'settings_updated';

    private static ?PDO $db = null;

    private static function db(): PDO
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    public static function log(string $event, ?string $description = null, ?int $userId = null, ?int $tenantId = null): void
    {
        try {
            $stmt = self::db()->prepare(
                'INSERT INTO audit_logs (user_id, tenant_id, event, description, ip_address, user_agent, created_at)
                 VALUES (:user_id, :tenant_id, :event, :description, :ip, :ua, NOW())'
            );
            $stmt->execute([
                'user_id'     => $userId,
                'tenant_id'   => $tenantId,
                'event'       => $event,
                'description' => $description ? substr($description, 0, 500) : null,
                'ip'          => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'ua'          => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
            ]);
        } catch (\PDOException $e) {
            // Silently fail - audit logging should never break the app
            app_log("Audit log error: " . $e->getMessage());
        }
    }
}
