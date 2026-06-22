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

        // Il beacon invia un body JSON (application/json): va letto via json(),
        // non input() (che vede solo $_POST/$_GET). Stesso pattern dello store.
        $data = $request->isJson() ? $request->json() : $request->all();

        $ref  = AttributionService::sanitize($data['ref'] ?? null, 60);
        $type = (string)($data['type'] ?? 'preset');
        if (!in_array($type, ['hero', 'preset', 'custom', 'social'], true)) {
            $type = 'preset';
        }
        if ($ref === null) {
            Response::noContent();
            return;
        }

        $label = AttributionService::sanitize($data['label'] ?? null, 120);
        $src   = AttributionService::sanitize($data['utm_source'] ?? null, 100);
        $med   = AttributionService::sanitize($data['utm_medium'] ?? null, 60);
        $camp  = AttributionService::sanitize($data['utm_campaign'] ?? null, 120);
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
