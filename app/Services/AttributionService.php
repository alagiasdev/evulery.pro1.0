<?php

namespace App\Services;

/**
 * Attribuzione marketing delle prenotazioni: normalizza gli UTM e deriva un
 * "canale" leggibile (last-click) da utm_source o, in mancanza, dal referrer.
 * Sorgente unica della logica, riusata in cattura (store) e report.
 */
class AttributionService
{
    /**
     * Mappa sorgenti note → canale canonico. I valori non noti restano come
     * sono (sanitizzati): cosi' il "Generico/Altro" (es. tripadvisor) funziona.
     */
    private const SOURCE_MAP = [
        'meta' => 'meta', 'facebook' => 'meta', 'fb' => 'meta', 'facebook-ads' => 'meta', 'metaads' => 'meta',
        'instagram' => 'instagram', 'ig' => 'instagram', 'insta' => 'instagram',
        'google' => 'google', 'google-ads' => 'google', 'googleads' => 'google', 'adwords' => 'google',
        'tiktok' => 'tiktok', 'tt' => 'tiktok',
        'hub' => 'hub', 'vetrina' => 'hub',
        'newsletter' => 'newsletter', 'email' => 'newsletter', 'mail' => 'newsletter',
        'whatsapp' => 'whatsapp', 'wa' => 'whatsapp',
        'flyer' => 'flyer', 'qr' => 'flyer', 'volantino' => 'flyer',
        'gbp' => 'gbp', 'google-business' => 'gbp', 'gmb' => 'gbp',
    ];

    /** Mappa host referrer → canale (traffico organico/non taggato). */
    private const REFERRER_MAP = [
        'instagram.com' => 'instagram', 'l.instagram.com' => 'instagram',
        'facebook.com' => 'facebook', 'l.facebook.com' => 'facebook', 'lm.facebook.com' => 'facebook', 'm.facebook.com' => 'facebook',
        'tiktok.com' => 'tiktok',
        'google.com' => 'google_organic', 'google.it' => 'google_organic',
        'bing.com' => 'bing_organic',
        't.co' => 'twitter', 'twitter.com' => 'twitter', 'x.com' => 'twitter',
    ];

    /** Etichette + colore per il report (single source of truth lato UI). */
    public static function channelMeta(): array
    {
        return [
            'meta'           => ['label' => 'Meta / Facebook', 'color' => '#0866ff'],
            'instagram'      => ['label' => 'Instagram',       'color' => '#e1306c'],
            'google'         => ['label' => 'Google Ads',      'color' => '#ea4335'],
            'google_organic' => ['label' => 'Google organico', 'color' => '#16a34a'],
            'bing_organic'   => ['label' => 'Bing organico',   'color' => '#16a34a'],
            'tiktok'         => ['label' => 'TikTok',          'color' => '#111111'],
            'hub'            => ['label' => 'Hub / Vetrina',   'color' => '#8b5cf6'],
            'newsletter'     => ['label' => 'Newsletter',      'color' => '#0d9488'],
            'whatsapp'       => ['label' => 'WhatsApp',        'color' => '#25d366'],
            'flyer'          => ['label' => 'Volantino QR',    'color' => '#f59e0b'],
            'gbp'            => ['label' => 'Google Business',  'color' => '#4285f4'],
            'facebook'       => ['label' => 'Facebook',        'color' => '#0866ff'],
            'twitter'        => ['label' => 'X / Twitter',     'color' => '#1d9bf0'],
            'direct'         => ['label' => 'Diretto / sconosciuto', 'color' => '#6b7280'],
        ];
    }

    /** Etichetta leggibile per un canale (fallback: il canale stesso capitalizzato). */
    public static function label(string $channel): string
    {
        $meta = self::channelMeta();
        if (isset($meta[$channel])) {
            return $meta[$channel]['label'];
        }
        return ucfirst(str_replace(['-', '_', '.'], ' ', $channel));
    }

    public static function color(string $channel): string
    {
        $meta = self::channelMeta();
        return $meta[$channel]['color'] ?? '#6b7280';
    }

    /** Pulisce un valore UTM (trim, lowercase, lunghezza max). */
    public static function sanitize(?string $v, int $max = 100): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        // niente caratteri di controllo / spazi anomali
        $v = preg_replace('/[\x00-\x1F\x7F]+/u', '', $v);
        return mb_substr($v, 0, $max);
    }

    /** Versione "slug" per usare la sorgente come chiave canale (a-z0-9-_.). */
    private static function slugifySource(string $v): string
    {
        $v = strtolower(trim($v));
        $v = preg_replace('/[^a-z0-9._-]+/', '-', $v);
        $v = trim($v, '-');
        return mb_substr($v, 0, 40);
    }

    /**
     * Deriva il canale (last-click): utm_source noto → canonico; utm_source
     * sconosciuto → la sorgente stessa (generico); altrimenti referrer; infine
     * 'direct'.
     */
    public static function deriveChannel(?string $utmSource, ?string $utmMedium, ?string $referrer): string
    {
        $src = self::sanitize($utmSource, 100);
        if ($src !== null) {
            $key = self::slugifySource($src);
            return self::SOURCE_MAP[$key] ?? $key;
        }

        $host = self::referrerHost($referrer);
        if ($host !== null) {
            if (isset(self::REFERRER_MAP[$host])) {
                return self::REFERRER_MAP[$host];
            }
            // referrer esterno non mappato → usa l'host come canale (es. tripadvisor.com)
            return mb_substr($host, 0, 40);
        }

        return 'direct';
    }

    /** Host del referrer in minuscolo, senza 'www.'. Null se assente/non valido. */
    public static function referrerHost(?string $referrer): ?string
    {
        if (!$referrer) {
            return null;
        }
        $host = parse_url($referrer, PHP_URL_HOST);
        if (!$host) {
            return null;
        }
        $host = strtolower($host);
        return preg_replace('/^www\./', '', $host);
    }

    /** True se il referrer punta alla pagina Hub del tenant (/{slug}/hub). */
    public static function isHubReferrer(?string $referrer, string $slug): bool
    {
        if (!$referrer || $slug === '') {
            return false;
        }
        $path = (string)parse_url($referrer, PHP_URL_PATH);
        return str_contains($path, '/' . $slug . '/hub');
    }
}
