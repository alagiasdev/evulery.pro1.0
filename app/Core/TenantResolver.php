<?php

namespace App\Core;

class TenantResolver
{
    private static ?array $currentTenant = null;
    private static string $mode = 'none';

    public static function resolve(string $host, string $uri): ?array
    {
        $db = Database::getInstance();
        $appHost = parse_url(env('APP_URL', ''), PHP_URL_HOST) ?: 'localhost';

        // Skip tenant resolution for system routes
        $systemPrefixes = ['/auth', '/admin', '/api/v1/admin', '/api/v1/stripe'];
        foreach ($systemPrefixes as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                return null;
            }
        }

        // Priority 1: Custom domain match
        if ($host !== $appHost && $host !== 'localhost') {
            $stmt = $db->prepare(
                'SELECT * FROM tenants WHERE custom_domain = :domain AND is_active = 1 LIMIT 1'
            );
            $stmt->execute(['domain' => $host]);
            $tenant = $stmt->fetch();

            if ($tenant) {
                self::$currentTenant = $tenant;
                self::$mode = 'custom_domain';
                return $tenant;
            }
        }

        // Priority 2: Slug from URI (first segment after base)
        $segments = explode('/', trim($uri, '/'));
        $slug = $segments[0] ?? '';

        if ($slug && !in_array($slug, ['dashboard', 'auth', 'admin', 'api'])) {
            $stmt = $db->prepare(
                'SELECT * FROM tenants WHERE slug = :slug AND is_active = 1 LIMIT 1'
            );
            $stmt->execute(['slug' => $slug]);
            $tenant = $stmt->fetch();

            if ($tenant) {
                self::$currentTenant = $tenant;
                self::$mode = isset($_GET['embed']) && $_GET['embed'] === '1' ? 'embed' : 'hosted';
                return $tenant;
            }
        }

        return null;
    }

    public static function current(): ?array
    {
        return self::$currentTenant;
    }

    public static function setCurrent(?array $tenant): void
    {
        self::$currentTenant = $tenant;
    }

    public static function mode(): string
    {
        return self::$mode;
    }

    public static function id(): ?int
    {
        return self::$currentTenant['id'] ?? null;
    }

    /**
     * Reload current tenant data from DB (e.g. after credits change).
     */
    public static function refreshCurrent(): void
    {
        if (!self::$currentTenant) return;

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => self::$currentTenant['id']]);
        $tenant = $stmt->fetch();
        if ($tenant) {
            self::$currentTenant = $tenant;
        }
    }
}
