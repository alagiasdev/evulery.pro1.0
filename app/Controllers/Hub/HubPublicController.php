<?php

namespace App\Controllers\Hub;

use App\Core\Request;
use App\Core\Response;
use App\Models\HubAction;
use App\Models\HubSettings;
use App\Models\Tenant;
use App\Services\HubService;

/**
 * Public Vetrina Digitale page rendered at /{slug}/hub.
 * Standalone layout (no dashboard chrome, no auth required).
 */
class HubPublicController
{
    public function show(Request $request): void
    {
        $slug = (string)$request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        // Tenant inesistente: 404 (caso vero "non esiste")
        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
            return;
        }

        // Tenant senza servizio attivo (Starter, sospeso, hub OFF):
        // mostriamo una pagina "torna al sito" friendly invece di 404.
        // Chi scansiona il QR vede comunque qualcosa, non un errore.
        $hasService = $tenantModel->canUseService((int)$tenant['id'], 'vetrina_digitale');
        $expired = (bool)$tenantModel->getExpiredSubscription((int)$tenant['id']);
        $settings = (new HubSettings())->findByTenant((int)$tenant['id']);
        $isEnabled = $settings && !empty($settings['enabled']);

        if (!$hasService || $expired || !$isEnabled) {
            $this->renderUnavailable($tenant);
            return;
        }

        $hubService = new HubService();
        $rendered = $hubService->getRenderableActions($tenant, $settings);
        $colors = (new HubSettings())->resolveColors($settings);

        // Standalone view (2-arg view = no layout)
        view('hub/public', [
            'tenant'   => $tenant,
            'settings' => $settings,
            'colors'   => $colors,
            'hero'     => $rendered['hero'],
            'items'    => $rendered['items'],
            'fontFamily' => $this->resolveFontFamily($settings['custom_font'] ?? null),
        ]);
    }

    private function resolveFontFamily(?string $key): string
    {
        return match ($key) {
            'serif'        => "'Playfair Display', Georgia, serif",
            'merriweather' => "'Merriweather', Georgia, serif",
            'caveat'       => "'Caveat', cursive",
            // Default: Inter (font dell'identità Evulery, coerente con landing/marchio)
            default        => "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
        };
    }

    /**
     * Friendly "Vetrina non attiva" page with CTA to booking widget.
     * Used when the tenant is on Starter, subscription expired, or hub disabled.
     */
    private function renderUnavailable(array $tenant): void
    {
        $bookingUrl = url($tenant['slug']);
        view('hub/unavailable', [
            'tenantName'    => $tenant['name'],
            'tenantPhone'   => $tenant['phone'] ?? '',
            'tenantEmail'   => $tenant['email'] ?? '',
            'tenantAddress' => $tenant['address'] ?? '',
            'bookingUrl'    => $bookingUrl,
        ]);
    }
}
