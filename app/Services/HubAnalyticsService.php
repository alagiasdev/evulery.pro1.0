<?php

namespace App\Services;

use App\Models\HubClick;
use App\Models\HubSettings;
use App\Models\HubVisit;
use App\Models\Reservation;

/**
 * Aggrega le statistiche della Vetrina (visite, click per pulsante,
 * prenotazioni passate dall'Hub) in una struttura master-detail:
 * canale → campagna, con funnel e click per pulsante per ogni scope.
 */
class HubAnalyticsService
{
    public function build(array $tenant, string $from, string $to): array
    {
        $tenantId = (int)$tenant['id'];

        $visits  = (new HubVisit())->aggregate($tenantId, $from, $to);
        $clicks  = (new HubClick())->aggregate($tenantId, $from, $to);
        $btns    = (new HubClick())->aggregateByButton($tenantId, $from, $to);
        $books   = (new Reservation())->hubBookings($tenantId, $from, $to);

        // mappe [channel][campaign] = n
        $vMap = $this->toMap($visits);
        $cMap = $this->toMap($clicks);
        $bMap = $this->toMap($books);
        // [channel][campaign][ref] = n
        $btnMap = [];
        foreach ($btns as $r) {
            $btnMap[$r['channel']][$r['campaign']][$r['ref']] = (int)$r['n'];
        }

        // lista pulsanti: azioni configurate (hero+items) + eventuali ref extra
        // visti nei click (social, custom rimossi) per far quadrare i totali.
        $buttons = $this->buildButtonList($tenant, $btnMap);

        // insieme canali/campagne presenti
        $channels = [];
        foreach ([$vMap, $cMap, $bMap] as $m) {
            foreach ($m as $ch => $camps) {
                $channels[$ch] = $channels[$ch] ?? [];
                foreach ($camps as $camp => $_) {
                    if ($camp !== '') $channels[$ch][$camp] = true;
                }
            }
        }

        $scopes = [];
        // scope globale
        $scopes['all'] = $this->scope('Tutto il traffico', 'tutte le provenienze', '#6b7280',
            $this->sumAll($vMap), $this->sumAll($cMap), $this->sumAll($bMap), $this->sumBtnAll($btnMap));

        $tree = [['id' => 'all', 'label' => 'Tutto il traffico', 'color' => '#6b7280',
                  'visits' => $scopes['all']['visits'], 'bookings' => $scopes['all']['book'], 'children' => []]];

        // ordina canali per visite desc
        $chOrder = array_keys($channels);
        usort($chOrder, fn($a, $b) => $this->sumCh($vMap, $b) <=> $this->sumCh($vMap, $a));

        foreach ($chOrder as $ch) {
            $label = AttributionService::label($ch);
            $color = AttributionService::color($ch);
            $scopes[$ch] = $this->scope($label, 'aggregato canale', $color,
                $this->sumCh($vMap, $ch), $this->sumCh($cMap, $ch), $this->sumCh($bMap, $ch), $this->sumBtnCh($btnMap, $ch));

            $children = [];
            $camps = array_keys($channels[$ch]);
            usort($camps, fn($a, $b) => (int)($vMap[$ch][$b] ?? 0) <=> (int)($vMap[$ch][$a] ?? 0));
            foreach ($camps as $camp) {
                $sid = $ch . ':' . $camp;
                $scopes[$sid] = $this->scope($label . ' · ' . $camp, 'campagna', $color,
                    (int)($vMap[$ch][$camp] ?? 0), (int)($cMap[$ch][$camp] ?? 0), (int)($bMap[$ch][$camp] ?? 0),
                    $btnMap[$ch][$camp] ?? []);
                $children[] = ['id' => $sid, 'label' => $camp,
                               'visits' => $scopes[$sid]['visits'], 'bookings' => $scopes[$sid]['book']];
            }

            $tree[] = ['id' => $ch, 'label' => $label, 'color' => $color,
                       'visits' => $scopes[$ch]['visits'], 'bookings' => $scopes[$ch]['book'], 'children' => $children];
        }

        $kpis = [
            'visits'   => $scopes['all']['visits'],
            'clicks'   => $scopes['all']['clicks'],
            'cpv'      => $scopes['all']['cpv'],
            'bookings' => $scopes['all']['book'],
            'channels' => count($chOrder),
        ];

        return ['kpis' => $kpis, 'tree' => $tree, 'scopes' => $scopes, 'buttons' => $buttons];
    }

