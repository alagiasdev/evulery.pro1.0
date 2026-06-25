<?php

namespace App\Services;

use App\Core\Database;

/**
 * Coda di invio asincrono per le email (tabella notification_outbox).
 *
 * Il contenuto accodato e' GIA' renderizzato (subject + HTML): il worker
 * (scripts/process-outbox.php) deve solo trasmetterlo via SMTP. Cosi' il
 * rendering resta in-request (veloce) e si sposta fuori solo la trasmissione,
 * che e' la parte lenta/fragile (rete verso il provider SMTP).
 */
class MailOutbox
{
    /**
     * Accoda un'email. Lancia un'eccezione se non riesce a scrivere: il
     * chiamante (MailService::send) la cattura e fa fallback all'invio inline,
     * cosi' non perdiamo mai un'email.
     */
    public static function enqueueEmail(
        string $to,
        string $subject,
        string $html,
        ?string $fromName = null,
        ?string $replyTo = null,
        ?int $tenantId = null
    ): void {
        $payload = json_encode([
            'to'        => $to,
            'subject'   => $subject,
            'html'      => $html,
            'from_name' => $fromName,
            'reply_to'  => $replyTo,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new \RuntimeException('notification_outbox: payload json_encode fallito (' . json_last_error_msg() . ')');
        }

        $stmt = Database::getInstance()->prepare(
            "INSERT INTO notification_outbox (channel, tenant_id, payload, status, available_at, created_at)
             VALUES ('email', :tid, :payload, 'pending', NOW(), NOW())"
        );
        $stmt->execute(['tid' => $tenantId, 'payload' => $payload]);
    }
}
