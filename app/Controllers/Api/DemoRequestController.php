<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\DemoRequest;
use App\Services\MailService;
use App\Services\RateLimit;

class DemoRequestController
{
    /**
     * POST /api/v1/demo-request
     *
     * Pipeline lead:
     *   1. Valida campi + email format + reCAPTCHA
     *   2. Rate limit IP-based (max 3 richieste/ora)
     *   3. Anti-duplicato visibile (stessa email entro 24h)
     *   4. Salva in DB (demo_requests + activity log "created")
     *   5. Email notifica admin (a SUPPORT_EMAIL)
     *   6. Email conferma cliente ("Ti contattiamo a breve")
     */
    public function store(Request $request): void
    {
        // CORS per landing page (evulery.it -> dash.evulery.it)
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = ['https://evulery.it', 'https://www.evulery.it', 'http://localhost'];
        if (in_array($origin, $allowed)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $data = $request->all();

        // 1. Validazione campi obbligatori
        $name       = trim($data['name'] ?? '');
        $restaurant = trim($data['restaurant'] ?? '');
        $email      = trim($data['email'] ?? '');
        $phone      = trim($data['phone'] ?? '');
        $message    = trim($data['message'] ?? '');
        $token      = trim($data['recaptcha_token'] ?? '');

        if (!$name || !$restaurant || !$email || !$phone) {
            Response::json(['success' => false, 'error' => 'Compila tutti i campi obbligatori.'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['success' => false, 'error' => 'Indirizzo email non valido.'], 422);
        }

        // 2. Rate limit IP-based: max 3 richieste/ora per IP
        $ip = $request->ip();
        $limiter = new RateLimit();
        if (!$limiter->checkCustom($ip, 'demo_form', 3, 3600)) {
            Response::json([
                'success' => false,
                'error'   => 'Troppe richieste dal tuo dispositivo. Riprova tra qualche minuto.',
            ], 429);
        }

        // 3. Verifica reCAPTCHA v3
        $secretKey = env('RECAPTCHA_SECRET_KEY', '');
        if ($secretKey && $token) {
            $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            $response = @file_get_contents($verifyUrl . '?' . http_build_query([
                'secret'   => $secretKey,
                'response' => $token,
            ]));

            if ($response) {
                $result = json_decode($response, true);
                if (!($result['success'] ?? false) || ($result['score'] ?? 0) < 0.3) {
                    Response::json(['success' => false, 'error' => 'Verifica anti-spam fallita. Riprova.'], 403);
                }
            }
        }

        // 4. Anti-duplicato visibile: stessa email negli ultimi 24h
        $leadModel = new DemoRequest();
        if ($leadModel->findRecentDuplicate($email, 24)) {
            // Comunque registriamo il record per non perderlo + recorda il rate limit
            $limiter->recordCustom($ip, 'demo_form');
            Response::json([
                'success' => true,
                'duplicate' => true,
                'message' => 'Hai già inviato una richiesta nelle ultime 24 ore. Ti contattiamo a breve!',
            ]);
        }

        // 5. Salva nel DB
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        $utmSource = trim($data['utm_source'] ?? '');

        try {
            $leadId = $leadModel->create([
                'name'       => $name,
                'restaurant' => $restaurant,
                'email'      => $email,
                'phone'      => $phone,
                'message'    => $message ?: null,
                'ip_address' => $ip,
                'referrer'   => $referrer,
                'utm_source' => $utmSource ?: null,
            ]);

            $leadModel->logActivity(
                $leadId,
                'created',
                'Lead ricevuto dal form pubblico evulery.it',
                null
            );
        } catch (\Throwable $e) {
            app_log('Demo request DB save failed: ' . $e->getMessage(), 'error');
            Response::json(['success' => false, 'error' => 'Errore interno. Riprova tra poco.'], 500);
        }

        // Registra nel rate limit DOPO il successo (prima del rate limit per evitare di bloccare il primo invio)
        $limiter->recordCustom($ip, 'demo_form');

        // 6. Email notifica admin
        $supportEmail = env('SUPPORT_EMAIL', 'info@evulery.it');
        $adminSubject = "Nuova richiesta demo - {$restaurant}";
        $adminBody = "Nuova richiesta demo dal sito evulery.it\n\n"
            . "Nome: {$name}\n"
            . "Ristorante: {$restaurant}\n"
            . "Email: {$email}\n"
            . "Telefono: {$phone}\n"
            . ($message ? "Messaggio: {$message}\n" : '')
            . "\nLead ID: #{$leadId}\n"
            . "Data: " . date('d/m/Y H:i') . "\n"
            . "IP: {$ip}\n"
            . ($referrer ? "Referrer: {$referrer}\n" : '')
            . "\nGestisci il lead: " . url("admin/leads/{$leadId}") . "\n";

        $adminSent = MailService::sendRawEmail($supportEmail, $adminSubject, $adminBody, $email, $name);
        if (!$adminSent) {
            app_log("Demo request: admin email failed for lead #{$leadId} ({$email})", 'warning');
        }

        // 7. Email conferma cliente
        $clientSubject = "Abbiamo ricevuto la tua richiesta - Evulery";
        $clientBody = "Ciao {$name},\n\n"
            . "grazie per averci contattato.\n\n"
            . "Abbiamo ricevuto la tua richiesta di demo per {$restaurant} e ti contattiamo a breve "
            . "per organizzare una chiamata di 30 minuti, in italiano e senza impegno.\n\n"
            . "Nel frattempo, se vuoi farti un'idea concreta del risparmio rispetto agli aggregatori "
            . "del tuo ristorante, abbiamo un calcolatore online: https://evulery.it\n\n"
            . "A presto,\n"
            . "Il team Evulery\n\n"
            . "---\n"
            . "Evulery - Il software italiano per le prenotazioni del tuo ristorante\n"
            . "Pieni la sera, felici la mattina.\n"
            . "https://evulery.it";

        $clientSent = MailService::sendRawEmail($email, $clientSubject, $clientBody, $supportEmail, 'Evulery');
        if (!$clientSent) {
            app_log("Demo request: client confirmation email failed for lead #{$leadId} ({$email})", 'warning');
        }

        app_log("Demo request received: lead #{$leadId} | {$name} - {$restaurant} - {$email}", 'info');

        Response::json([
            'success' => true,
            'lead_id' => $leadId,
            'message' => 'Richiesta ricevuta! Ti contattiamo a breve. Controlla la tua email.',
        ]);
    }
}
