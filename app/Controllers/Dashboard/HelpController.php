<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Services\MailService;

class HelpController
{
    /**
     * Load help sections metadata + body from single config file.
     */
    private function sections(): array
    {
        static $cached = null;
        if ($cached === null) {
            $cached = require BASE_PATH . '/views/dashboard/help/_sections.php';
        }
        return $cached;
    }

    /**
     * GET /dashboard/help — Home guida (card grid).
     */
    public function index(Request $request): void
    {
        view('dashboard/help/index', [
            'title'      => 'Guida Evulery',
            'activeMenu' => 'help',
            'tenant'     => TenantResolver::current(),
            'sections'   => $this->sections(),
        ], 'dashboard');
    }

    /**
     * GET /dashboard/help/{slug} — Dettaglio singolo articolo.
     */
    public function show(Request $request): void
    {
        $slug     = (string)$request->param('slug');
        $sections = $this->sections();

        if (!isset($sections[$slug])) {
            flash('danger', 'Articolo non trovato.');
            Response::redirect(url('dashboard/help'));
        }

        $section = $sections[$slug];

        // Related: other sections from same category, excluding current (max 4)
        $related = [];
        foreach ($sections as $s => $data) {
            if ($s === $slug) continue;
            if (($data['category'] ?? '') === ($section['category'] ?? '')) {
                $related[$s] = $data;
                if (count($related) >= 4) break;
            }
        }
        // If less than 4 related, top up with other sections
        if (count($related) < 4) {
            foreach ($sections as $s => $data) {
                if ($s === $slug || isset($related[$s])) continue;
                $related[$s] = $data;
                if (count($related) >= 4) break;
            }
        }

        view('dashboard/help/detail', [
            'title'      => ($section['title'] ?? 'Guida') . ' — Guida Evulery',
            'activeMenu' => 'help',
            'tenant'     => TenantResolver::current(),
            'slug'       => $slug,
            'section'    => $section,
            'related'    => $related,
        ], 'dashboard');
    }

    /**
     * POST /dashboard/help/feedback — Invia feedback su un articolo via email.
     * No DB: solo email a SUPPORT_EMAIL con dati tenant/utente/sezione/commento.
     */
    public function feedback(Request $request): void
    {
        $section = trim((string)$request->input('section', ''));
        $value   = trim((string)$request->input('value', ''));
        $comment = trim((string)$request->input('comment', ''));

        if ($section === '' || !in_array($value, ['up', 'down'], true)) {
            Response::json(['success' => false, 'error' => 'Parametri non validi.'], 422);
        }

        $tenant = TenantResolver::current();
        $user   = Auth::user();

        $subject = ($value === 'up' ? '[👍 Guida]' : '[👎 Guida]') . ' ' . ($tenant['name'] ?? 'Tenant')
            . ' — sezione ' . $section;

        $body = "Nuovo feedback sulla guida Evulery\n\n"
            . 'Sezione: ' . $section . "\n"
            . 'Voto: ' . ($value === 'up' ? '👍 Utile' : '👎 Non utile') . "\n"
            . 'Ristorante: ' . ($tenant['name'] ?? '-') . ' (#' . ($tenant['id'] ?? '-') . ")\n"
            . 'Utente: ' . ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')
            . ' <' . ($user['email'] ?? '-') . ">\n"
            . 'Data: ' . date('d/m/Y H:i') . "\n";

        if ($comment !== '') {
            $body .= "\nCommento del ristoratore:\n" . mb_substr($comment, 0, 1000) . "\n";
        }

        $supportEmail = env('SUPPORT_EMAIL', 'info@evulery.it');
        $replyTo = $user['email'] ?? null;
        $replyName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

        $sent = MailService::sendRawEmail($supportEmail, $subject, $body, $replyTo, $replyName ?: null);

        if (!$sent) {
            app_log("Help feedback email failed — section={$section} value={$value}", 'error');
            Response::json(['success' => false, 'error' => 'Invio fallito.'], 500);
        }

        app_log("Help feedback received — section={$section} value={$value} tenant={$tenant['id']}", 'info');

        Response::json(['success' => true]);
    }
}
