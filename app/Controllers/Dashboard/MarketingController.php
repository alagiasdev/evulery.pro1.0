<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\MarketingLink;
use App\Models\Reservation;
use App\Services\AttributionService;
use App\Services\HubAnalyticsService;

/**
 * Sezione Marketing (gated 'marketing', Pro+Ent):
 *  - Provenienza: report prenotazioni/coperti per canale + campagna
 *  - Genera link: costruttore di link tracciati (UTM) verso l'Hub/azioni
 *
 * La cattura dei dati e' libera per tutti (vedi AttributionService); qui c'e'
 * il valore consultabile, riservato ai piani superiori.
 */
class MarketingController
{
    /** Periodi preimpostati (giorni). */
    private const RANGES = [7 => 'Ultimi 7 giorni', 30 => 'Ultimi 30 giorni', 90 => 'Ultimi 90 giorni'];

    public function index(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUse = tenant_can('marketing');

        [$from, $to, $rangeKey] = $this->resolveRange($request);

        $channels = [];
        $totals = ['n' => 0, 'covers' => 0, 'tracked' => 0, 'tracked_pct' => 0, 'channels' => 0, 'via_hub' => 0];

        if ($canUse) {
            $rows = (new Reservation())->attributionReport((int)$tenant['id'], $from, $to);
            [$channels, $totals] = $this->aggregate($rows);
        }

        view('dashboard/marketing/index', [
            'title'      => 'Marketing',
            'activeMenu' => 'marketing',
            'tenant'     => $tenant,
            'canUse'     => $canUse,
            'channels'   => $channels,
            'totals'     => $totals,
            'from'       => $from,
            'to'         => $to,
            'rangeKey'   => $rangeKey,
            'ranges'     => self::RANGES,
            'tab'        => 'provenienza',
        ], 'dashboard');
    }

    public function links(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUse = tenant_can('marketing');
        $slug = (string)($tenant['slug'] ?? '');

        $saved = $canUse ? (new MarketingLink())->findByTenantWithStats((int)$tenant['id']) : [];
        $destLabels = ['hub' => 'Hub', 'booking' => 'Prenota', 'menu' => 'Menù', 'order' => 'Ordina'];

        // Una destinazione e' selezionabile solo se il relativo servizio/pagina
        // e' attivo, altrimenti il link porterebbe a una pagina "non disponibile".
        $active = $this->destActive($tenant);

        view('dashboard/marketing/links', [
            'title'       => 'Marketing',
            'activeMenu'  => 'marketing',
            'tenant'      => $tenant,
            'canUse'      => $canUse,
            'tab'         => 'links',
            // base pubblica del tenant (dominio dash + slug)
            'hubUrl'      => url($slug . '/hub'),
            'bookingUrl'  => url($slug),
            'menuUrl'     => url($slug . '/menu'),
            'orderUrl'    => url($slug . '/order'),
            'saved'       => $saved,
            'destLabels'  => $destLabels,
            'hubActive'   => $active['hub'],
            'menuActive'  => $active['menu'],
            'orderActive' => $active['order'],
            'hubConfigUrl' => url('dashboard/settings/hub'),
        ], 'dashboard');
    }

    public function vetrina(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUse = tenant_can('marketing');
        [$from, $to, $rangeKey] = $this->resolveRange($request);

        $analytics = ['kpis' => ['visits' => 0, 'clicks' => 0, 'cpv' => 0, 'bookings' => 0, 'channels' => 0], 'tree' => [], 'scopes' => [], 'buttons' => []];
        if ($canUse) {
            try {
                $analytics = (new HubAnalyticsService())->build($tenant, $from, $to);
            } catch (\Throwable $e) {
                // es. migration 072 non ancora applicata: mostra stato vuoto invece di crashare
                app_log('Statistiche Vetrina non disponibili: ' . $e->getMessage(), 'warning');
            }
        }

        view('dashboard/marketing/vetrina', [
            'title'        => 'Marketing',
            'activeMenu'   => 'marketing',
            'tenant'       => $tenant,
            'canUse'       => $canUse,
            'tab'          => 'vetrina',
            'analytics'    => $analytics,
            'from'         => $from,
            'to'           => $to,
            'rangeKey'     => $rangeKey,
            'ranges'       => self::RANGES,
            'hubConfigUrl' => url('dashboard/settings/hub'),
        ], 'dashboard');
    }

    public function saveLink(Request $request): void
    {
        $tenant = TenantResolver::current();
        if (!tenant_can('marketing')) {
            Response::redirect(url('dashboard/marketing/links'));
            return;
        }

        $dest = (string)$request->input('destination', 'hub');
        if (!in_array($dest, ['hub', 'booking', 'menu', 'order'], true)) $dest = 'hub';

        // Guardia: non salvare link verso una destinazione non attiva (porterebbe
        // a una pagina "non disponibile"). Difesa server-side oltre alla UI.
        $active = $this->destActive($tenant);
        if (!($active[$dest] ?? false)) {
            $labels = ['hub' => 'la Vetrina Digitale', 'menu' => 'il Menù', 'order' => 'gli Ordini online'];
            flash('warning', 'Non puoi creare un link verso ' . ($labels[$dest] ?? 'questa destinazione') . ': il servizio non è attivo.');
            Response::redirect(url('dashboard/marketing/links'));
            return;
        }

        $source   = $this->slug((string)$request->input('utm_source', ''), 100);
        $medium   = $this->slug((string)$request->input('utm_medium', ''), 60) ?: 'referral';
        $campaign = $this->slug((string)$request->input('utm_campaign', ''), 120) ?: null;

        if ($source === '') {
            flash('danger', 'Seleziona un canale prima di salvare la campagna.');
            Response::redirect(url('dashboard/marketing/links'));
            return;
        }

        $model = new MarketingLink();
        if ($model->existsDuplicate((int)$tenant['id'], $dest, $source, $medium, $campaign)) {
            flash('warning', 'Hai già salvato questa campagna (stesso canale, destinazione e nome). La trovi nella lista qui sotto.');
            Response::redirect(url('dashboard/marketing/links'));
            return;
        }

        $slug    = (string)($tenant['slug'] ?? '');
        $channel = AttributionService::deriveChannel($source, $medium, null);
        $url     = $this->buildUrl($slug, $dest, $source, $medium, $campaign);

        $model->create([
            'tenant_id'    => (int)$tenant['id'],
            'destination'  => $dest,
            'utm_source'   => $source,
            'utm_medium'   => $medium,
            'utm_campaign' => $campaign,
            'channel'      => $channel,
            'url'          => $url,
        ]);

        flash('success', 'Campagna salvata. La ritrovi qui sotto pronta da copiare.');
        Response::redirect(url('dashboard/marketing/links'));
    }

