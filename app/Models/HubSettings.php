<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Vetrina Digitale (Hub) — settings per tenant.
 * Una riga per tenant. Auto-creata vuota al primo accesso al pannello.
 */
class HubSettings
{
    private PDO $db;

    /** Palette preset disponibili. Key = slug usato in DB. */
    public const PALETTES = [
        'evulery_green'  => ['primary' => '#00844A', 'accent' => '#E8F5E9', 'dark' => '#006837', 'name' => 'Evulery Green'],
        'terracotta'     => ['primary' => '#D84315', 'accent' => '#FFF3E0', 'dark' => '#BF360C', 'name' => 'Terracotta'],
        'nero_elegante'  => ['primary' => '#212121', 'accent' => '#FFD700', 'dark' => '#000000', 'name' => 'Nero Elegante'],
        'oro_marrone'    => ['primary' => '#8D6E63', 'accent' => '#EFEBE9', 'dark' => '#5D4037', 'name' => 'Oro & Marrone'],
        'verde_bosco'    => ['primary' => '#2E7D32', 'accent' => '#F1F8E9', 'dark' => '#1B5E20', 'name' => 'Verde Bosco'],
        'grigio_minimal' => ['primary' => '#37474F', 'accent' => '#ECEFF1', 'dark' => '#263238', 'name' => 'Grigio Minimal'],
    ];

    /** Font choices (Enterprise only). */
    public const FONTS = [
        'system'       => 'System moderno (default)',
        'serif'        => 'Serif elegante (Playfair Display)',
        'merriweather' => 'Serif classico (Merriweather)',
        'caveat'       => 'Artigianale (Caveat — scritta a mano)',
        'inter'        => 'Minimalista (Inter)',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByTenant(int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tenant_hub_settings WHERE tenant_id = :tid LIMIT 1'
        );
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Returns existing settings or creates an empty default row and returns it.
     * Idempotent: safe to call on every settings page view.
     */
    public function findOrCreate(int $tenantId): array
    {
        $existing = $this->findByTenant($tenantId);
        if ($existing) return $existing;

        $stmt = $this->db->prepare(
            'INSERT INTO tenant_hub_settings (tenant_id) VALUES (:tid)'
        );
        $stmt->execute(['tid' => $tenantId]);
        return $this->findByTenant($tenantId);
    }

    /**
     * Updates a tenant's hub settings. Allowed fields are whitelisted.
     */
    public function update(int $tenantId, array $data): void
    {
        $allowed = [
            'enabled', 'palette', 'logo_url', 'cover_url', 'subtitle',
            'custom_colors_enabled', 'custom_primary', 'custom_accent', 'custom_dark',
            'custom_bg', 'custom_font', 'hide_branding',
            'instagram_url', 'facebook_url', 'tiktok_url',
            'twitter_url', 'youtube_url', 'whatsapp_number',
        ];
        $sets = [];
        $params = ['tid' => $tenantId];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "`{$field}` = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        if (empty($sets)) return;

        $sql = 'UPDATE tenant_hub_settings SET ' . implode(', ', $sets) . ' WHERE tenant_id = :tid';
        $this->db->prepare($sql)->execute($params);
    }

    /**
     * Resolves the active palette colors for rendering.
     * Returns ['primary' => '#xxx', 'accent' => '#xxx', 'dark' => '#xxx', 'bg' => '#xxx'].
     *
     * Modalità tutto-o-niente:
     *   custom_colors_enabled = 0 → usa preset, custom_* ignorati
     *   custom_colors_enabled = 1 → usa custom_*, preset ignorato (con fallback a primario se dark vuoto)
     */
    public function resolveColors(array $settings): array
    {
        $useCustom = !empty($settings['custom_colors_enabled']);

        if ($useCustom) {
            $primary = $settings['custom_primary'] ?: '#00844A';
            return [
                'primary' => $primary,
                'accent'  => $settings['custom_accent'] ?: '#E8F5E9',
                // Se dark non impostato, fallback al primary (gradiente piatto invece di mismatch)
                'dark'    => $settings['custom_dark'] ?: $primary,
                'bg'      => $settings['custom_bg'] ?: '#ffffff',
            ];
        }

        // Modalità preset: tutto deriva dalla palette scelta
        $paletteKey = $settings['palette'] ?? 'evulery_green';
        $base = self::PALETTES[$paletteKey] ?? self::PALETTES['evulery_green'];

        return [
            'primary' => $base['primary'],
            'accent'  => $base['accent'],
            'dark'    => $base['dark'],
            'bg'      => '#ffffff',
        ];
    }
}
