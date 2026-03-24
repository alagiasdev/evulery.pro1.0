<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class BroadcastService
{
    /**
     * Get recipients for a broadcast based on segment filter.
     * Excludes blocked and unsubscribed customers.
     */
    public static function getRecipients(int $tenantId, string $segment, ?int $inactiveDays, array $thresholds): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT id, email, first_name, last_name FROM customers
                WHERE tenant_id = :tid AND is_blocked = 0 AND unsubscribed = 0';
        $params = ['tid' => $tenantId];

        $sql .= self::segmentWhere($segment, $inactiveDays, $thresholds);

        $sql .= ' ORDER BY last_name, first_name';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count recipients for preview (same filters as getRecipients).
     */
    public static function countRecipients(int $tenantId, string $segment, ?int $inactiveDays, array $thresholds): int
    {
        $db = Database::getInstance();
        $sql = 'SELECT COUNT(*) FROM customers
                WHERE tenant_id = :tid AND is_blocked = 0 AND unsubscribed = 0';
        $params = ['tid' => $tenantId];

        $sql .= self::segmentWhere($segment, $inactiveDays, $thresholds);

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Build segment WHERE clause (reuses Customer model logic).
     */
    private static function segmentWhere(string $segment, ?int $inactiveDays, array $thresholds): string
    {
        return match ($segment) {
            'nuovo'       => ' AND total_bookings < ' . (int)$thresholds['occ'],
            'occasionale' => ' AND total_bookings >= ' . (int)$thresholds['occ'] . ' AND total_bookings < ' . (int)$thresholds['abi'],
            'abituale'    => ' AND total_bookings >= ' . (int)$thresholds['abi'] . ' AND total_bookings < ' . (int)$thresholds['vip'],
            'vip'         => ' AND total_bookings >= ' . (int)$thresholds['vip'],
            'inactive'    => ' AND (last_booking_at IS NULL OR last_booking_at < DATE_SUB(NOW(), INTERVAL ' . max(1, (int)$inactiveDays) . ' DAY))',
            default       => '', // 'all'
        };
    }

    /**
     * Generate or retrieve unsubscribe token for a tenant+email pair.
     */
    public static function generateUnsubscribeToken(int $tenantId, string $email): string
    {
        $db = Database::getInstance();

        // Check existing
        $stmt = $db->prepare('SELECT token FROM email_unsubscribes WHERE tenant_id = :tid AND email = :email LIMIT 1');
        $stmt->execute(['tid' => $tenantId, 'email' => $email]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return $existing;
        }

        // Create new
        $token = bin2hex(random_bytes(32));
        $db->prepare(
            'INSERT INTO email_unsubscribes (tenant_id, email, token) VALUES (:tid, :email, :token)'
        )->execute(['tid' => $tenantId, 'email' => $email, 'token' => $token]);

        return $token;
    }

    /**
     * Build the HTML email for a broadcast message.
     */
    public static function buildEmailHtml(string $bodyText, array $tenant, string $unsubscribeUrl): string
    {
        $restaurantName = e($tenant['name'] ?? '');
        $restaurantAddress = e($tenant['address'] ?? '');
        $restaurantPhone = e($tenant['phone'] ?? '');

        // Convert plain text to HTML: escape, auto-link URLs, then nl2br
        $bodyHtml = e($bodyText);
        $bodyHtml = preg_replace_callback(
            '/(https?:\/\/[^\s<]+)/',
            fn($m) => '<a href="' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '" style="color:#00844A;text-decoration:underline;" target="_blank">' . $m[1] . '</a>',
            $bodyHtml
        );
        $bodyHtml = nl2br($bodyHtml);

        // Restaurant info
        $restaurantInfoHtml = '';
        if ($restaurantAddress || $restaurantPhone) {
            $addressLine = $restaurantAddress ? "<div style=\"font-size:12px;color:#6c757d;margin-bottom:2px;\">{$restaurantAddress}</div>" : '';
            $phoneLine = $restaurantPhone ? "<div style=\"font-size:12px;color:#00844A;font-weight:600;\">{$restaurantPhone}</div>" : '';
            $restaurantInfoHtml = <<<HTML
            <div style="padding:20px 32px;text-align:center;">
                <div style="font-size:14px;font-weight:700;color:#1a1d23;margin-bottom:4px;">{$restaurantName}</div>
                {$addressLine}
                {$phoneLine}
            </div>
            HTML;
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f6f8;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;">
        <div style="max-width:600px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header -->
                <div style="background:#00844A;padding:32px 32px 28px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">&#9993;</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">{$restaurantName}</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.8);margin:0;">Ha un messaggio per te</p>
                </div>

                <!-- Body -->
                <div style="padding:28px 32px;font-size:15px;color:#1a1d23;line-height:1.65;">
                    {$bodyHtml}
                </div>

                <div style="border-top:1px solid #f0f0f0;margin:0 32px;"></div>

                {$restaurantInfoHtml}

                <!-- Footer -->
                <div style="background:#f8f9fa;padding:20px 32px;text-align:center;border-top:1px solid #f0f0f0;">
                    <div style="font-size:11px;color:#adb5bd;line-height:1.5;">
                        Hai ricevuto questa email perch&eacute; sei un cliente di {$restaurantName}.
                    </div>
                    <div style="margin-top:8px;">
                        <a href="{$unsubscribeUrl}" style="font-size:10px;color:#adb5bd;text-decoration:underline;">Non desidero ricevere altre comunicazioni</a>
                    </div>
                    <div style="font-size:10px;color:#ced4da;margin-top:12px;">
                        Powered by Evulery &middot; by alagias. - Soluzioni per il web
                    </div>
                </div>

            </div>
        </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Create a PHPMailer instance configured for TurboSMTP broadcast.
     */
    public static function createBroadcastMailer(): PHPMailer
    {
        $mailer = new PHPMailer(true);

        $host = env('BROADCAST_SMTP_HOST', '');
        $username = env('BROADCAST_SMTP_USERNAME', '');
        $password = env('BROADCAST_SMTP_PASSWORD', '');

        // Fall back to standard MAIL_* config if broadcast not configured
        if (!$username || !$password) {
            $host = env('MAIL_HOST', 'localhost');
            $username = env('MAIL_USERNAME', '');
            $password = env('MAIL_PASSWORD', '');
        }

        if ($username && $password) {
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->SMTPAuth = true;
            $mailer->Username = $username;
            $mailer->Password = $password;
            $mailer->Port = (int) env('BROADCAST_SMTP_PORT', env('MAIL_PORT', 587));

            $encryption = env('BROADCAST_SMTP_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));
            if ($encryption === 'tls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
        }

        $mailer->setFrom(
            env('BROADCAST_SMTP_FROM', env('MAIL_FROM', 'noreply@evulery.pro')),
            env('BROADCAST_SMTP_FROM_NAME', env('MAIL_FROM_NAME', 'Evulery'))
        );

        $mailer->isHTML(true);
        $mailer->CharSet = 'UTF-8';

        return $mailer;
    }

    /**
     * Send a single broadcast email via the provided mailer.
     */
    public static function sendOne(PHPMailer $mailer, string $to, string $subject, string $html, ?string $fromName = null, ?string $replyTo = null): bool
    {
        try {
            $mailer->clearAddresses();
            $mailer->clearReplyTos();
            $mailer->addAddress($to);
            $mailer->Subject = $subject;
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));

            if ($fromName) {
                $mailer->FromName = $fromName;
            }
            if ($replyTo) {
                $mailer->addReplyTo($replyTo, $fromName ?? '');
            }

            $mailer->send();
            return true;
        } catch (Exception $e) {
            app_log("Broadcast send error [{$to}]: " . preg_replace('/password[=:]\S+/i', 'password=***', $e->getMessage()));
            return false;
        }
    }
}
