<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\MailService;

class DemoRequestController
{
    /**
     * POST /api/v1/demo-request
     * Validates reCAPTCHA, sends email to support.
     */
    public function store(Request $request): void
    {
        // CORS for landing page (evulery.it → dash.evulery.it)
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

        // Validate required fields
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

        // Verify reCAPTCHA v3
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

        // Send email to support
        $supportEmail = env('SUPPORT_EMAIL', 'info@evulery.it');

        $subject = "Nuova richiesta demo — {$restaurant}";
        $body = "Nuova richiesta demo dal sito evulery.it\n\n"
            . "Nome: {$name}\n"
            . "Ristorante: {$restaurant}\n"
            . "Email: {$email}\n"
            . "Telefono: {$phone}\n"
            . ($message ? "Messaggio: {$message}\n" : '')
            . "\nData: " . date('d/m/Y H:i') . "\n"
            . "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

        $sent = MailService::sendRawEmail($supportEmail, $subject, $body, $email, $name);

        if (!$sent) {
            app_log("Demo request email failed: {$email} — {$restaurant}", 'error');
            Response::json(['success' => false, 'error' => 'Errore nell\'invio. Riprova o contattaci direttamente.'], 500);
        }

        app_log("Demo request received: {$name} — {$restaurant} — {$email}", 'info');

        Response::json([
            'success' => true,
            'message' => 'Richiesta inviata! Ti contatteremo entro 24 ore.',
        ]);
    }
}
