<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\HubAction;
use App\Models\HubSettings;
use App\Models\Tenant;
use App\Services\HubService;

/**
 * Vetrina Digitale (Hub) — settings dashboard side.
 * Public side handled by App\Controllers\Public\HubPublicController.
 */
class HubController
{
    public function index(Request $request): void
    {
        $tenant = TenantResolver::current();
        $tenantId = (int)$tenant['id'];

        $canUseHub = (new Tenant())->canUseService($tenantId, 'vetrina_digitale');

        // Auto-init settings + presets on first visit
        if ($canUseHub) {
            (new HubService())->ensureInitialized($tenantId);
        }

        $settings = (new HubSettings())->findByTenant($tenantId) ?? [];
        $actions  = (new HubAction())->findAllByTenant($tenantId);
        $isEnterprise = $this->isEnterprise($tenantId);

        view('dashboard/settings/hub', [
            'title'        => 'Vetrina Digitale',
            'activeMenu'   => 'settings-hub',
            'tenant'       => $tenant,
            'canUseHub'    => $canUseHub,
            'isEnterprise' => $isEnterprise,
            'settings'     => $settings,
            'actions'      => $actions,
            'palettes'     => HubSettings::PALETTES,
            'fonts'        => HubSettings::FONTS,
            'presets'      => HubAction::PRESETS,
            'pageStyles'   => ['css/hub.css'],
            'pageScripts'  => ['js/hub-settings.js'],
        ], 'dashboard');
    }

    public function update(Request $request): void
    {
        if (gate_service('vetrina_digitale', url('dashboard/settings/hub'))) return;

        $tenantId = Auth::tenantId();
        $isEnterprise = $this->isEnterprise($tenantId);

        $data = [
            'enabled'         => $request->input('enabled') ? 1 : 0,
            'palette'         => $this->validPalette($request->input('palette', 'evulery_green')),
            'logo_url'        => $this->cleanUrl($request->input('logo_url', '')),
            'cover_url'       => $this->cleanUrl($request->input('cover_url', '')),
            'subtitle'        => trim((string)$request->input('subtitle', '')) ?: null,
            'instagram_url'   => $this->cleanUrl($request->input('instagram_url', '')),
            'facebook_url'    => $this->cleanUrl($request->input('facebook_url', '')),
            'tiktok_url'      => $this->cleanUrl($request->input('tiktok_url', '')),
            'twitter_url'     => $this->cleanUrl($request->input('twitter_url', '')),
            'youtube_url'     => $this->cleanUrl($request->input('youtube_url', '')),
            'whatsapp_number' => trim((string)$request->input('whatsapp_number', '')) ?: null,
        ];

        // Enterprise-only fields — silently ignored on lower plans
        if ($isEnterprise) {
            $data['custom_primary'] = $this->cleanHex($request->input('custom_primary', ''));
            $data['custom_accent']  = $this->cleanHex($request->input('custom_accent', ''));
            $data['custom_bg']      = $this->cleanHex($request->input('custom_bg', ''));
            $data['custom_font']    = $this->validFont($request->input('custom_font', ''));
            $data['hide_branding']  = $request->input('hide_branding') ? 1 : 0;
        }

        // Ensure settings row exists
        (new HubSettings())->findOrCreate($tenantId);
        (new HubSettings())->update($tenantId, $data);

        // Toggle individual preset actions on/off
        $activeIds = (array)$request->input('action_active', []);
        $allActions = (new HubAction())->findAllByTenant($tenantId);
        $hubAction = new HubAction();
        foreach ($allActions as $action) {
            $shouldBeActive = in_array((string)$action['id'], $activeIds, true);
            $hubAction->setActive((int)$action['id'], $tenantId, $shouldBeActive);
        }

        flash('success', 'Vetrina aggiornata.');
        Response::redirect(url('dashboard/settings/hub'));
    }

    public function reorder(Request $request): void
    {
        if (gate_service('vetrina_digitale', url('dashboard/settings/hub'))) return;

        $tenantId = Auth::tenantId();
        $orderedIds = (array)$request->input('order', []);

        if (empty($orderedIds)) {
            Response::json(['success' => false, 'error' => 'Empty order list'], 400);
            return;
        }

        try {
            (new HubAction())->reorder($tenantId, $orderedIds);
            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'error' => 'Reorder failed'], 500);
        }
    }

    public function addCustomLink(Request $request): void
    {
        if (gate_service('vetrina_digitale', url('dashboard/settings/hub'))) return;

        $tenantId = Auth::tenantId();
        if (!$this->isEnterprise($tenantId)) {
            flash('danger', 'I link personalizzati sono disponibili solo nel piano Enterprise.');
            Response::redirect(url('dashboard/settings/hub'));
        }

        $label = trim((string)$request->input('label', ''));
        $url   = trim((string)$request->input('url', ''));
        $icon  = trim((string)$request->input('icon', 'bi-link-45deg'));

        if ($label === '' || $url === '') {
            flash('danger', 'Etichetta e URL del link sono obbligatori.');
            Response::redirect(url('dashboard/settings/hub'));
        }

        if (!$this->isAcceptableUrl($url)) {
            flash('danger', 'URL non valido. Usa http://, https://, mailto: o tel:.');
            Response::redirect(url('dashboard/settings/hub'));
        }

        (new HubAction())->createCustom($tenantId, mb_substr($label, 0, 100), $url, $icon);
        flash('success', 'Link aggiunto.');
        Response::redirect(url('dashboard/settings/hub'));
    }

    public function deleteCustomLink(Request $request): void
    {
        if (gate_service('vetrina_digitale', url('dashboard/settings/hub'))) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');

        (new HubAction())->deleteCustom($id, $tenantId);
        flash('success', 'Link eliminato.');
        Response::redirect(url('dashboard/settings/hub'));
    }

    // ============== Helpers ==============

    private function isEnterprise(int $tenantId): bool
    {
        $stmt = \App\Core\Database::getInstance()->prepare(
            'SELECT p.name FROM tenants t JOIN plans p ON p.id = t.plan_id WHERE t.id = :tid LIMIT 1'
        );
        $stmt->execute(['tid' => $tenantId]);
        $name = (string)$stmt->fetchColumn();
        return strcasecmp($name, 'Enterprise') === 0;
    }

    private function validPalette(string $key): string
    {
        return array_key_exists($key, HubSettings::PALETTES) ? $key : 'evulery_green';
    }

    private function validFont(string $key): ?string
    {
        return array_key_exists($key, HubSettings::FONTS) ? $key : null;
    }

    private function cleanUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') return null;
        // Allow only http(s); reject anything else for image/social URLs
        if (!preg_match('#^https?://#i', $url)) return null;
        return mb_substr($url, 0, 500);
    }

    private function cleanHex(string $hex): ?string
    {
        $hex = trim($hex);
        if ($hex === '') return null;
        return preg_match('/^#[0-9a-f]{6}$/i', $hex) ? strtolower($hex) : null;
    }

    /**
     * Custom action URLs accept a wider range of schemes (http, mailto, tel).
     */
    private function isAcceptableUrl(string $url): bool
    {
        return (bool)preg_match('#^(https?://|mailto:|tel:)#i', $url);
    }
}
