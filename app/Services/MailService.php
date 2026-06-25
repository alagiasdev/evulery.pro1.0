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
        // In richiesta web, se l'invio asincrono e' attivo (MAIL_ASYNC=1),
        // accodiamo e torniamo subito: la trasmissione SMTP la fa il worker
        // (scripts/process-outbox.php). In CLI (cron/worker/broadcast) si invia
        // sempre in sincrono. Se l'accodamento fallisce -> fallback inline, cosi'
        // non perdiamo mai un'email (es. tabella/coda non disponibile).
        $async = PHP_SAPI !== 'cli' && (string)env('MAIL_ASYNC', '0') === '1';
        if ($async) {
            try {
                MailOutbox::enqueueEmail($to, $subject, $htmlBody, $fromName, $replyTo);
                return true;
            } catch (\Throwable $e) {
                app_log('MailOutbox enqueue fallito, fallback invio inline: ' . $e->getMessage(), 'warning');
                // prosegue con la trasmissione inline qui sotto
            }
        }
        return $this->transmit($to, $subject, $htmlBody, $fromName, $replyTo);
    }

    /**
     * Trasmissione SMTP effettiva e sincrona. Usata dal worker della coda e dal
     * fallback inline. NON passa dalla coda (e' il punto in cui si invia davvero).
     */
    public function transmit(string $to, string $subject, string $htmlBody, ?string $fromName = null, ?string $replyTo = null): bool
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

        // Permanenza al tavolo: durata snapshot (fallback durata globale tenant, poi 90)
        $stayDur = (int)($reservation['duration_minutes'] ?? $tenant['table_duration'] ?? 90);
        $stayEnd = ($time && $stayDur > 0)
            ? date('H:i', strtotime($reservation['reservation_time']) + $stayDur * 60)
            : '';
        $stayText = $stayEnd ? "dalle {$time} alle {$stayEnd} (" . format_duration_label($stayDur) . ')' : '';
        // Riga riepilogo "Tavolo riservato fino alle HH:MM" (heredoc-ready)
        $stayRowHtml = '';
        if ($stayEnd !== '') {
            $stayRowHtml = <<<HTML
                        <tr><td style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr>
                            <td style="padding:10px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"><tr>
                                    <td style="width:36px;height:36px;border-radius:10px;background:#fff;color:#00844A;text-align:center;font-size:15px;line-height:36px;" width="36">&#9203;</td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;letter-spacing:.3px;">Tavolo riservato</div>
                                        <div style="font-size:15px;font-weight:600;color:#1a1d23;">{$stayText}</div>
                                    </td>
                                </tr></table>
                            </td>
                        </tr>
            HTML;
        }

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

        // Carta a garanzia section (only if present)
        $guaranteeHtml = '';
        $gStatus = $reservation['guarantee_status'] ?? 'none';
        if (in_array($gStatus, ['pending', 'secured'], true) && !empty($reservation['deposit_amount'])) {
            $penale = number_format((float)$reservation['deposit_amount'], 2, ',', '.');
            $guaranteeHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#E8F5E9;border-radius:10px;padding:14px 16px;font-size:13px;color:#2E7D32;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">&#128274; Carta a garanzia</div>
                Hai registrato una carta a garanzia: <strong>nessun addebito</strong> &egrave; stato effettuato.
                In caso di mancata presentazione, il ristorante potr&agrave; addebitare una penale di <strong>&euro;{$penale}</strong>.
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
                        {$stayRowHtml}
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
                {$guaranteeHtml}
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
     * Email "Prenotazione in attesa" — inviata al cliente subito dopo la prenotazione
     * quando questa resta in pending per via della caparra/garanzia.
     * Un solo template, riquadro azione variabile per tipo (info/link/stripe/guarantee).
     */
    public static function sendReservationPending(array $reservation, array $tenant): bool
    {
        $customerEmail = $reservation['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName     = e($reservation['first_name'] ?? '');
        $partySize     = (int)($reservation['party_size'] ?? 0);
        $personeLabel  = $partySize === 1 ? 'persona' : 'persone';
        $date          = $reservation['reservation_date'] ?? '';
        $time          = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted = self::formatDateItalian($date);
        $restaurantName = e($tenant['name'] ?? '');
        $bookingNumber = (int)($reservation['booking_number'] ?? 0);
        $token         = $reservation['manage_token'] ?? '';
        $amount        = number_format((float)($reservation['deposit_amount'] ?? 0), 2, ',', '.');
        $depositType   = $tenant['deposit_type'] ?? 'info';
        $completeUrl   = url('booking/complete/' . $token);
        $year          = date('Y');

        // Contenuto variabile per tipo di caparra
        if ($depositType === 'guarantee') {
            $circleIcon  = '&#128274;';
            $statusTitle = 'Manca solo un passaggio';
            $statusSub   = "Ciao {$firstName}, registra la carta per confermare";
            $boxBg = '#ECF7EF'; $boxBorder = '#cfe8d6'; $boxColor = '#2E7D32';
            $boxLabel = '&#128274; Carta a garanzia';
            $boxText  = "Per confermare la prenotazione devi registrare una carta. <strong>Non verr&agrave; addebitato nulla</strong>: la carta serve solo come garanzia, salvo mancata presentazione.";
            $ctaHtml  = '<a href="' . $completeUrl . '" style="display:inline-block;background:#00844A;color:#fff;padding:13px 32px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;">Completa la prenotazione</a>';
            $timerHtml = '&#9201;&#65039; Hai <strong>30 minuti</strong> dalla prenotazione per completare.<br>Se il tempo &egrave; scaduto, dalla stessa pagina potrai riprenotare il tavolo.';
        } elseif ($depositType === 'stripe') {
            $circleIcon  = '&#128179;';
            $statusTitle = 'Completa il pagamento';
            $statusSub   = "Ciao {$firstName}, paga la caparra per confermare";
            $boxBg = '#F1F0FE'; $boxBorder = '#ddd9f5'; $boxColor = '#4b40c7';
            $boxLabel = '&#128179; Caparra richiesta';
            $boxText  = "Per confermare la prenotazione versa la caparra di <strong>&euro;{$amount}</strong> con pagamento sicuro (carta, Apple Pay, Google Pay). La conferma &egrave; automatica.";
            $ctaHtml  = '<a href="' . $completeUrl . '" style="display:inline-block;background:#00844A;color:#fff;padding:13px 32px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;">Completa la prenotazione</a>';
            $timerHtml = '&#9201;&#65039; Hai <strong>30 minuti</strong> dalla prenotazione per pagare la caparra.<br>Se il tempo &egrave; scaduto, dalla stessa pagina potrai riprenotare il tavolo.';
        } elseif ($depositType === 'link') {
            $payLink = $tenant['deposit_payment_link'] ?? '';
            $circleIcon  = '&#128279;';
            $statusTitle = 'Completa il pagamento';
            $statusSub   = "Ciao {$firstName}, paga la caparra per confermare";
            $boxBg = '#FFF3E0'; $boxBorder = '#ffe0b2'; $boxColor = '#E65100';
            $boxLabel = '&#128279; Caparra via link &mdash; &euro;' . $amount;
            $boxText  = "Completa il pagamento della caparra di <strong>&euro;{$amount}</strong> tramite il link qui sotto. Indica nella causale: <strong>Prenotazione n. {$bookingNumber}</strong>.";
            $ctaHtml  = $payLink
                ? '<a href="' . e($payLink) . '" style="display:inline-block;background:#E65100;color:#fff;padding:13px 32px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;">Vai al pagamento</a>'
                : '';
            $timerHtml = '&#128221; Il ristorante confermer&agrave; la prenotazione dopo aver ricevuto il pagamento.';
        } else {
            // info / bonifico
            $bankInfo = trim((string)($tenant['deposit_bank_info'] ?? ''));
            $bankBlock = $bankInfo
                ? '<div style="background:#fff;border:1px dashed #90b8dd;border-radius:8px;padding:10px 12px;margin-top:10px;font-family:Consolas,monospace;font-size:12px;color:#0d3d66;white-space:pre-line;">' . e($bankInfo) . '</div>'
                : '';
            $circleIcon  = '&#127974;';
            $statusTitle = 'In attesa del bonifico';
            $statusSub   = "Ciao {$firstName}, completa il bonifico per confermare";
            $boxBg = '#E3F2FD'; $boxBorder = '#c5e1f7'; $boxColor = '#1565C0';
            $boxLabel = '&#127974; Caparra via bonifico &mdash; &euro;' . $amount;
            $boxText  = "Effettua un bonifico di <strong>&euro;{$amount}</strong> con le coordinate qui sotto. Causale: <strong>Caparra prenotazione n. {$bookingNumber}</strong>." . $bankBlock;
            $ctaHtml  = '';
            $timerHtml = '&#128221; La prenotazione resta valida in attesa della verifica del pagamento.';
        }

        $ctaBlock = $ctaHtml
            ? '<div style="text-align:center;padding:0 32px 6px;">' . $ctaHtml . '</div>'
            : '';
        $subjectLine = "Prenotazione in attesa - {$restaurantName}";

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f6f8;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;">
        <div style="max-width:600px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header -->
                <div style="background:#FF8F00;padding:32px 32px 28px;text-align:center;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">&#9203;</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">{$restaurantName}</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.85);margin:0;">Prenotazione in attesa</p>
                </div>

                <!-- Status -->
                <div style="padding:24px 32px 8px;text-align:center;">
                    <div style="width:56px;height:56px;border-radius:50%;background:#FFF3E0;color:#E65100;display:inline-block;line-height:56px;font-size:24px;margin-bottom:12px;">{$circleIcon}</div>
                    <h2 style="font-size:18px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{$statusTitle}</h2>
                    <p style="font-size:14px;color:#6c757d;margin:0;">{$statusSub}</p>
                </div>

                <!-- Details card -->
                <div style="margin:20px 32px 20px;background:#f8f9fa;border-radius:12px;padding:6px 18px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr><td style="padding:11px 0;font-size:13px;color:#6c757d;">Prenotazione</td><td style="padding:11px 0;font-size:14px;font-weight:600;color:#1a1d23;text-align:right;">n. {$bookingNumber}</td></tr>
                        <tr><td colspan="2" style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr><td style="padding:11px 0;font-size:13px;color:#6c757d;">Data</td><td style="padding:11px 0;font-size:14px;font-weight:600;color:#1a1d23;text-align:right;">{$dateFormatted}</td></tr>
                        <tr><td colspan="2" style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr><td style="padding:11px 0;font-size:13px;color:#6c757d;">Orario</td><td style="padding:11px 0;font-size:14px;font-weight:600;color:#1a1d23;text-align:right;">{$time}</td></tr>
                        <tr><td colspan="2" style="border-top:1px solid #e9ecef;"></td></tr>
                        <tr><td style="padding:11px 0;font-size:13px;color:#6c757d;">Persone</td><td style="padding:11px 0;font-size:14px;font-weight:600;color:#1a1d23;text-align:right;">{$partySize} {$personeLabel}</td></tr>
                    </table>
                </div>

                <!-- Action box -->
                <div style="margin:0 32px 16px;background:{$boxBg};border:1px solid {$boxBorder};border-radius:10px;padding:16px;font-size:13px;line-height:1.55;color:{$boxColor};">
                    <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:6px;">{$boxLabel}</div>
                    {$boxText}
                </div>

                {$ctaBlock}

                <!-- Timer / note -->
                <div style="margin:6px 32px 22px;background:#FFF3E0;border-radius:8px;padding:12px 14px;font-size:12.5px;color:#B5521A;text-align:center;line-height:1.5;">
                    {$timerHtml}
                </div>

                <!-- Footer -->
                <div style="background:#f8f9fa;padding:20px 32px;text-align:center;border-top:1px solid #f0f0f0;">
                    <div style="font-size:11px;color:#adb5bd;line-height:1.5;">
                        Hai ricevuto questa email perch&eacute; hai effettuato una prenotazione presso {$restaurantName}.
                    </div>
                    <div style="font-size:10px;color:#ced4da;margin-top:12px;">
                        &copy; {$year} Evulery &middot; by alagias. - Soluzioni per il web
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
     * Email al cliente quando la prenotazione resta in attesa di APPROVAZIONE
     * manuale del ristoratore (gruppi numerosi sopra la soglia configurata, o
     * modalità conferma manuale del locale). Nessun pagamento richiesto:
     * comunica che la richiesta è stata ricevuta e sarà confermata a breve.
     */
    public static function sendReservationAwaitingApproval(array $reservation, array $tenant): bool
    {
        $customerEmail = $reservation['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName      = e($reservation['first_name'] ?? '');
        $partySize      = (int)($reservation['party_size'] ?? 0);
        $personeLabel   = $partySize === 1 ? 'persona' : 'persone';
        $date           = $reservation['reservation_date'] ?? '';
        $time           = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted  = self::formatDateItalian($date);
        $restaurantName = e($tenant['name'] ?? '');
        $bookingNumber  = (int)($reservation['booking_number'] ?? 0);
        $year           = date('Y');
        $subjectLine    = "Richiesta ricevuta - {$restaurantName}";

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
        <div style="max-width:560px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                <div style="background:#00844A;padding:26px 28px;text-align:center;">
                    <div style="font-size:34px;line-height:1;">&#9203;</div>
                    <div style="color:#fff;font-size:19px;font-weight:800;margin-top:8px;">Richiesta ricevuta</div>
                    <div style="color:#d6efe0;font-size:13.5px;margin-top:4px;">Ciao {$firstName}, la confermiamo a breve</div>
                </div>
                <div style="padding:24px 28px;">
                    <p style="font-size:14.5px;color:#1a1d23;line-height:1.6;margin:0 0 16px;">
                        Abbiamo ricevuto la tua richiesta di prenotazione presso <strong>{$restaurantName}</strong>.
                        Per i gruppi numerosi la conferma &egrave; <strong>manuale</strong>: riceverai a breve
                        un'email di conferma definitiva. Per ora la prenotazione &egrave; <strong>in attesa</strong>.
                    </p>
                    <div style="background:#f8fafb;border:1px solid #eceff2;border-radius:10px;padding:14px 16px;">
                        <table style="width:100%;font-size:14px;color:#1a1d23;border-collapse:collapse;">
                            <tr><td style="padding:5px 0;color:#6c757d;">Data</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$dateFormatted}</td></tr>
                            <tr><td style="padding:5px 0;color:#6c757d;">Ora</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$time}</td></tr>
                            <tr><td style="padding:5px 0;color:#6c757d;">Persone</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$partySize} {$personeLabel}</td></tr>
                            <tr><td style="padding:5px 0;color:#6c757d;">N. prenotazione</td><td style="padding:5px 0;text-align:right;font-weight:700;">#{$bookingNumber}</td></tr>
                        </table>
                    </div>
                    <p style="font-size:12.5px;color:#8893a1;line-height:1.6;margin:16px 0 0;">
                        Se hai necessit&agrave; particolari, rispondi pure a questa email: arriver&agrave; direttamente al ristorante.
                    </p>
                </div>
            </div>
            <div style="text-align:center;color:#9aa3aa;font-size:11.5px;margin-top:16px;">&copy; {$year} {$restaurantName} &middot; powered by Evulery</div>
        </div>
        </body>
        </html>
        HTML;

        $service = new self();
        $replyTo = $tenant['email'] ?? null;
        return $service->send($customerEmail, $subjectLine, $html, $tenant['name'] ?? null, $replyTo);
    }

    /**
     * Email al cliente quando la caparra richiesta manualmente (gruppo) NON è
     * stata completata entro la finestra: la prenotazione è stata annullata e
     * il tavolo liberato. Tono cortese + invito a riprenotare.
     */
    public static function sendReservationDepositExpired(array $reservation, array $tenant): bool
    {
        $customerEmail = $reservation['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName      = e($reservation['first_name'] ?? '');
        $partySize      = (int)($reservation['party_size'] ?? 0);
        $personeLabel   = $partySize === 1 ? 'persona' : 'persone';
        $time           = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted  = self::formatDateItalian($reservation['reservation_date'] ?? '');
        $restaurantName = e($tenant['name'] ?? '');
        $bookingNumber  = (int)($reservation['booking_number'] ?? 0);
        $bookUrl        = url($tenant['slug'] ?? '');
        $year           = date('Y');
        $subjectLine    = "Prenotazione annullata - {$restaurantName}";

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
        <div style="max-width:560px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                <div style="background:#6b7280;padding:26px 28px;text-align:center;">
                    <div style="font-size:34px;line-height:1;">&#9203;</div>
                    <div style="color:#fff;font-size:19px;font-weight:800;margin-top:8px;">Tempo scaduto</div>
                    <div style="color:#e5e7eb;font-size:13.5px;margin-top:4px;">Ciao {$firstName}, la prenotazione è stata annullata</div>
                </div>
                <div style="padding:24px 28px;">
                    <p style="font-size:14.5px;color:#1a1d23;line-height:1.6;margin:0 0 16px;">
                        Non abbiamo ricevuto la caparra entro il tempo previsto, quindi la richiesta di prenotazione
                        presso <strong>{$restaurantName}</strong> è stata <strong>annullata</strong> e il tavolo è di nuovo
                        disponibile.
                    </p>
                    <div style="background:#f8fafb;border:1px solid #eceff2;border-radius:10px;padding:14px 16px;">
                        <table style="width:100%;font-size:14px;color:#9aa3aa;border-collapse:collapse;">
                            <tr><td style="padding:5px 0;">Data</td><td style="padding:5px 0;text-align:right;font-weight:700;text-decoration:line-through;">{$dateFormatted}</td></tr>
                            <tr><td style="padding:5px 0;">Ora</td><td style="padding:5px 0;text-align:right;font-weight:700;text-decoration:line-through;">{$time}</td></tr>
                            <tr><td style="padding:5px 0;">Persone</td><td style="padding:5px 0;text-align:right;font-weight:700;text-decoration:line-through;">{$partySize} {$personeLabel}</td></tr>
                            <tr><td style="padding:5px 0;">N. prenotazione</td><td style="padding:5px 0;text-align:right;font-weight:700;text-decoration:line-through;">#{$bookingNumber}</td></tr>
                        </table>
                    </div>
                    <p style="font-size:14px;color:#1a1d23;line-height:1.6;margin:16px 0;">Vuoi ancora venire da noi? Puoi prenotare di nuovo in pochi secondi:</p>
                    <div style="text-align:center;padding-bottom:4px;">
                        <a href="{$bookUrl}" style="display:inline-block;background:#00844A;color:#fff;padding:13px 32px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;">Prenota di nuovo</a>
                    </div>
                </div>
            </div>
            <div style="text-align:center;color:#9aa3aa;font-size:11.5px;margin-top:16px;">&copy; {$year} {$restaurantName} &middot; powered by Evulery</div>
        </div>
        </body>
        </html>
        HTML;

        $service = new self();
        return $service->send($customerEmail, $subjectLine, $html, $tenant['name'] ?? null, $tenant['email'] ?? null);
    }

    /**
     * Chiusura straordinaria — email al cliente: prenotazione ANNULLATA.
     * $ctx['message'] = messaggio facoltativo del ristoratore. Invito a
     * riprenotare sempre presente.
     */
    public static function sendEmergencyCancelled(array $reservation, array $tenant, array $ctx = []): bool
    {
        $customerEmail = $reservation['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName      = e($reservation['first_name'] ?? '');
        $partySize      = (int)($reservation['party_size'] ?? 0);
        $personeLabel   = $partySize === 1 ? 'persona' : 'persone';
        $time           = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted  = self::formatDateItalian($reservation['reservation_date'] ?? '');
        $restaurantName = e($tenant['name'] ?? '');
        $bookUrl        = url($tenant['slug'] ?? '');
        $year           = date('Y');
        $subjectLine    = "Prenotazione annullata - {$restaurantName}";

        $msgBlock = '';
        if (!empty($ctx['message'])) {
            $msg = nl2br(e($ctx['message']));
            $msgBlock = <<<MSG
                    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:13px 15px;margin:16px 0;font-size:14px;color:#9a3412;line-height:1.5;font-style:italic;">{$msg}</div>
            MSG;
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
        <div style="max-width:560px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                <div style="background:#dc3545;padding:26px 28px;text-align:center;">
                    <div style="font-size:34px;line-height:1;">&#9888;&#65039;</div>
                    <div style="color:#fff;font-size:19px;font-weight:800;margin-top:8px;">Siamo spiacenti</div>
                    <div style="color:#fde2e1;font-size:13.5px;margin-top:4px;">Ciao {$firstName}, dobbiamo annullare la tua prenotazione</div>
                </div>
                <div style="padding:24px 28px;">
                    <p style="font-size:14.5px;color:#1a1d23;line-height:1.6;margin:0 0 8px;">
                        Per un <strong>imprevisto</strong> siamo costretti a chiudere e ad annullare la tua prenotazione
                        presso <strong>{$restaurantName}</strong>. Ci dispiace molto per il disagio.
                    </p>
                    {$msgBlock}
                    <div style="background:#f8fafb;border:1px solid #eceff2;border-radius:10px;padding:14px 16px;">
                        <table style="width:100%;font-size:14px;color:#9aa3aa;border-collapse:collapse;">
                            <tr><td style="padding:5px 0;">Data</td><td style="padding:5px 0;text-align:right;font-weight:700;text-decoration:line-through;">{$dateFormatted}</td></tr>
                            <tr><td style="padding:5px 0;">Ora</td><td style="padding:5px 0;text-align:right;font-weight:700;text-decoration:line-through;">{$time}</td></tr>
                            <tr><td style="padding:5px 0;">Persone</td><td style="padding:5px 0;text-align:right;font-weight:700;text-decoration:line-through;">{$partySize} {$personeLabel}</td></tr>
                        </table>
                    </div>
                    <p style="font-size:14px;color:#1a1d23;line-height:1.6;margin:16px 0;">Appena risolto saremo felici di riaverti da noi. Puoi prenotare un'altra data quando vuoi:</p>
                    <div style="text-align:center;padding-bottom:4px;">
                        <a href="{$bookUrl}" style="display:inline-block;background:#00844A;color:#fff;padding:13px 32px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;">Prenota un'altra data</a>
                    </div>
                </div>
            </div>
            <div style="text-align:center;color:#9aa3aa;font-size:11.5px;margin-top:16px;">&copy; {$year} {$restaurantName} &middot; powered by Evulery</div>
        </div>
        </body>
        </html>
        HTML;

        $service = new self();
        return $service->send($customerEmail, $subjectLine, $html, $tenant['name'] ?? null, $tenant['email'] ?? null);
    }

    /**
     * Chiusura straordinaria — email al cliente: prenotazione SOSPESA (non
     * cancellata). La conferma/aggiornamento arrivera' a breve. Niente CTA di
     * riprenotazione: vogliamo che il cliente attenda.
     */
    public static function sendEmergencySuspended(array $reservation, array $tenant, array $ctx = []): bool
    {
        $customerEmail = $reservation['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName      = e($reservation['first_name'] ?? '');
        $partySize      = (int)($reservation['party_size'] ?? 0);
        $personeLabel   = $partySize === 1 ? 'persona' : 'persone';
        $time           = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted  = self::formatDateItalian($reservation['reservation_date'] ?? '');
        $restaurantName = e($tenant['name'] ?? '');
        $year           = date('Y');
        $subjectLine    = "La tua prenotazione - {$restaurantName}";

        $msgBlock = '';
        if (!empty($ctx['message'])) {
            $msg = nl2br(e($ctx['message']));
            $msgBlock = <<<MSG
                    <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:10px;padding:13px 15px;margin:16px 0;font-size:14px;color:#5b21b6;line-height:1.5;font-style:italic;">{$msg}</div>
            MSG;
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
        <div style="max-width:560px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                <div style="background:#8b5cf6;padding:26px 28px;text-align:center;">
                    <div style="font-size:34px;line-height:1;">&#9203;</div>
                    <div style="color:#fff;font-size:19px;font-weight:800;margin-top:8px;">Prenotazione in sospeso</div>
                    <div style="color:#ede9fe;font-size:13.5px;margin-top:4px;">Ciao {$firstName}, stiamo gestendo un imprevisto</div>
                </div>
                <div style="padding:24px 28px;">
                    <p style="font-size:14.5px;color:#1a1d23;line-height:1.6;margin:0 0 8px;">
                        Per un <strong>imprevisto tecnico</strong> dobbiamo momentaneamente sospendere il servizio presso
                        <strong>{$restaurantName}</strong>. La tua prenotazione <strong>non è annullata</strong>: stiamo
                        lavorando per risolvere e <strong>ti aggiorniamo al più presto</strong>.
                    </p>
                    {$msgBlock}
                    <div style="background:#f8fafb;border:1px solid #eceff2;border-radius:10px;padding:14px 16px;">
                        <table style="width:100%;font-size:14px;color:#6c757d;border-collapse:collapse;">
                            <tr><td style="padding:5px 0;">Data</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$dateFormatted}</td></tr>
                            <tr><td style="padding:5px 0;">Ora</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$time}</td></tr>
                            <tr><td style="padding:5px 0;">Persone</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$partySize} {$personeLabel}</td></tr>
                        </table>
                    </div>
                    <p style="font-size:13.5px;color:#6c757d;line-height:1.6;margin:16px 0 0;">Ti ricontattiamo a brevissimo per confermarti se possiamo accoglierti. Grazie per la pazienza.</p>
                </div>
            </div>
            <div style="text-align:center;color:#9aa3aa;font-size:11.5px;margin-top:16px;">&copy; {$year} {$restaurantName} &middot; powered by Evulery</div>
        </div>
        </body>
        </html>
        HTML;

        $service = new self();
        return $service->send($customerEmail, $subjectLine, $html, $tenant['name'] ?? null, $tenant['email'] ?? null);
    }

    /**
     * Chiusura straordinaria — email al cliente: servizio RIPRISTINATO, la
     * prenotazione (futura) e' di nuovo confermata. "Tutto risolto".
     */
    public static function sendEmergencyRecovered(array $reservation, array $tenant): bool
    {
        $customerEmail = $reservation['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName      = e($reservation['first_name'] ?? '');
        $partySize      = (int)($reservation['party_size'] ?? 0);
        $personeLabel   = $partySize === 1 ? 'persona' : 'persone';
        $time           = substr($reservation['reservation_time'] ?? '', 0, 5);
        $dateFormatted  = self::formatDateItalian($reservation['reservation_date'] ?? '');
        $restaurantName = e($tenant['name'] ?? '');
        $year           = date('Y');
        $subjectLine    = "Buone notizie, ti aspettiamo! - {$restaurantName}";

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f4f6f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
        <div style="max-width:560px;margin:0 auto;padding:24px 16px;">
            <div style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                <div style="background:#00844A;padding:26px 28px;text-align:center;">
                    <div style="font-size:34px;line-height:1;">&#9989;</div>
                    <div style="color:#fff;font-size:19px;font-weight:800;margin-top:8px;">Tutto risolto!</div>
                    <div style="color:#d6f0e2;font-size:13.5px;margin-top:4px;">Ciao {$firstName}, la tua prenotazione è confermata</div>
                </div>
                <div style="padding:24px 28px;">
                    <p style="font-size:14.5px;color:#1a1d23;line-height:1.6;margin:0 0 16px;">
                        Buone notizie: il servizio presso <strong>{$restaurantName}</strong> è di nuovo operativo e la tua
                        prenotazione è <strong>confermata</strong>. Ti aspettiamo!
                    </p>
                    <div style="background:#f0faf5;border:1px solid #cdebd9;border-radius:10px;padding:14px 16px;">
                        <table style="width:100%;font-size:14px;color:#1a1d23;border-collapse:collapse;">
                            <tr><td style="padding:5px 0;color:#6c757d;">Data</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$dateFormatted}</td></tr>
                            <tr><td style="padding:5px 0;color:#6c757d;">Ora</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$time}</td></tr>
                            <tr><td style="padding:5px 0;color:#6c757d;">Persone</td><td style="padding:5px 0;text-align:right;font-weight:700;">{$partySize} {$personeLabel}</td></tr>
                        </table>
                    </div>
                    <p style="font-size:13.5px;color:#6c757d;line-height:1.6;margin:16px 0 0;">Grazie per la pazienza. A presto!</p>
                </div>
            </div>
            <div style="text-align:center;color:#9aa3aa;font-size:11.5px;margin-top:16px;">&copy; {$year} {$restaurantName} &middot; powered by Evulery</div>
        </div>
        </body>
        </html>
        HTML;

        $service = new self();
        return $service->send($customerEmail, $subjectLine, $html, $tenant['name'] ?? null, $tenant['email'] ?? null);
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

        // Deposit / guarantee info
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
        } elseif (in_array($reservation['guarantee_status'] ?? 'none', ['pending', 'secured'], true) && !empty($reservation['deposit_amount'])) {
            $penale = number_format((float)$reservation['deposit_amount'], 2, ',', '.');
            $gSecured = ($reservation['guarantee_status'] ?? '') === 'secured';
            $gLine = $gSecured
                ? "Carta registrata. Penale no-show addebitabile: &euro;{$penale}."
                : "In attesa che il cliente registri la carta. Penale no-show: &euro;{$penale}.";
            $depositHtml = <<<HTML
            <div style="margin:0 32px 24px;background:#E8F5E9;border-radius:10px;padding:14px 16px;font-size:13px;color:#2E7D32;">
                <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;">Carta a garanzia</div>
                {$gLine}
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

    /**
     * Send review request email to customer after their visit.
     */
    public static function sendReviewRequest(array $reviewRequest, array $tenant, array $customer, ?array $reservation): bool
    {
        $customerEmail = $customer['email'] ?? '';
        if (!$customerEmail) {
            return false;
        }

        $firstName       = e($customer['first_name'] ?? '');
        $restaurantName  = e($tenant['name'] ?? '');
        $slug            = $tenant['slug'] ?? '';
        $token           = $reviewRequest['token'] ?? '';
        $platformLabel   = e($tenant['review_platform_label'] ?? '');

        // Email template vars
        $subjectTemplate = $tenant['review_email_subject'] ?? 'Come è andata da {ristorante}?';
        $bodyTemplate    = $tenant['review_email_body'] ?? "Ciao {nome_cliente},\n\ngrazie per aver cenato da {ristorante}! Ci farebbe piacere sapere come è stata la tua esperienza.";
        $ctaText         = e($tenant['review_email_cta'] ?? 'Lascia una recensione');

        // Reservation details
        $dateFormatted = '';
        $timeFormatted = '';
        if ($reservation) {
            $dateFormatted = self::formatDateItalian($reservation['reservation_date'] ?? '');
            $timeFormatted = substr($reservation['reservation_time'] ?? '', 0, 5);
        }

        // Replace placeholders
        $replacements = [
            '{ristorante}'        => $restaurantName,
            '{nome_cliente}'      => $firstName,
            '{data_prenotazione}' => $dateFormatted ? "$dateFormatted ore $timeFormatted" : '',
        ];
        $subjectLine = str_replace(array_keys($replacements), array_values($replacements), $subjectTemplate);
        $bodyText    = str_replace(array_keys($replacements), array_values($replacements), $bodyTemplate);
        $bodyHtml    = nl2br(e($bodyText));

        // CTA label
        $ctaLabel = $ctaText;
        if ($platformLabel) {
            $ctaLabel = "$ctaText su $platformLabel";
        }

        // URLs
        $baseUrl   = rtrim(env('APP_URL', ''), '/');
        $reviewUrl = "{$baseUrl}/{$slug}/review?t={$token}";
        $pixelUrl  = "{$baseUrl}/{$slug}/review/open?t={$token}";

        // Unsubscribe
        $unsubToken = $customer['unsubscribe_token'] ?? '';
        $unsubUrl   = $unsubToken ? "{$baseUrl}/email/unsubscribe/{$unsubToken}" : '';
        $unsubHtml  = $unsubUrl
            ? "<a href=\"{$unsubUrl}\" style=\"color:#adb5bd;text-decoration:underline;\">Non desidero ricevere queste email</a><br>"
            : '';

        // Reservation details card
        $detailsHtml = '';
        if ($reservation && $dateFormatted) {
            $partySize = (int)($reservation['party_size'] ?? 0);
            $personeLabel = $partySize === 1 ? 'persona' : 'persone';
            $detailsHtml = <<<HTML
            <div style="background:#f8f9fa;border-radius:10px;padding:14px 16px;margin:0 32px 20px;font-size:13px;color:#495057;text-align:center;">
                <span style="color:#6c757d;">&#128197;</span> {$dateFormatted}, ore {$timeFormatted} &middot; {$partySize} {$personeLabel}
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
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;display:inline-block;line-height:48px;font-size:22px;margin-bottom:12px;">&#11088;</div>
                    <h1 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 4px;">{$restaurantName}</h1>
                    <p style="font-size:13px;color:rgba(255,255,255,.8);margin:0;">Ti ringraziamo per averci scelto!</p>
                </div>

                <!-- Body -->
                <div style="padding:28px 32px 12px;">
                    <p style="font-size:15px;line-height:1.6;color:#1a1d23;margin:0 0 16px;">{$bodyHtml}</p>
                </div>

                {$detailsHtml}

                <!-- CTA -->
                <div style="text-align:center;padding:8px 32px 28px;">
                    <a href="{$reviewUrl}" style="display:inline-block;background:#00844A;color:#ffffff;padding:14px 36px;border-radius:10px;font-size:15px;font-weight:700;text-decoration:none;">{$ctaLabel}</a>
                </div>

                <!-- Footer -->
                <div style="padding:16px 32px;border-top:1px solid #e9ecef;text-align:center;">
                    <p style="margin:0 0 6px;font-size:11px;color:#adb5bd;">
                        {$unsubHtml}
                        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
                    </p>
                </div>
            </div>
        </div>
        <!-- Open tracking pixel -->
        <img src="{$pixelUrl}" width="1" height="1" style="display:none;" alt="">
        </body>
        </html>
        HTML;

        $service = new self();
        $replyTo = $tenant['email'] ?? null;
        return $service->send($customerEmail, $subjectLine, $html, $restaurantName, $replyTo);
    }

    /**
     * Send feedback reply from restaurant to customer.
     */
    public static function sendFeedbackReply(array $feedback, array $tenant, string $replyText): bool
    {
        $customerEmail = $feedback['email'] ?? '';
        if (!$customerEmail) {
            return false; // anonymous review (QR/NFC), no email available
        }

        $firstName      = e($feedback['first_name'] ?? '');
        $restaurantName = e($tenant['name'] ?? 'Il ristorante');
        $rating         = (int)($feedback['rating'] ?? 0);
        $stars          = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
        $originalText   = e($feedback['feedback_text'] ?? '');
        $replyEsc       = nl2br(e($replyText));
        $replyToEmail   = $tenant['email'] ?? null;

        $subject = "Risposta al tuo feedback — {$restaurantName}";

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family:Arial,sans-serif;background:#f5f6f8;margin:0;padding:20px;">
            <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);">
                <div style="background:#00844A;color:#fff;padding:24px;text-align:center;">
                    <h1 style="margin:0;font-size:1.3rem;">Risposta dal ristorante</h1>
                </div>
                <div style="padding:28px;">
                    <p style="font-size:1rem;color:#1a1d23;">Ciao {$firstName},</p>
                    <p style="font-size:.95rem;color:#495057;line-height:1.6;">
                        Grazie per il tempo che hai dedicato a lasciarci un feedback. Volevamo risponderti personalmente.
                    </p>

                    <div style="background:#f8f9fa;border-left:3px solid #FFC107;padding:16px;border-radius:8px;margin:20px 0;">
                        <div style="font-size:.78rem;color:#6c757d;margin-bottom:6px;">Il tuo feedback ({$stars})</div>
                        <div style="font-size:.9rem;color:#495057;font-style:italic;">"{$originalText}"</div>
                    </div>

                    <div style="background:#e8f5e9;border-left:3px solid #00844A;padding:16px;border-radius:8px;margin:20px 0;">
                        <div style="font-size:.78rem;color:#2e7d32;font-weight:600;margin-bottom:6px;">Risposta da {$restaurantName}</div>
                        <div style="font-size:.95rem;color:#1a1d23;line-height:1.6;">{$replyEsc}</div>
                    </div>

                    <p style="font-size:.88rem;color:#6c757d;line-height:1.6;margin-top:24px;">
                        Speriamo di rivederti presto.<br>
                        <strong>{$restaurantName}</strong>
                    </p>
                </div>
                <div style="background:#fafbfc;padding:16px;text-align:center;font-size:.72rem;color:#adb5bd;border-top:1px solid #f0f0f0;">
                    Questa email &egrave; stata inviata in risposta al feedback che hai lasciato.
                </div>
            </div>
        </body>
        </html>
        HTML;

        $service = new self();
        return $service->send($customerEmail, $subject, $html, $restaurantName, $replyToEmail);
    }

    /**
     * Notifica al reseller: nuovo lead assegnato dall'admin.
     */
    public static function sendLeadAssignedToReseller(array $reseller, array $lead, ?string $adminName = null): bool
    {
        $email = $reseller['email'] ?? '';
        if (!$email) {
            return false;
        }

        $firstName    = e($reseller['first_name'] ?? 'Reseller');
        $leadName     = e($lead['name'] ?? '');
        $restaurant   = e($lead['restaurant'] ?? '');
        $leadEmail    = e($lead['email'] ?? '');
        $leadPhone    = e($lead['phone'] ?? '');
        $messageRaw   = trim((string)($lead['message'] ?? ''));
        $messageHtml  = $messageRaw ? '<p style="margin:8px 0 0;color:#495057;font-style:italic;">"' . nl2br(e($messageRaw)) . '"</p>' : '';
        $leadUrl      = url('reseller/leads/' . (int)($lead['id'] ?? 0));
        $assignedBy   = $adminName ? ' da ' . e($adminName) : '';
        $appName      = e(env('APP_NAME', 'Evulery'));

        $html = <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#fff;">
            <div style="background:#00844A;color:#fff;padding:24px 28px;">
                <div style="font-size:13px;text-transform:uppercase;letter-spacing:1px;opacity:.85;">Nuovo lead assegnato</div>
                <h1 style="font-size:22px;margin:6px 0 0;font-weight:800;">{$restaurant}</h1>
            </div>
            <div style="padding:24px 28px;color:#1a1d23;font-size:14px;line-height:1.6;">
                <p>Ciao <strong>{$firstName}</strong>,</p>
                <p>ti &egrave; stato assegnato{$assignedBy} un nuovo lead: <strong>{$restaurant}</strong>.</p>

                <div style="background:#fafbfc;border:1px solid #e9ecef;border-radius:10px;padding:16px 18px;margin:18px 0;">
                    <div style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;font-weight:700;margin-bottom:6px;">Contatti</div>
                    <div><strong>{$leadName}</strong></div>
                    <div style="color:#495057;">
                        <a href="mailto:{$leadEmail}" style="color:#00844A;text-decoration:none;">{$leadEmail}</a>
                        <br><a href="tel:{$leadPhone}" style="color:#00844A;text-decoration:none;">{$leadPhone}</a>
                    </div>
                    {$messageHtml}
                </div>

                <p style="text-align:center;margin:24px 0;">
                    <a href="{$leadUrl}" style="display:inline-block;background:#00844A;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">
                        Apri il lead nella tua area
                    </a>
                </p>

                <p style="color:#6c757d;font-size:13px;margin-top:24px;">
                    Tip: contatta il lead entro 24 ore. La velocit&agrave; di risposta &egrave; il fattore pi&ugrave; correlato alla conversione.
                </p>
            </div>
            <div style="background:#f5f6f8;padding:14px 28px;font-size:12px;color:#6c757d;text-align:center;">
                Questo &egrave; un avviso automatico da {$appName}.
            </div>
        </div>
        HTML;

        $service = new self();
        return $service->send($email, "Nuovo lead assegnato: {$lead['restaurant']}", $html);
    }

    /**
     * Notifica al reseller: richiesta ricarica crediti APPROVATA.
     */
    public static function sendCreditRequestApproved(array $reseller, array $request): bool
    {
        $email = $reseller['email'] ?? '';
        if (!$email) {
            return false;
        }

        $firstName  = e($reseller['first_name'] ?? 'Reseller');
        $tenantName = e($request['tenant_name'] ?? '');
        $credits    = number_format((int)$request['credits_requested'], 0, ',', '.');
        $newBalance = number_format((int)$request['email_credits_balance'], 0, ',', '.');
        $url        = url('reseller/credits');
        $appName    = e(env('APP_NAME', 'Evulery'));

        $html = <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#fff;">
            <div style="background:#00844A;color:#fff;padding:24px 28px;">
                <div style="font-size:13px;text-transform:uppercase;letter-spacing:1px;opacity:.85;">Ricarica approvata</div>
                <h1 style="font-size:22px;margin:6px 0 0;font-weight:800;">+{$credits} crediti email</h1>
            </div>
            <div style="padding:24px 28px;color:#1a1d23;font-size:14px;line-height:1.6;">
                <p>Ciao <strong>{$firstName}</strong>,</p>
                <p>la tua richiesta di ricarica per <strong>{$tenantName}</strong> &egrave; stata approvata. I crediti sono gi&agrave; disponibili sul saldo del cliente.</p>

                <div style="background:#fafbfc;border:1px solid #e9ecef;border-radius:10px;padding:16px 18px;margin:18px 0;">
                    <div style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;font-weight:700;margin-bottom:6px;">Saldo attuale</div>
                    <div style="font-size:22px;font-weight:800;color:#00844A;">{$newBalance} crediti</div>
                </div>

                <p style="text-align:center;margin:24px 0;">
                    <a href="{$url}" style="display:inline-block;background:#00844A;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">
                        Vai allo storico ricariche
                    </a>
                </p>
            </div>
            <div style="background:#f5f6f8;padding:14px 28px;font-size:12px;color:#6c757d;text-align:center;">
                Avviso automatico da {$appName}.
            </div>
        </div>
        HTML;

        $service = new self();
        return $service->send($email, "Ricarica approvata: +{$credits} crediti per {$request['tenant_name']}", $html);
    }

    /**
     * Notifica al reseller: richiesta ricarica crediti RIFIUTATA.
     */
    public static function sendCreditRequestRejected(array $reseller, array $request, string $reason): bool
    {
        $email = $reseller['email'] ?? '';
        if (!$email) {
            return false;
        }

        $firstName  = e($reseller['first_name'] ?? 'Reseller');
        $tenantName = e($request['tenant_name'] ?? '');
        $credits    = number_format((int)$request['credits_requested'], 0, ',', '.');
        $reasonHtml = nl2br(e($reason));
        $url        = url('reseller/credits');
        $appName    = e(env('APP_NAME', 'Evulery'));

        $html = <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#fff;">
            <div style="background:#C62828;color:#fff;padding:24px 28px;">
                <div style="font-size:13px;text-transform:uppercase;letter-spacing:1px;opacity:.85;">Ricarica rifiutata</div>
                <h1 style="font-size:22px;margin:6px 0 0;font-weight:800;">{$credits} crediti — {$tenantName}</h1>
            </div>
            <div style="padding:24px 28px;color:#1a1d23;font-size:14px;line-height:1.6;">
                <p>Ciao <strong>{$firstName}</strong>,</p>
                <p>la tua richiesta di ricarica per <strong>{$tenantName}</strong> non &egrave; stata approvata.</p>

                <div style="background:#FFF3E0;border-left:3px solid #f57c00;border-radius:6px;padding:14px 18px;margin:18px 0;">
                    <div style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#E65100;font-weight:700;margin-bottom:6px;">Motivo</div>
                    <div style="color:#5d4037;">{$reasonHtml}</div>
                </div>

                <p>Per dubbi o chiarimenti, contatta direttamente l'amministratore.</p>

                <p style="text-align:center;margin:24px 0;">
                    <a href="{$url}" style="display:inline-block;background:#fff;color:#00844A;padding:12px 28px;border:2px solid #00844A;border-radius:8px;text-decoration:none;font-weight:600;">
                        Apri lo storico
                    </a>
                </p>
            </div>
            <div style="background:#f5f6f8;padding:14px 28px;font-size:12px;color:#6c757d;text-align:center;">
                Avviso automatico da {$appName}.
            </div>
        </div>
        HTML;

        $service = new self();
        return $service->send($email, "Ricarica rifiutata: {$credits} crediti per {$request['tenant_name']}", $html);
    }

    /**
     * Send a plain text email (utility for system notifications, demo requests, etc.).
     */
    public static function sendRawEmail(string $to, string $subject, string $textBody, ?string $replyTo = null, ?string $replyToName = null): bool
    {
        $html = '<div style="font-family:sans-serif;font-size:14px;line-height:1.6;color:#333;">'
            . nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8'))
            . '</div>';

        $service = new self();
        return $service->send($to, $subject, $html, null, $replyTo);
    }
}
