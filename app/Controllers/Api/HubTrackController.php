<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\HubClick;
use App\Models\Tenant;
use App\Services\AttributionService;

/**
 * Endpoint pubblico per i click sui pulsanti della Vetrina/Hub (beacon).
 * Riceve dal browser il pulsante cliccato + l'UTM della pagina e registra il
 * click. Fire-and-forget: risponde 204 e non blocca mai la navigazione.
 */
class HubTrackController
{
    public function click(Request $request): void
    {
        $slug = (string)$request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);
        if (!$tenant || !$tenant['is_active']) {
            Response::noContent();
            return;
        }

        $ref  = AttributionService::sanitize($request->input('ref'), 60);
        $type = (string)$request->input('type', 'preset');
        if (!in_array($type, ['hero', 'preset', 'custom', 'social'], true)) {
            $type = 'preset';
        }
        if ($ref === null) {
            Response::noContent();
            return;
        }

        $label = AttributionService::sanitize($request->input('label'), 120);
        $src   = AttributionService::sanitize($request->input('utm_source'), 100);
        $med   = AttributionService::sanitize($request->input('utm_medium'), 60);
        $camp  = AttributionService::sanitize($request->input('utm_campaign'), 120);
        // canale: UTM se presente, altrimenti accesso diretto alla Vetrina -> 'hub'
        $channel = $src ? AttributionService::deriveChannel($src, $med, null) : 'hub';

        try {
            (new HubClick())->record((int)$tenant['id'], $ref, $type, $label, $channel, $src, $med, $camp);
        } catch (\Throwable $e) {
            // tracking best-effort: non deve mai disturbare l'utente
        }

        Response::noContent();
    }
}