    private function scope(string $title, string $sub, string $color, int $visits, int $clicks, int $book, array $btnCounts): array
    {
        return [
            'title'   => $title,
            'sub'     => $sub,
            'color'   => $color,
            'visits'  => $visits,
            'clicks'  => $clicks,
            // click per visita (rapporto): un visitatore puo' cliccare piu'
            // pulsanti, quindi NON e' una percentuale (puo' superare 1).
            'cpv'     => $visits > 0 ? round($clicks / $visits, 1) : 0,
            'book'    => $book,
            'conv'    => $visits > 0 ? (int)round($book / $visits * 100) : 0,
            'buttons' => $btnCounts,
            'vp'      => $book, // conversioni del pulsante "Prenota" = prenotazioni dell'Hub
        ];
    }

    private function toMap(array $rows): array
    {
        $m = [];
        foreach ($rows as $r) {
            $m[$r['channel']][$r['campaign']] = (int)$r['n'];
        }
        return $m;
    }

    private function sumAll(array $map): int
    {
        $t = 0;
        foreach ($map as $camps) foreach ($camps as $n) $t += $n;
        return $t;
    }

    private function sumCh(array $map, string $ch): int
    {
        $t = 0;
        foreach (($map[$ch] ?? []) as $n) $t += $n;
        return $t;
    }

    private function sumBtnAll(array $btnMap): array
    {
        $out = [];
        foreach ($btnMap as $camps) foreach ($camps as $refs) foreach ($refs as $ref => $n) {
            $out[$ref] = ($out[$ref] ?? 0) + $n;
        }
        return $out;
    }

    private function sumBtnCh(array $btnMap, string $ch): array
    {
        $out = [];
        foreach (($btnMap[$ch] ?? []) as $refs) foreach ($refs as $ref => $n) {
            $out[$ref] = ($out[$ref] ?? 0) + $n;
        }
        return $out;
    }

    /** Pulsanti configurati (hero+items) + ref extra visti nei click. */
    private function buildButtonList(array $tenant, array $btnMap): array
    {
        $buttons = [];
        $seen = [];
        try {
            $settings = (new HubSettings())->findByTenant((int)$tenant['id']) ?: [];
            $rendered = (new HubService())->getRenderableActions($tenant, $settings);
            if (!empty($rendered['hero'])) {
                $h = $rendered['hero'];
                $buttons[] = ['ref' => $h['ref'] ?? ($h['key'] ?? 'hero'), 'label' => $h['label'], 'icon' => $h['icon'], 'type' => 'hero'];
                $seen[$buttons[count($buttons) - 1]['ref']] = true;
            }
            foreach ($rendered['items'] as $it) {
                $ref = $it['ref'] ?? $it['type'];
                $buttons[] = ['ref' => $ref, 'label' => $it['label'], 'icon' => $it['icon'], 'type' => $it['type']];
                $seen[$ref] = true;
            }
        } catch (\Throwable $e) {
            // se la config non e' leggibile, ricostruiamo solo dai click
        }

        // ref extra presenti nei click ma non tra i pulsanti configurati
        // (social, oppure custom poi rimossi): li aggiungiamo per far quadrare.
        $extra = [];
        foreach ($this->sumBtnAll($btnMap) as $ref => $n) {
            if (isset($seen[$ref])) continue;
            $extra[$ref] = $n;
        }
        arsort($extra);
        foreach ($extra as $ref => $n) {
            $buttons[] = $this->describeRef($ref);
        }

        return $buttons;
    }

    private function describeRef(string $ref): array
    {
        if (str_starts_with($ref, 'social:')) {
            $name = substr($ref, 7);
            return ['ref' => $ref, 'label' => ucfirst($name), 'icon' => 'bi-' . $name, 'type' => 'social'];
        }
        if (str_starts_with($ref, 'custom:')) {
            return ['ref' => $ref, 'label' => 'Link personalizzato', 'icon' => 'bi-link-45deg', 'type' => 'custom'];
        }
        return ['ref' => $ref, 'label' => ucfirst($ref), 'icon' => 'bi-dot', 'type' => 'preset'];
    }
}
