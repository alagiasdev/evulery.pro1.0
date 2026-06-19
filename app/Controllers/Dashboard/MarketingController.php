<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\TenantResolver;
use App\Models\Reservation;
use App\Services\AttributionService;

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
        ], 'dashboard');
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
