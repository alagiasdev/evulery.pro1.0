<?php

namespace App\Controllers\Hub;

use App\Core\Request;
use App\Core\Response;
use App\Models\HubSettings;
use App\Models\Promotion;
use App\Models\Tenant;

/**
 * Public promotions list page rendered at /{slug}/promo.
 * Linked dalla Vetrina Digitale (azione "Offerte del momento") e
 * direttamente condivisibile.
 */
class PromotionsPublicController
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

        // Service gating + abbonamento attivo
        if (!$tenantModel->canUseService((int)$tenant['id'], 'promotions')
            || $tenantModel->getExpiredSubscription((int)$tenant['id'])) {
            Response::redirect(url($slug));
            return;
        }

        $promos = (new Promotion())->findActiveByTenant((int)$tenant['id']);

        // Marca le promo applicabili "adesso" e ordina (applicabili prima)
        $promoModel = new Promotion();
        $now = new \DateTimeImmutable('now');
        $today = $now->format('Y-m-d');
        $time  = $now->format('H:i');

        $decorated = array_map(function ($p) use ($promoModel, $tenant, $today, $time) {
            $reservationsApplicable = $promoModel->findApplicable((int)$tenant['id'], $today, $time, 'reservations');
            $ordersApplicable       = $promoModel->findApplicable((int)$tenant['id'], $today, $time, 'orders');
            $isLiveNow = ($reservationsApplicable && (int)$reservationsApplicable['id'] === (int)$p['id'])
                      || ($ordersApplicable       && (int)$ordersApplicable['id']       === (int)$p['id']);
            $p['_is_live_now'] = $isLiveNow;
            $p['_when_label']  = $this->formatWhen($p);
            $p['_applies_to_label'] = $this->formatAppliesTo($p['applies_to'] ?? 'all');
            return $p;
        }, $promos);

        // Ordine: live first, poi sconto decrescente
        usort($decorated, function ($a, $b) {
            if ($a['_is_live_now'] !== $b['_is_live_now']) return $a['_is_live_now'] ? -1 : 1;
            return (int)$b['discount_percent'] <=> (int)$a['discount_percent'];
        });

        // Branding: usa lo stesso colore/cover/logo della Vetrina (se hub abilitato)
        $settings = (new HubSettings())->findByTenant((int)$tenant['id']) ?? [];

        // Plan gating: custom colors + white-label sono Enterprise-only.
        // Al downgrade il flag resta in DB ma non viene applicato.
        if (!$tenantModel->isEnterprise((int)$tenant['id'])) {
            $settings['custom_colors_enabled'] = 0;
            $settings['hide_branding']         = 0;
        }
        $colors = (new HubSettings())->resolveColors($settings);

        view('hub/promotions', [
            'tenant'   => $tenant,
            'settings' => $settings,
            'colors'   => $colors,
            'promos'   => $decorated,
        ]);
    }

    /**
     * Stringa human-readable per "quando vale" la promo.
     */
    private function formatWhen(array $p): string
    {
        $type = $p['type'] ?? 'recurring';

        if ($type === 'specific_date') {
            $from = $p['date_from'] ?? null;
            $to   = $p['date_to']   ?? null;
            $dates = $from && $to && $from !== $to
                ? 'Dal ' . $this->shortDate($from) . ' al ' . $this->shortDate($to)
                : ($from ? 'Solo il ' . $this->shortDate($from) : '');
            $time = $this->timeRange($p);
            return trim($dates . ($time ? ' · ' . $time : ''));
        }

        // recurring / time_slot (legacy)
        $days = $this->daysLabel($p['days_of_week'] ?? null);
        $time = $this->timeRange($p);
        $parts = array_filter([$days, $time]);
        return $parts ? implode(' · ', $parts) : 'Sempre attiva';
    }

    private function daysLabel(?string $days): string
    {
        if (!$days) return 'Tutti i giorni';
        $map = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
        $list = array_map('intval', explode(',', $days));
        sort($list);
        if (count($list) === 7) return 'Tutti i giorni';
        return implode(' · ', array_map(fn($d) => $map[$d] ?? '', $list));
    }

    private function timeRange(array $p): string
    {
        if (empty($p['time_from']) || empty($p['time_to'])) return '';
        return substr($p['time_from'], 0, 5) . '–' . substr($p['time_to'], 0, 5);
    }

    private function shortDate(string $isoDate): string
    {
        $ts = strtotime($isoDate);
        return $ts ? date('d/m/Y', $ts) : $isoDate;
    }

    private function formatAppliesTo(string $appliesTo): string
    {
        return match ($appliesTo) {
            'reservations' => 'Solo prenotazioni',
            'orders'       => 'Solo ordini online',
            default        => 'Prenotazioni e ordini',
        };
    }
}
