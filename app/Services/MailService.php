<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        $host = env('MAIL_HOST', 'localhost');
        $username = env('MAIL_USERNAME', '');
        $password = env('MAIL_PASSWORD', '');

        // Use SMTP if credentials are configured
        if ($username && $password) {
            $this->mailer->isSMTP();
            $this->mailer->Host = $host;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $username;
            $this->mailer->Password = $password;
            $this->mailer->Port = (int)env('MAIL_PORT', 587);

            $encryption = env('MAIL_ENCRYPTION', 'tls');
            if ($encryption === 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
        }

        $this->mailer->setFrom(
            env('MAIL_FROM', 'noreply@evulery.pro'),
            env('MAIL_FROM_NAME', 'Evulery')
        );

        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'UTF-8';
    }

    public function send(string $to, string $subject, string $htmlBody, ?string $fromName = null, ?string $replyTo = null): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            // Override sender name if provided (keeps MAIL_FROM address for SPF/DKIM)
            if ($fromName) {
                $this->mailer->FromName = $fromName;
            }

            // Reply-To: replies go to this address instead of noreply
            if ($replyTo) {
                $this->mailer->addReplyTo($replyTo, $fromName ?? '');
            }

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            app_log("Mail send error to {$to}: " . $e->getMessage(), 'error');
            return false;
        } catch (\Throwable $e) {
            app_log("Mail send unexpected error to {$to}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send reservation email to customer.
     * @param string $type 'confirmed' (new booking) or 'updated' (modified booking)
     */
    public static function sendReservationConfirmation(array $reservation, array $tenant, string $type = 'confirmed'): bool
    {
        $customerEmail = $reservation['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName   = e($reservation['first_name'] ?? '');
        $lastName    = e($reservation['last_name'] ?? '');
        $partySize   = (int)($reservation['party_size'] ?? 0);
        $notes       = $reservation['customer_notes'] ?? '';
        $date        = $reservation['reservation_date'] ?? '';
        $time        = substr($reservation['reservation_time'] ?? '', 0, 5);

        $dateFormatted = self::formatDateItalian($date);

        $restaurantName    = e($tenant['name'] ?? '');
        $restaurantAddress = e($tenant['address'] ?? '');
        $restaurantPhone   = e($tenant['phone'] ?? '');
        $bookingUrl        = url($tenant['slug'] ?? '');
        $menuUrl           = !empty($tenant['menu_enabled']) ? url(($tenant['slug'] ?? '') . '/menu') : '';
        $manageToken       = $reservation['manage_token'] ?? '';
        $manageUrl         = $manageToken ? url("manage/{$manageToken}") : '';

        // Discount section (only if present)
        $discountPercent = (int)($reservation['discount_percent'] ?? 0);
        $discountHtml = '';
        if ($discountPercent > 0) {
            $discountHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#FFF3E0;border-radius:10px;padding:14px 16px;font-size:13px;color:#E65100;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">&#127942; Promozione</div>
                <strong>-{$discountPercent}%</strong> di sconto al tavolo &mdash; lo sconto verr&agrave; applicato al conto dal ristorante.
            </div>
            HTML;
        }

        // Notes section (only if present)
        $notesHtml = '';
        if (!empty($notes)) {
            $notesEscaped = e($notes);
            $notesHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#FFF3E0;border-radius:10px;padding:14px 16px;font-size:13px;color:#E65100;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">Note</div>
                {$notesEscaped}
            </div>
            HTML;
        }

        // Booking instructions section (from tenant settings)
        $instructionsHtml = '';
        $bookingInstructions = $tenant['booking_instructions'] ?? '';
        if (!empty($bookingInstructions)) {
            $instructionsEscaped = nl2br(e($bookingInstructions));
            $instructionsHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#E3F2FD;border-radius:10px;padding:14px 16px;font-size:13px;color:#37474F;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;color:#1565C0;">&#128227; Informazioni dal ristorante</div>
                {$instructionsEscaped}
            </div>
            HTML;
        }

        // Restaurant info section
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

        $personeLabel = $partySize === 1 ? 'persona' : 'persone';

        // Manage link (build before heredoc for interpolation)
        $manageLinkHtml = '';
        if ($manageUrl) {
            $manageLinkHtml = '<a href="' . $manageUrl . '" style="display:inline-block;background:#00844A;color:#ffffff;padding:12px 32px;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;">Gestisci prenotazione</a><br>';
        }

        // Menu link (only if menu is enabled)
        $menuLinkHtml = '';
        if ($menuUrl) {
            $menuLinkHtml = '<a href="' . $menuUrl . '" style="display:inline-block;background:#fff;color:#00844A;border:2px solid #00844A;padding:10px 24px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;margin-top:8px;">&#128214; Consulta il men&ugrave;</a><br>';
        }

        // Type-specific content
        if ($type === 'updated') {
            $headerBg     = '#1565C0';
            $headerIcon   = '&#9998;';  // pencil
            $headerSub    = 'Prenotazione aggiornata';
            $statusBg     = '#E3F2FD';
            $statusColor  = '#1565C0';
            $statusIcon   = '&#9998;';
            $statusTitle  = 'La tua prenotazione &egrave; stata aggiornata';
            $statusSub    = "Ciao {$firstName}, ecco i nuovi dettagli";
            $subjectLine  = "Prenotazione aggiornata - {$restaurantName}";
            $iconColor    = '#1565C0';
        } else {
            $headerBg     = '#00844A';
            $headerIcon   = '&#10003;';
            $headerSub    = 'Prenotazione confermata';
            $statusBg     = '#E8F5E9';
            $statusColor  = '#2E7D32';
            $statusIcon   = '&#10003;';
            $statusTitle  = 'La tua prenotazione &egrave; confermata!';
            $statusSub    = "Ciao {$firstName}, ti aspettiamo!";
            $subjectLine  = "Conferma prenotazione - {$restaurantName}";
            $iconColor    = '#00844A';
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f6f8;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;">
        <div style="max-width:600px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header -->
                <div style="background:{$headerBg};padding:32px 32px 28px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">{$headerIcon}</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">{$restaurantName}</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.8);margin:0;">{$headerSub}</p>
                </div>

                <!-- Status -->
                <div style="padding:24px 32px 8px;text-align:center;">
                    <div style="width:56px;height:56px;border-radius:50%;background:{$statusBg};color:{$statusColor};display:inline-block;line-height:56px;font-size:24px;margin-bottom:12px;">{$statusIcon}</div>
                    <h2 style="font-size:18px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{$statusTitle}</h2>
                    <p style="font-size:14px;color:#6c757d;margin:0;">{$statusSub}</p>
                </div>

                <!-- Details card -->
                <div style="margin:20px 32px 24px;background:#f8f9fa;border-radius:12px;padding:20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$iconColor};text-align:center;font-size:15px;line-height:36px;" width="36">&#128197;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Data</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$dateFormatted}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$iconColor};text-align:center;font-size:15px;line-height:36px;" width="36">&#128336;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Orario</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$time}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$iconColor};text-align:center;font-size:15px;line-height:36px;" width="36">&#128101;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Persone</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$partySize} {$personeLabel}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$iconColor};text-align:center;font-size:15px;line-height:36px;" width="36">&#128100;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Intestata a</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$firstName} {$lastName}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                    </table>
                </div>

                {$notesHtml}
                {$discountHtml}
                {$instructionsHtml}

                <!-- CTA -->
                <div style="text-align:center;padding:0 32px 24px;">
                    {$manageLinkHtml}
                    {$menuLinkHtml}
                    <a href="{$bookingUrl}" style="display:inline-block;background:#f0f0f0;color:#495057;padding:10px 24px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;margin-top:8px;">Prenota ancora</a>
                </div>

                <div style="border-top:1px solid #f0f0f0;margin:0 32px;"></div>

                {$restaurantInfoHtml}

                <!-- Footer -->
                <div style="background:#f8f9fa;padding:20px 32px;text-align:center;border-top:1px solid #f0f0f0;">
                    <div style="font-size:11px;color:#adb5bd;line-height:1.5;">
                        Hai ricevuto questa email perch&eacute; hai effettuato una prenotazione presso {$restaurantName}.
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

        $service = new self();
        $replyTo = $tenant['email'] ?? null;
        return $service->send($customerEmail, $subjectLine, $html, $tenant['name'] ?? null, $replyTo);
    }

    /**
     * Send reservation reminder email.
     * @param string $type '24h' or '2h'
     */
    public static function sendReservationReminder(array $reservation, array $tenant, string $type = '24h'): bool
    {
        $customerEmail = $reservation['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName   = e($reservation['first_name'] ?? '');
        $lastName    = e($reservation['last_name'] ?? '');
        $partySize   = (int)($reservation['party_size'] ?? 0);
        $date        = $reservation['reservation_date'] ?? '';
        $time        = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted = self::formatDateItalian($date);
        $personeLabel  = $partySize === 1 ? 'persona' : 'persone';
        $discountPercent = (int)($reservation['discount_percent'] ?? 0);
        $discountHtml = '';
        if ($discountPercent > 0) {
            $discountHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#FFF3E0;border-radius:10px;padding:14px 16px;font-size:13px;color:#E65100;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">&#127942; Promozione</div>
                <strong>-{$discountPercent}%</strong> di sconto al tavolo &mdash; lo sconto verr&agrave; applicato al conto dal ristorante.
            </div>
            HTML;
        }

        $restaurantName    = e($tenant['name'] ?? '');
        $restaurantAddress = e($tenant['address'] ?? '');
        $restaurantPhone   = e($tenant['phone'] ?? '');
        $bookingSlug       = $tenant['slug'] ?? '';

        // Cancel link (if API supports it)
        $cancelUrl = url("api/v1/tenants/{$bookingSlug}/reservations/{$reservation['id']}");

        // Type-specific messaging
        if ($type === '2h') {
            $headerBg    = '#E65100';
            $headerIcon  = '&#9200;'; // alarm clock
            $headerSub   = 'Promemoria - tra 2 ore';
            $greeting    = "Ci vediamo tra poco!";
            $subjectLine = "Tra 2 ore: prenotazione da {$restaurantName}";
            $timingText  = "La tua prenotazione &egrave; <strong>tra 2 ore</strong>.";
        } else {
            $headerBg    = '#1565C0';
            $headerIcon  = '&#128276;'; // bell
            $headerSub   = 'Promemoria - domani';
            $greeting    = "Ti ricordiamo la tua prenotazione!";
            $subjectLine = "Promemoria: prenotazione domani da {$restaurantName}";
            $timingText  = "Ti aspettiamo <strong>domani</strong>!";
        }

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

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f6f8;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;">
        <div style="max-width:600px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header -->
                <div style="background:{$headerBg};padding:32px 32px 28px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">{$headerIcon}</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">{$restaurantName}</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.8);margin:0;">{$headerSub}</p>
                </div>

                <!-- Greeting -->
                <div style="padding:24px 32px 8px;text-align:center;">
                    <h2 style="font-size:18px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{$greeting}</h2>
                    <p style="font-size:14px;color:#6c757d;margin:0;">Ciao {$firstName}, {$timingText}</p>
                </div>

                <!-- Details card -->
                <div style="margin:20px 32px 24px;background:#f8f9fa;border-radius:12px;padding:20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$headerBg};text-align:center;font-size:15px;line-height:36px;" width="36">&#128197;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Data</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$dateFormatted}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$headerBg};text-align:center;font-size:15px;line-height:36px;" width="36">&#128336;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Orario</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$time}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$headerBg};text-align:center;font-size:15px;line-height:36px;" width="36">&#128101;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Persone</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$partySize} {$personeLabel}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                    </table>
                </div>

                {$discountHtml}

                <div style="border-top:1px solid #f0f0f0;margin:0 32px;"></div>

                {$restaurantInfoHtml}

                <!-- Footer -->
                <div style="background:#f8f9fa;padding:20px 32px;text-align:center;border-top:1px solid #f0f0f0;">
                    <div style="font-size:11px;color:#adb5bd;line-height:1.5;">
                        Hai ricevuto questa email perch&eacute; hai una prenotazione presso {$restaurantName}.
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

        $service = new self();
        $replyTo = $tenant['email'] ?? null;
        return $service->send($customerEmail, $subjectLine, $html, $tenant['name'] ?? null, $replyTo);
    }

    /**
     * Format a Y-m-d date in Italian (e.g. "Mercoledì 18 Marzo 2026").
     */
    private static function formatDateItalian(string $date): string
    {
        $ts = strtotime($date);
        if (!$ts) {
            return e($date);
        }

        $days = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
        $months = ['', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                   'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];

        $dayName = $days[(int)date('w', $ts)];
        $day = (int)date('j', $ts);
        $month = $months[(int)date('n', $ts)];
        $year = date('Y', $ts);

        return "{$dayName} {$day} {$month} {$year}";
    }

    /**
     * Send cancellation notification to the restaurant owner.
     * Triggered when a customer cancels via manage link or staff cancels from dashboard.
     */
    public static function sendCancellationNotification(array $reservation, array $tenant, string $cancelledBy = 'cliente'): bool
    {
        $ownerEmail = $tenant['email'] ?? '';
        if (!$ownerEmail) {
            return false;
        }

        $firstName     = e($reservation['first_name'] ?? '');
        $lastName      = e($reservation['last_name'] ?? '');
        $customerEmail = e($reservation['email'] ?? '');
        $customerPhone = e($reservation['phone'] ?? '');
        $partySize     = (int)($reservation['party_size'] ?? 0);
        $date          = $reservation['reservation_date'] ?? '';
        $time          = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted = self::formatDateItalian($date);
        $personeLabel  = $partySize === 1 ? 'persona' : 'persone';

        $restaurantName = e($tenant['name'] ?? '');
        $dashboardUrl   = url('dashboard/reservations');

        $cancelLabel = $cancelledBy === 'cliente'
            ? 'Annullata dal cliente via link di gestione'
            : 'Annullata dallo staff dalla dashboard';

        $subjectLine = "Prenotazione annullata - {$firstName} {$lastName} ({$dateFormatted})";

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f6f8;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;">
        <div style="max-width:600px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header -->
                <div style="background:#dc3545;padding:32px 32px 28px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">&#10007;</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">{$restaurantName}</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.8);margin:0;">Prenotazione annullata</p>
                </div>

                <!-- Status -->
                <div style="padding:24px 32px 8px;text-align:center;">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fee2e2;color:#dc3545;display:inline-block;line-height:56px;font-size:24px;margin-bottom:12px;">&#10007;</div>
                    <h2 style="font-size:18px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Prenotazione cancellata</h2>
                    <p style="font-size:14px;color:#6c757d;margin:0;">{$cancelLabel}</p>
                </div>

                <!-- Details card -->
                <div style="margin:20px 32px 24px;background:#f8f9fa;border-radius:12px;padding:20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#dc3545;text-align:center;font-size:15px;line-height:36px;" width="36">&#128197;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Data</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$dateFormatted}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#dc3545;text-align:center;font-size:15px;line-height:36px;" width="36">&#128336;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Orario</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$time}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#dc3545;text-align:center;font-size:15px;line-height:36px;" width="36">&#128101;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Persone</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$partySize} {$personeLabel}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#dc3545;text-align:center;font-size:15px;line-height:36px;" width="36">&#128100;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Cliente</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$firstName} {$lastName}</div>
                                        <div style="font-size:12px;color:#6c757d;">{$customerEmail} &middot; {$customerPhone}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- CTA -->
                <div style="text-align:center;padding:0 32px 24px;">
                    <a href="{$dashboardUrl}" style="display:inline-block;background:#00844A;color:#ffffff;padding:12px 32px;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;">Vai alla dashboard</a>
                </div>

                <!-- Footer -->
                <div style="background:#f8f9fa;padding:20px 32px;text-align:center;border-top:1px solid #f0f0f0;">
                    <div style="font-size:11px;color:#adb5bd;line-height:1.5;">
                        Notifica automatica di cancellazione prenotazione.
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

        $service = new self();
        return $service->send($ownerEmail, $subjectLine, $html);
    }

    /**
     * Send new reservation notification to the restaurant owner.
     */
    public static function sendNewReservationNotification(array $reservation, array $tenant): bool
    {
        $ownerEmail = $tenant['email'] ?? '';
        if (!$ownerEmail) {
            return false;
        }

        $firstName     = e($reservation['first_name'] ?? '');
        $lastName      = e($reservation['last_name'] ?? '');
        $customerEmail = e($reservation['email'] ?? '');
        $customerPhone = e($reservation['phone'] ?? '');
        $partySize     = (int)($reservation['party_size'] ?? 0);
        $date          = $reservation['reservation_date'] ?? '';
        $time          = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted = self::formatDateItalian($date);
        $personeLabel  = $partySize === 1 ? 'persona' : 'persone';
        $notes         = $reservation['customer_notes'] ?? '';
        $source        = ($reservation['source'] ?? 'widget') === 'dashboard' ? 'Dashboard' : 'Widget online';
        $status        = ($reservation['status'] ?? 'confirmed') === 'pending' ? 'In attesa di conferma' : 'Confermata';
        $statusColor   = ($reservation['status'] ?? 'confirmed') === 'pending' ? '#E65100' : '#00844A';

        $restaurantName = e($tenant['name'] ?? '');
        $reservationId  = (int)($reservation['id'] ?? 0);
        $dashboardUrl   = url("dashboard/reservations/{$reservationId}");

        $subjectLine = "Nuova prenotazione - {$firstName} {$lastName} ({$dateFormatted}, {$time})";

        // Notes section
        $notesHtml = '';
        if (!empty($notes)) {
            $notesEscaped = e($notes);
            $notesHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#FFF3E0;border-radius:10px;padding:14px 16px;font-size:13px;color:#E65100;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">Note del cliente</div>
                {$notesEscaped}
            </div>
            HTML;
        }

        // Deposit info
        $depositHtml = '';
        if (!empty($reservation['deposit_required']) && !empty($reservation['deposit_amount'])) {
            $depositAmount = number_format((float)$reservation['deposit_amount'], 2, ',', '.');
            $depositType = $reservation['deposit_type'] ?? 'info';
            $depositLabel = match($depositType) {
                'stripe' => 'Stripe',
                'link'   => 'Link pagamento',
                default  => 'Bonifico bancario',
            };
            $depositHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#E3F2FD;border-radius:10px;padding:14px 16px;font-size:13px;color:#1565C0;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">Caparra richiesta</div>
                &euro;{$depositAmount} via {$depositLabel}
            </div>
            HTML;
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f6f8;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;">
        <div style="max-width:600px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header -->
                <div style="background:#00844A;padding:32px 32px 28px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">&#128276;</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">Nuova prenotazione!</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.8);margin:0;">{$restaurantName}</p>
                </div>

                <!-- Status -->
                <div style="padding:24px 32px 8px;text-align:center;">
                    <div style="display:inline-block;padding:6px 16px;border-radius:20px;background:{$statusColor}15;color:{$statusColor};font-size:13px;font-weight:700;">{$status}</div>
                    <p style="font-size:14px;color:#6c757d;margin:8px 0 0;">Origine: {$source}</p>
                </div>

                <!-- Details card -->
                <div style="margin:20px 32px 24px;background:#f8f9fa;border-radius:12px;padding:20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#00844A;text-align:center;font-size:15px;line-height:36px;" width="36">&#128197;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Data</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$dateFormatted}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#00844A;text-align:center;font-size:15px;line-height:36px;" width="36">&#128336;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Orario</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$time}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#00844A;text-align:center;font-size:15px;line-height:36px;" width="36">&#128101;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Persone</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$partySize} {$personeLabel}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#00844A;text-align:center;font-size:15px;line-height:36px;" width="36">&#128100;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Cliente</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$firstName} {$lastName}</div>
                                        <div style="font-size:12px;color:#6c757d;">{$customerEmail} &middot; {$customerPhone}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                    </table>
                </div>

                {$notesHtml}
                {$depositHtml}

                <!-- CTA -->
                <div style="text-align:center;padding:0 32px 24px;">
                    <a href="{$dashboardUrl}" style="display:inline-block;background:#00844A;color:#ffffff;padding:12px 32px;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;">Vedi dettagli</a>
                </div>

                <!-- Footer -->
                <div style="background:#f8f9fa;padding:20px 32px;text-align:center;border-top:1px solid #f0f0f0;">
                    <div style="font-size:11px;color:#adb5bd;line-height:1.5;">
                        Notifica automatica di nuova prenotazione. Puoi disattivare queste email dalle impostazioni della dashboard.
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

        $service = new self();
        return $service->send($ownerEmail, $subjectLine, $html);
    }

    public static function sendPasswordReset(string $email, string $token, string $appName = 'Evulery'): bool
    {
        $resetUrl = url("auth/reset-password/{$token}");

        $html = <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Reset Password - {$appName}</h2>
            <p>Hai richiesto il reset della tua password.</p>
            <p>Clicca sul pulsante qui sotto per reimpostare la password:</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{$resetUrl}"
                   style="background-color: #0d6efd; color: #ffffff; padding: 12px 30px;
                          text-decoration: none; border-radius: 5px; display: inline-block;">
                    Reimposta Password
                </a>
            </p>
            <p style="color: #666; font-size: 14px;">
                Se non hai richiesto il reset, ignora questa email.<br>
                Il link scade tra 1 ora.
            </p>
            <p style="color: #999; font-size: 12px;">
                Se il pulsante non funziona, copia questo link nel browser:<br>
                <a href="{$resetUrl}">{$resetUrl}</a>
            </p>
        </div>
        HTML;

        $service = new self();
        return $service->send($email, "Reset Password - {$appName}", $html);
    }

    // ─── ORDINI ONLINE ────────────────────────────────────────

    /**
     * Email al ristoratore per nuovo ordine.
     */
    public static function sendNewOrderNotification(array $order, array $tenant): bool
    {
        $to = $tenant['email'] ?? '';
        if (!$to) return false;

        $customerName = htmlspecialchars($order['customer_name'] ?? 'Cliente', ENT_QUOTES);
        $orderNum = $order['order_number'] ?? '';
        $isDelivery = ($order['order_type'] ?? '') === 'delivery';
        $typeLabel = $isDelivery ? 'Consegna' : 'Asporto';
        $typeIcon = $isDelivery ? '&#128666;' : '&#127869;';
        $total = number_format((float)($order['total'] ?? 0), 2, ',', '.');
        $phone = htmlspecialchars($order['customer_phone'] ?? '', ENT_QUOTES);
        $pickupTime = !empty($order['pickup_time']) ? date('H:i', strtotime($order['pickup_time'])) : 'Non specificato';
        $restaurantName = htmlspecialchars($tenant['name'] ?? '', ENT_QUOTES);
        $orderUrl = url("dashboard/orders/{$order['id']}");

        $deliveryRow = '';
        if ($isDelivery && !empty($order['delivery_address'])) {
            $addr = htmlspecialchars($order['delivery_address'], ENT_QUOTES);
            $cap = htmlspecialchars($order['delivery_cap'] ?? '', ENT_QUOTES);
            $deliveryRow = <<<HTML
            <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
            <tr>
                <td style="padding:10px 0;">
                    <table cellpadding="0" cellspacing="0" border="0"><tr>
                        <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#E65100;text-align:center;font-size:15px;line-height:36px;" width="36">&#128205;</td>
                        <td style="padding-left:12px;">
                            <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Indirizzo</div>
                            <div style="font-size:14px;font-weight:600;color:#1a1d23;">{$addr} {$cap}</div>
                        </td>
                    </tr></table>
                </td>
            </tr>
            HTML;
        }

        $notesHtml = '';
        if (!empty($order['notes'])) {
            $notes = htmlspecialchars($order['notes'], ENT_QUOTES);
            $notesHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#FFF3E0;border-radius:10px;padding:14px 16px;font-size:13px;color:#E65100;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">&#128221; Note</div>
                {$notes}
            </div>
            HTML;
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f6f8;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;">
        <div style="max-width:600px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header -->
                <div style="background:#E65100;padding:32px 32px 28px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">&#128276;</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">Nuovo ordine #{$orderNum}</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.8);margin:0;">{$restaurantName}</p>
                </div>

                <!-- Greeting -->
                <div style="padding:24px 32px 8px;text-align:center;">
                    <h2 style="font-size:18px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Hai ricevuto un nuovo ordine!</h2>
                    <p style="font-size:14px;color:#6c757d;margin:0;">{$customerName} &mdash; {$typeLabel}</p>
                </div>

                <!-- Details card -->
                <div style="margin:20px 32px 24px;background:#f8f9fa;border-radius:12px;padding:20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#E65100;text-align:center;font-size:15px;line-height:36px;" width="36">&#128100;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Cliente</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$customerName}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#E65100;text-align:center;font-size:15px;line-height:36px;" width="36">&#128222;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Telefono</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$phone}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#E65100;text-align:center;font-size:15px;line-height:36px;" width="36">{$typeIcon}</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Tipo &middot; Ritiro</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$typeLabel} &middot; {$pickupTime}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        {$deliveryRow}
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#E65100;text-align:center;font-size:15px;line-height:36px;" width="36">&#128176;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Totale</div>
                                        <div style="font-size:18px;font-weight:700;color:#E65100;">&euro; {$total}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                    </table>
                </div>

                {$notesHtml}

                <!-- CTA -->
                <div style="padding:0 32px 28px;text-align:center;">
                    <a href="{$orderUrl}" style="display:inline-block;padding:14px 40px;background:#E65100;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Gestisci ordine</a>
                </div>

                <!-- Footer -->
                <div style="padding:16px 32px;border-top:1px solid #e9ecef;text-align:center;">
                    <p style="margin:0;font-size:12px;color:#adb5bd;">&copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web</p>
                </div>
            </div>
        </div>
        </body>
        </html>
        HTML;

        $service = new self();
        return $service->send($to, "Nuovo ordine #{$orderNum} - {$tenant['name']}", $html);
    }

    /**
     * Email al cliente per aggiornamento stato ordine.
     */
    public static function sendOrderStatusUpdate(array $order, array $tenant, string $newStatus): bool
    {
        $to = $order['customer_email'] ?? '';
        if (!$to) return false;

        $orderNum = $order['order_number'] ?? '';
        $statusLabel = order_status_label($newStatus);
        $restaurantName = htmlspecialchars($tenant['name'] ?? '', ENT_QUOTES);
        $customerName = htmlspecialchars(explode(' ', $order['customer_name'] ?? '')[0] ?? '', ENT_QUOTES);
        $total = number_format((float)($order['total'] ?? 0), 2, ',', '.');
        $isDelivery = ($order['order_type'] ?? '') === 'delivery';
        $typeLabel = $isDelivery ? 'Consegna' : 'Asporto';
        $restaurantPhone = htmlspecialchars($tenant['phone'] ?? '', ENT_QUOTES);

        $statusMessages = [
            'accepted'  => 'Il ristorante ha accettato il tuo ordine ed &egrave; in lavorazione.',
            'preparing' => 'Il tuo ordine &egrave; in preparazione!',
            'ready'     => ($isDelivery ? 'Il tuo ordine &egrave; in consegna!' : 'Il tuo ordine &egrave; pronto per il ritiro!'),
            'completed' => 'Il tuo ordine &egrave; stato completato. Grazie!',
            'cancelled' => 'Il tuo ordine &egrave; stato annullato.',
            'rejected'  => 'Il ristorante non ha potuto accettare il tuo ordine.',
        ];
        $msg = $statusMessages[$newStatus] ?? "Stato aggiornato: {$statusLabel}.";

        $statusIcons = [
            'accepted'  => '&#9989;',
            'preparing' => '&#127859;',
            'ready'     => '&#9989;',
            'completed' => '&#127881;',
            'cancelled' => '&#10060;',
            'rejected'  => '&#10060;',
        ];
        $statusIcon = $statusIcons[$newStatus] ?? '&#128276;';

        $statusColors = [
            'accepted'  => '#1565C0',
            'preparing' => '#E65100',
            'ready'     => '#198754',
            'completed' => '#198754',
            'cancelled' => '#dc3545',
            'rejected'  => '#dc3545',
        ];
        $headerBg = $statusColors[$newStatus] ?? '#6c757d';

        $rejectedHtml = '';
        if ($newStatus === 'rejected' && !empty($order['rejected_reason'])) {
            $reason = htmlspecialchars($order['rejected_reason'], ENT_QUOTES);
            $rejectedHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#FFF3E0;border-radius:10px;padding:14px 16px;font-size:13px;color:#E65100;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">Motivo</div>
                {$reason}
            </div>
            HTML;
        }

        $contactHtml = '';
        if ($restaurantPhone) {
            $contactHtml = <<<HTML
            <div style="padding:20px 32px;text-align:center;">
                <div style="font-size:14px;font-weight:700;color:#1a1d23;margin-bottom:4px;">{$restaurantName}</div>
                <div style="font-size:12px;color:#00844A;font-weight:600;">{$restaurantPhone}</div>
            </div>
            HTML;
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f6f8;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;">
        <div style="max-width:600px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header -->
                <div style="background:{$headerBg};padding:32px 32px 28px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">{$statusIcon}</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">Ordine #{$orderNum}</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.8);margin:0;">{$restaurantName}</p>
                </div>

                <!-- Status -->
                <div style="padding:24px 32px 8px;text-align:center;">
                    <div style="display:inline-block;padding:8px 24px;background:{$headerBg};color:#fff;border-radius:20px;font-weight:600;font-size:15px;margin-bottom:12px;">{$statusLabel}</div>
                    <p style="font-size:14px;color:#6c757d;margin:0;">Ciao {$customerName}, {$msg}</p>
                </div>

                <!-- Details card -->
                <div style="margin:20px 32px 24px;background:#f8f9fa;border-radius:12px;padding:20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$headerBg};text-align:center;font-size:15px;line-height:36px;" width="36">&#128179;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Ordine</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">#{$orderNum} &middot; {$typeLabel}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:{$headerBg};text-align:center;font-size:15px;line-height:36px;" width="36">&#128176;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Totale</div>
                                        <div style="font-size:18px;font-weight:700;color:#1a1d23;">&euro; {$total}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
                    </table>
                </div>

                {$rejectedHtml}
                {$contactHtml}

                <!-- Footer -->
                <div style="padding:16px 32px;border-top:1px solid #e9ecef;text-align:center;">
                    <p style="margin:0;font-size:12px;color:#adb5bd;">&copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web</p>
                </div>
            </div>
        </div>
        </body>
        </html>
        HTML;

        $service = new self();
        return $service->send($to, "Ordine #{$orderNum}: {$statusLabel} - {$tenant['name']}", $html, $tenant['name'] ?? null);
    }
}
