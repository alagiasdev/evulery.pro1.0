<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Services\HeartbeatService;

/**
 * Endpoint per polling auto-refresh (Fase C).
 *
 * Lo usano i moduli JS lato dashboard per scoprire se i dati visualizzati
 * sono stale rispetto al DB. Risponde sempre con cache-control no-store
 * e supporta If-None-Match per ridurre il payload a 304 quando l'hash
 * non e' cambiato dall'ultima chiamata del client.
 */
class HeartbeatController
{
    /**
     * GET /dashboard/heartbeat/reservations?date=YYYY-MM-DD&date_to=YYYY-MM-DD
     */
    public function reservations(Request $request): void
    {
        $tenantId = Auth::tenantId();

        $date   = $this->validDate($request->query('date', date('Y-m-d')));
        $dateTo = $request->query('date_to');
        $dateTo = $dateTo ? $this->validDate($dateTo) : null;

        $state = HeartbeatService::forReservations($tenantId, $date, $dateTo);
        $this->respondWithEtag($state);
    }

    /**
     * GET /dashboard/heartbeat/floor?date=YYYY-MM-DD
     *
     * Gatato sul servizio table_management (Enterprise): se il tenant non ha
     * accesso ritorna 403, il polling lato JS smette di triggerare il banner.
     */
    public function floor(Request $request): void
    {
        if (!tenant_can('table_management')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'service_not_available']);
            exit;
        }

        $tenantId = Auth::tenantId();
        $date = $this->validDate($request->query('date', date('Y-m-d')));

        $state = HeartbeatService::forFloor($tenantId, $date);
        $this->respondWithEtag($state);
    }

    /**
     * Emette ETag + 304/200 JSON, factor comune per i due endpoint.
     *
     * @param array{hash:string, last_updated_at:?string, count:int} $state
     */
    private function respondWithEtag(array $state): void
    {
        $etag = '"' . $state['hash'] . '"';
        $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('ETag: ' . $etag);

        if ($clientEtag === $etag) {
            http_response_code(304);
            exit;
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'hash'            => $state['hash'],
            'last_updated_at' => $state['last_updated_at'],
            'count'           => $state['count'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function validDate(string $raw): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $raw);
        if ($d && $d->format('Y-m-d') === $raw) {
            return $raw;
        }
        return date('Y-m-d');
    }
}
