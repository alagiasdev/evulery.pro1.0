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

        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
            return;
        }

        // Service gating: same as menu/order/etc — show graceful unavailable page
        if (!$tenantModel->canUseService((int)$tenant['id'], 'vetrina_digitale')) {
            Response::notFound();
            return;
        }

        // Subscription expiry check (same pattern as MenuPageController)
        if ($tenantModel->getExpiredSubscription((int)$tenant['id'])) {
            Response::notFound();
            return;
        }

        $settings = (new HubSettings())->findByTenant((int)$tenant['id']);
        if (!$settings || !$settings['enabled']) {
            Response::notFound();
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
            'inter'        => "'Inter', system-ui, sans-serif",
            default        => "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
        };
    }
}
