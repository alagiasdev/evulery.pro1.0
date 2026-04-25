<?php

namespace App\Services;

use App\Models\HubAction;
use App\Models\HubSettings;
use App\Models\Tenant;

/**
 * Orchestration logic for Vetrina Digitale (Hub):
 * - resolves preset action URLs at render-time (NO DB snapshot)
 * - checks availability of each preset based on tenant config + services
 * - prepares render-ready data for the public hub page
 */
class HubService
{
    private HubSettings $settingsModel;
    private HubAction $actionModel;
    private Tenant $tenantModel;

    public function __construct()
    {
        $this->settingsModel = new HubSettings();
        $this->actionModel   = new HubAction();
        $this->tenantModel   = new Tenant();
    }

    /**
     * Builds the render-ready list of actions for the public hub page,
     * filtering by availability + active flag, computing dynamic URLs.
     *
     * @return array{
     *     hero: array|null,           // booking action as hero (always first if active)
     *     items: array<array>,        // remaining active actions in sort_order
     * }
     */
    public function getRenderableActions(array $tenant, array $settings): array
    {
        $rows = $this->actionModel->findActiveByTenant((int)$tenant['id']);
        $hero = null;
        $items = [];

        foreach ($rows as $row) {
            $resolved = $this->resolveAction($row, $tenant, $settings);
            if ($resolved === null) continue;     // skipped: not available

            if (($resolved['is_hero'] ?? false) === true) {
                $hero = $resolved;
            } else {
                $items[] = $resolved;
            }
        }

        return ['hero' => $hero, 'items' => $items];
    }

    /**
     * Resolves a DB row into a render-ready action with URL, label, sub, icon.
     * Returns null if the action is a preset that requires unmet conditions
     * (so the public page hides it transparently).
     */
    public function resolveAction(array $row, array $tenant, array $settings): ?array
    {
        if ($row['action_type'] === 'custom') {
            // Enterprise custom links: trust what's in DB
            return [
                'type'    => 'custom',
                'label'   => $row['custom_label'],
                'sub'     => null,
                'icon'    => $row['custom_icon'] ?: 'bi-link-45deg',
                'url'     => $row['custom_url'],
                'is_hero' => false,
                'sort'    => (int)$row['sort_order'],
            ];
        }

        // Preset
        $key = $row['preset_key'];
        $def = HubAction::PRESETS[$key] ?? null;
        if (!$def) return null;

        if (!$this->isPresetAvailable($key, $tenant, $settings)) {
            return null;
        }

        return [
            'type'    => 'preset',
            'key'     => $key,
            'label'   => $def['label'],
            'sub'     => $this->resolvePresetSub($key, $def, $tenant, $settings),
            'icon'    => $def['icon'],
            'url'     => $this->resolvePresetUrl($key, $tenant, $settings),
            'is_hero' => $def['is_hero'] ?? false,
            'sort'    => (int)$row['sort_order'],
        ];
    }

    /**
     * Checks whether a preset is available based on its `requires` condition.
     */
    public function isPresetAvailable(string $presetKey, array $tenant, array $settings): bool
    {
        $def = HubAction::PRESETS[$presetKey] ?? null;
        if (!$def) return false;

        $req = $def['requires'] ?? null;
        if ($req === null) return true;

        if (str_starts_with($req, 'tenant.')) {
            $field = substr($req, 7);
            return !empty($tenant[$field]);
        }

        if (str_starts_with($req, 'service.')) {
            $serviceKey = substr($req, 8);
            return $this->tenantModel->canUseService((int)$tenant['id'], $serviceKey);
        }

        if (str_starts_with($req, 'hub.')) {
            $field = substr($req, 4);
            return !empty($settings[$field]);
        }

        return true;
    }

    /**
     * Computes the dynamic URL for a preset based on tenant data.
     */
    public function resolvePresetUrl(string $presetKey, array $tenant, array $settings): string
    {
        $slug = $tenant['slug'] ?? '';

        return match ($presetKey) {
            'booking'    => url($slug),
            'menu'       => url($slug . '/menu'),
            'order'      => url($slug . '/order'),
            'reviews'    => url($slug . '/review'),
            'promotions' => url($slug),  // widget shows active promos in slot picker
            'whatsapp'   => 'https://wa.me/' . $this->cleanPhone($settings['whatsapp_number'] ?? ''),
            'phone'      => 'tel:' . $this->cleanPhone($tenant['phone'] ?? ''),
            'maps'       => 'https://maps.google.com/?q=' . urlencode($tenant['address'] ?? ''),
            default      => '#',
        };
    }

    /**
     * Computes a dynamic sub-text where it makes sense (phone numbers, addresses).
     */
    public function resolvePresetSub(string $presetKey, array $def, array $tenant, array $settings): ?string
    {
        // Static sub from preset definition
        if (!empty($def['sub'])) return $def['sub'];

        // Dynamic sub based on tenant data
        return match ($presetKey) {
            'whatsapp' => $settings['whatsapp_number'] ?? null,
            'phone'    => $tenant['phone'] ?? null,
            'maps'     => $this->shortAddress($tenant['address'] ?? null),
            default    => null,
        };
    }

    /**
     * On first hub activation, ensures settings row exists and presets are seeded.
     */
    public function ensureInitialized(int $tenantId): void
    {
        $this->settingsModel->findOrCreate($tenantId);
        $this->actionModel->seedPresets($tenantId);
    }

    private function cleanPhone(string $phone): string
    {
        // wa.me / tel: prefer digits only with leading country code
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    private function shortAddress(?string $address): ?string
    {
        if (!$address) return null;
        // Show only the first part before the first comma to keep the sub short
        $parts = explode(',', $address, 2);
        return trim($parts[0]);
    }
}
