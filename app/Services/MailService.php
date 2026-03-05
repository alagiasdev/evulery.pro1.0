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

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            app_log("Mail send error: " . $e->getMessage());
            return false;
        }
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
}
