<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class AuditLog
{
    // Auth
    public const LOGIN_SUCCESS          = 'login_success';
    public const LOGIN_FAILED           = 'login_failed';
    public const LOGOUT                 = 'logout';
    public const PASSWORD_RESET_REQUEST = 'password_reset_request';
    public const PASSWORD_RESET_DONE    = 'password_reset_done';

    // Tenant (admin)
    public const TENANT_CREATED  = 'tenant_created';
    public const TENANT_TOGGLED  = 'tenant_toggled';

    // Prenotazioni
    public const RESERVATION_CREATED = 'reservation_created';
    public const RESERVATION_UPDATED = 'reservation_updated';
    public const RESERVATION_STATUS  = 'reservation_status';
    public const RESERVATION_DELETED = 'reservation_deleted';

    // Clienti
    public const CUSTOMER_NOTES_UPDATED = 'customer_notes_updated';
    public const CUSTOMER_BLOCKED       = 'customer_blocked';

    // Menù
    public const MENU_CATEGORY_CREATED = 'menu_category_created';
    public const MENU_CATEGORY_UPDATED = 'menu_category_updated';
    public const MENU_CATEGORY_DELETED = 'menu_category_deleted';
    public const MENU_ITEM_CREATED     = 'menu_item_created';
    public const MENU_ITEM_UPDATED     = 'menu_item_updated';
    public const MENU_ITEM_DELETED     = 'menu_item_deleted';
    public const MENU_TOGGLED          = 'menu_toggled';

    // Promozioni
    public const PROMOTION_CREATED = 'promotion_created';
    public const PROMOTION_UPDATED = 'promotion_updated';
    public const PROMOTION_DELETED = 'promotion_deleted';

    // Abbonamenti (admin)
    public const SUBSCRIPTION_CHANGED = 'subscription_changed';
    public const PLAN_CREATED         = 'plan_created';
    public const PLAN_UPDATED         = 'plan_updated';
    public const PLAN_DELETED         = 'plan_deleted';
    public const SERVICE_CREATED      = 'service_created';
    public const SERVICE_UPDATED      = 'service_updated';
    public const SERVICE_DELETED      = 'service_deleted';

    // Settings
    public const SETTINGS_UPDATED = 'settings_updated';
    public const DEPOSIT_UPDATED  = 'deposit_updated';
    public const SLOTS_UPDATED    = 'slots_updated';

    // Profilo
    public const PROFILE_UPDATED = 'profile_updated';

    // Impersonation
    public const IMPERSONATION_START = 'impersonation_start';
    public const IMPERSONATION_END   = 'impersonation_end';

    // Email Broadcast
    public const EMAIL_BROADCAST_CREATED = 'email_broadcast_created';
    public const EMAIL_BROADCAST_SENT    = 'email_broadcast_sent';
    public const EMAIL_BROADCAST_DELETED = 'email_broadcast_deleted';
    public const EMAIL_CREDITS_ASSIGNED  = 'email_credits_assigned';

    /**
     * Traduce un evento in etichetta italiana leggibile.
     */
    public static function eventLabel(string $event): string
    {
        return match ($event) {
            self::LOGIN_SUCCESS          => 'Login effettuato',
            self::LOGIN_FAILED           => 'Login fallito',
            self::LOGOUT                 => 'Logout',
            self::PASSWORD_RESET_REQUEST => 'Richiesta reset password',
            self::PASSWORD_RESET_DONE    => 'Password reimpostata',
            self::TENANT_CREATED         => 'Ristorante creato',
            self::TENANT_TOGGLED         => 'Ristorante attivato/disattivato',
            self::RESERVATION_CREATED    => 'Prenotazione creata',
            self::RESERVATION_UPDATED    => 'Prenotazione modificata',
            self::RESERVATION_STATUS     => 'Stato prenotazione cambiato',
            self::RESERVATION_DELETED    => 'Prenotazione eliminata',
            self::CUSTOMER_NOTES_UPDATED => 'Note cliente aggiornate',
            self::CUSTOMER_BLOCKED       => 'Cliente bloccato/sbloccato',
            self::MENU_CATEGORY_CREATED  => 'Categoria menù creata',
            self::MENU_CATEGORY_UPDATED  => 'Categoria menù modificata',
            self::MENU_CATEGORY_DELETED  => 'Categoria menù eliminata',
            self::MENU_ITEM_CREATED      => 'Piatto creato',
            self::MENU_ITEM_UPDATED      => 'Piatto modificato',
            self::MENU_ITEM_DELETED      => 'Piatto eliminato',
            self::MENU_TOGGLED           => 'Menù attivato/disattivato',
            self::PROMOTION_CREATED      => 'Promozione creata',
            self::PROMOTION_UPDATED      => 'Promozione modificata',
            self::PROMOTION_DELETED      => 'Promozione eliminata',
            self::SUBSCRIPTION_CHANGED   => 'Abbonamento modificato',
            self::PLAN_CREATED           => 'Piano creato',
            self::PLAN_UPDATED           => 'Piano modificato',
            self::PLAN_DELETED           => 'Piano eliminato',
            self::SERVICE_CREATED        => 'Servizio creato',
            self::SERVICE_UPDATED        => 'Servizio modificato',
            self::SERVICE_DELETED        => 'Servizio eliminato',
            self::SETTINGS_UPDATED       => 'Impostazioni aggiornate',
            self::DEPOSIT_UPDATED        => 'Caparra aggiornata',
            self::SLOTS_UPDATED          => 'Fasce orarie aggiornate',
            self::PROFILE_UPDATED        => 'Profilo aggiornato',
            self::IMPERSONATION_START    => 'Impersonation avviata',
            self::IMPERSONATION_END      => 'Impersonation terminata',
            self::EMAIL_BROADCAST_CREATED => 'Comunicazione email creata',
            self::EMAIL_BROADCAST_SENT    => 'Comunicazione email inviata',
            self::EMAIL_BROADCAST_DELETED => 'Comunicazione email eliminata',
            self::EMAIL_CREDITS_ASSIGNED  => 'Crediti email assegnati',
            default                      => $event,
        };
    }

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
