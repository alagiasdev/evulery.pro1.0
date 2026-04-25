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

        // Load current settings to know what to preserve / delete
        $current = (new HubSettings())->findOrCreate($tenantId);

        // Handle file uploads (logo + cover) before assembling $data
        $logoUrl = $this->handleFileUpload('logo', $tenantId, $current['logo_url'] ?? null, $request);
        $coverUrl = $this->handleFileUpload('cover', $tenantId, $current['cover_url'] ?? null, $request);

        $data = [
            'enabled'         => $request->input('enabled') ? 1 : 0,
            'palette'         => $this->validPalette($request->input('palette', 'evulery_green')),
            'logo_url'        => $logoUrl,
            'cover_url'       => $coverUrl,
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
            $data['custom_colors_enabled'] = $request->input('custom_colors_enabled') ? 1 : 0;
            $data['custom_primary'] = $this->cleanHex($request->input('custom_primary', ''));
            $data['custom_accent']  = $this->cleanHex($request->input('custom_accent', ''));
            $data['custom_dark']    = $this->cleanHex($request->input('custom_dark', ''));
            $data['custom_bg']      = $this->cleanHex($request->input('custom_bg', ''));
            // custom_font: UI temporaneamente nascosta — non scriviamo per non
            // azzerare valori esistenti. Il public hub forza Inter di default.
            $data['hide_branding']  = $request->input('hide_branding') ? 1 : 0;
        }

        // Ensure settings row + preset actions exist (idempotent safety net)
        (new HubService())->ensureInitialized($tenantId);
        (new HubSettings())->update($tenantId, $data);

        // Toggle individual preset actions on/off.
        // Skip locked presets (e.g. booking) — their checkbox is `disabled`
        // and never submits, so toggling them off here would silently
        // deactivate them. They must always stay active.
        $activeIds = (array)$request->input('action_active', []);
        $allActions = (new HubAction())->findAllByTenant($tenantId);
        $hubAction = new HubAction();
        foreach ($allActions as $action) {
            if ($action['action_type'] === 'preset') {
                $def = HubAction::PRESETS[$action['preset_key']] ?? null;
                if ($def && !empty($def['locked_position'])) {
                    continue;
                }
            }
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
        $sub   = trim((string)$request->input('sub', ''));

        if ($label === '' || $url === '') {
            flash('danger', 'Etichetta e URL del link sono obbligatori.');
            Response::redirect(url('dashboard/settings/hub'));
        }

        if (!$this->isAcceptableUrl($url)) {
            flash('danger', 'URL non valido. Usa http://, https://, mailto: o tel:.');
            Response::redirect(url('dashboard/settings/hub'));
        }

        (new HubAction())->createCustom(
            $tenantId,
            mb_substr($label, 0, 100),
            $url,
            $icon,
            $sub !== '' ? mb_substr($sub, 0, 100) : null
        );
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
        return (new Tenant())->isEnterprise($tenantId);
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

    /**
     * Handle logo/cover image upload. Returns the new URL to save, or
     * the existing one if no new upload, or null if user clicked "remove".
     */
    private function handleFileUpload(string $field, int $tenantId, ?string $currentUrl, Request $request): ?string
    {
        // "remove" checkbox: user wants to clear the image
        if ($request->input($field . '_remove')) {
            $this->deleteOldFile($currentUrl);
            return null;
        }

        $file = $_FILES[$field] ?? null;
        $hasUpload = $file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE;

        // No upload, no remove → keep existing URL
        if (!$hasUpload) {
            return $currentUrl;
        }

        // Upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'File troppo grande.'
                : 'Errore durante il caricamento.';
            flash('danger', $msg . ' (' . $field . ')');
            return $currentUrl;
        }

        // Size check (2MB)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            flash('danger', ucfirst($field) . ' troppo grande (max 2 MB).');
            return $currentUrl;
        }

        // MIME validation
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowed, true)) {
            flash('danger', ucfirst($field) . ': formato non supportato. Usa JPG, PNG o WebP.');
            return $currentUrl;
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };

        // Move file
        $uploadDir = BASE_PATH . '/public/uploads/hubs/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $filename = $field . '_' . $tenantId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            flash('danger', ucfirst($field) . ': errore salvataggio file.');
            return $currentUrl;
        }

        // Delete old file (success path: replace)
        $this->deleteOldFile($currentUrl);

        return url('uploads/hubs/' . $filename);
    }

    private function deleteOldFile(?string $url): void
    {
        if (!$url) return;
        // Only delete if it's a local upload URL
        $base = url('uploads/hubs/');
        if (!str_starts_with($url, $base)) return;
        $filename = basename(parse_url($url, PHP_URL_PATH) ?: '');
        if (!$filename) return;
        $path = BASE_PATH . '/public/uploads/hubs/' . $filename;
        if (is_file($path)) @unlink($path);
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