    public function deleteLink(Request $request): void
    {
        $tenant = TenantResolver::current();
        $id = (int)$request->param('id');
        if (tenant_can('marketing')) {
            (new MarketingLink())->delete($id, (int)$tenant['id']);
            flash('success', 'Campagna eliminata.');
        }
        Response::redirect(url('dashboard/marketing/links'));
    }

    /**
     * Quali destinazioni del generatore sono utilizzabili (servizio incluso +
     * funzione abilitata). Booking sempre attivo (pagina pubblica base).
     * Stessa logica delle pagine pubbliche (Hub/Menu/Order controller).
     */
    private function destActive(array $tenant): array
    {
        $hubSettings = (new \App\Models\HubSettings())->findByTenant((int)$tenant['id']);
        return [
            'booking' => true,
            'hub'     => tenant_can('vetrina_digitale') && $hubSettings && !empty($hubSettings['enabled']),
            'menu'    => tenant_can('digital_menu') && !empty($tenant['menu_enabled']),
            'order'   => tenant_can('online_ordering') && !empty($tenant['ordering_enabled']),
        ];
    }

    /** Path pubblico per destinazione. */
    private function destPath(string $destination, string $slug): string
    {
        return match ($destination) {
            'booking' => $slug,
            'menu'    => $slug . '/menu',
            'order'   => $slug . '/order',
            default   => $slug . '/hub',
        };
    }

    /** Costruisce l'URL tracciato (stessa logica del generatore client). */
    private function buildUrl(string $slug, string $destination, string $source, ?string $medium, ?string $campaign): string
    {
        $u = url($this->destPath($destination, $slug))
           . '?utm_source=' . urlencode($source)
           . '&utm_medium=' . urlencode($medium ?: 'referral');
        if ($campaign) {
            $u .= '&utm_campaign=' . urlencode($campaign);
        }
        return $u;
    }

    /** Slugify identico al generatore JS: lowercase, spazi→-, solo [a-z0-9._-]. */
    private function slug(string $v, int $max): string
    {
        $v = strtolower(trim($v));
        $v = preg_replace('/\s+/', '-', $v);
        $v = preg_replace('/[^a-z0-9._-]/', '', $v);
        return mb_substr($v, 0, $max);
    }

    /**
     * Aggrega le righe (channel,campaign) in canali con drill-down campagne
     * + totali e quota "tracciata" (canale != direct).
     */
    private function aggregate(array $rows): array
    {
        $byChannel = [];
        $totN = 0; $totCovers = 0; $tracked = 0; $viaHub = 0;

        foreach ($rows as $r) {
            $ch = $r['channel'];
            $n = (int)$r['n'];
            $covers = (int)$r['covers'];
            $totN += $n; $totCovers += $covers;
            $viaHub += (int)$r['via_hub'];
            if ($ch !== 'direct') $tracked += $n;

            if (!isset($byChannel[$ch])) {
                $byChannel[$ch] = [
                    'key'       => $ch,
                    'label'     => AttributionService::label($ch),
                    'color'     => AttributionService::color($ch),
                    'n'         => 0,
                    'covers'    => 0,
                    'campaigns' => [],
                ];
            }
            $byChannel[$ch]['n'] += $n;
            $byChannel[$ch]['covers'] += $covers;
            if ($r['campaign'] !== '') {
                $byChannel[$ch]['campaigns'][] = [
                    'name'   => $r['campaign'],
                    'n'      => $n,
                    'covers' => $covers,
                ];
            }
        }

        // ordina canali per coperti desc
        usort($byChannel, fn($a, $b) => $b['covers'] <=> $a['covers']);

        $totals = [
            'n'           => $totN,
            'covers'      => $totCovers,
            'tracked'     => $tracked,
            'tracked_pct' => $totN > 0 ? (int)round($tracked / $totN * 100) : 0,
            'channels'    => count(array_filter($byChannel, fn($c) => $c['key'] !== 'direct')),
            'via_hub'     => $viaHub,
        ];

        return [array_values($byChannel), $totals];
    }

    /** Risolve il periodo dai parametri (preset o from/to custom). */
    private function resolveRange(Request $request): array
    {
        $from = (string)$request->query('from', '');
        $to   = (string)$request->query('to', '');
        $valid = fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        if ($valid($from) && $valid($to) && $to >= $from) {
            return [$from, $to, 'custom'];
        }

        $days = (int)$request->query('days', 30);
        if (!isset(self::RANGES[$days])) $days = 30;
        return [date('Y-m-d', strtotime("-" . ($days - 1) . " days")), date('Y-m-d'), (string)$days];
    }
}
