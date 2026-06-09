<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Rider;
use App\Services\AuditLog;

/**
 * Gestione anagrafica rider e statistiche di consegna.
 *
 * Gating: il modulo e' utile SOLO se il tenant ha online_ordering attivo
 * con delivery_mode != 'none'. Senza delivery, gestire i rider e' inutile.
 * Senza online_ordering, la pagina non ha senso (non esistono ordini).
 *
 * Niente login dedicato per i rider: continuano a usare la board pubblica
 * /delivery/{token} con il PIN del ristoratore. Qui costruiamo solo
 * l'anagrafica + link orders.rider_id per attribuzione e statistiche.
 */
class RidersController
{
    /**
     * Gate combinato: ordering attivo + delivery configurato.
     * Redirect alla dashboard se il tenant non ha entrambi.
     */
    private function gate(): bool
    {
        if (!tenant_can('online_ordering')) {
            flash('warning', 'Questa funzionalità non è inclusa nel tuo piano.');
            Response::redirect(url('dashboard'));
            return true;
        }
        $t = TenantResolver::current();
        if (empty($t['ordering_enabled']) || ($t['delivery_mode'] ?? 'none') === 'none') {
            flash('warning', 'Per gestire i rider attiva il servizio di consegna a domicilio dalle impostazioni Ordini.');
            Response::redirect(url('dashboard/settings/ordering'));
            return true;
        }
        return false;
    }

    /** GET /dashboard/riders — lista anagrafica. */
    public function index(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $model = new Rider();
        $riders = $model->findAll($tenantId);

        // Contatore ordini di questo mese per ogni rider (mostrato in lista).
        // O(N) query, accettabile fino a ~50 rider per tenant — caso realistico
        // ha 2-5 rider, quindi 2-5 query da indice mirato. Non ottimizziamo
        // prematuramente con JOIN finche' non e' un problema reale.
        foreach ($riders as &$r) {
            $r['orders_this_month'] = $model->countOrdersThisMonth((int)$r['id']);
        }
        unset($r);

        view('dashboard/riders/index', [
            'title'      => 'Rider',
            'activeMenu' => 'riders',
            'riders'     => $riders,
            'palette'    => Rider::ALLOWED_COLORS,
        ], 'dashboard');
    }

    /** POST /dashboard/riders — crea nuovo rider. */
    public function store(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $data = $this->validated($request);
        if ($data === null) {
            Response::redirect(url('dashboard/riders'));
            return;
        }

        $id = (new Rider())->create($tenantId, $data);
        AuditLog::log('rider_created', "Rider creato: {$data['name']} (#{$id})", Auth::id(), $tenantId);

        flash('success', 'Rider aggiunto.');
        Response::redirect(url('dashboard/riders'));
    }

    /** POST /dashboard/riders/{id}/update — modifica rider esistente. */
    public function update(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');

        $model = new Rider();
        if (!$model->findById($id, $tenantId)) {
            flash('danger', 'Rider non trovato.');
            Response::redirect(url('dashboard/riders'));
            return;
        }

        $data = $this->validated($request);
        if ($data === null) {
            Response::redirect(url('dashboard/riders'));
            return;
        }

        $model->update($id, $tenantId, $data);
        AuditLog::log('rider_updated', "Rider aggiornato: {$data['name']} (#{$id})", Auth::id(), $tenantId);

        flash('success', 'Rider aggiornato.');
        Response::redirect(url('dashboard/riders'));
    }

    /** POST /dashboard/riders/{id}/toggle — attiva/disattiva. */
    public function toggleActive(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');
        $model = new Rider();
        $rider = $model->findById($id, $tenantId);
        if (!$rider) {
            flash('danger', 'Rider non trovato.');
            Response::redirect(url('dashboard/riders'));
            return;
        }
        $model->toggleActive($id, $tenantId);
        $newState = ((int)$rider['is_active'] === 1) ? 'disattivato' : 'riattivato';
        AuditLog::log('rider_toggle', "Rider {$newState}: {$rider['name']} (#{$id})", Auth::id(), $tenantId);

        flash('success', "Rider {$newState}.");
        Response::redirect(url('dashboard/riders'));
    }

    /**
     * POST /dashboard/riders/{id}/delete — soft delete definitivo.
     * Consentito SOLO su rider archiviati (is_active=0): evita eliminazioni
     * accidentali di rider operativi. La riga resta nel DB cosi' lo storico
     * ordini conserva il link al nome del rider per JOIN.
     */
    public function destroy(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');
        $model = new Rider();
        $rider = $model->findById($id, $tenantId);
        if (!$rider) {
            flash('danger', 'Rider non trovato.');
            Response::redirect(url('dashboard/riders'));
            return;
        }
        if ((int)$rider['is_active'] === 1) {
            flash('warning', 'Archivia il rider prima di eliminarlo definitivamente.');
            Response::redirect(url('dashboard/riders'));
            return;
        }

        if (!$model->softDelete($id, $tenantId)) {
            flash('danger', 'Impossibile eliminare il rider.');
            Response::redirect(url('dashboard/riders'));
            return;
        }

        AuditLog::log('rider_deleted', "Rider eliminato definitivamente: {$rider['name']} (#{$id})", Auth::id(), $tenantId);
        flash('success', "Rider \"{$rider['name']}\" eliminato definitivamente.");
        Response::redirect(url('dashboard/riders'));
    }

    /** GET /dashboard/riders/stats — KPI globali + tabella per rider. */
    public function stats(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();

        // Range default: oggi (chi apre la pagina al volo vede subito i numeri
        // del servizio in corso). Mese/7gg/30gg sono accessibili via chip.
        $dateFrom = $request->query('from', date('Y-m-d'));
        $dateTo   = $request->query('to', date('Y-m-d'));
        // Validazione minima formato; cade su oggi se input sporco
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-d');

        $stats = (new Rider())->getStats($tenantId, $dateFrom, $dateTo);

        // KPI globali aggregati dalla tabella per rider
        $kpi = [
            'total'        => array_sum(array_column($stats, 'total')),
            'completed'    => array_sum(array_column($stats, 'completed')),
            'cancelled'    => array_sum(array_column($stats, 'cancelled')),
            'total_value'  => array_sum(array_column($stats, 'total_value')),
        ];
        // Tempo medio globale: media pesata per numero ordini completati per rider
        $totalCompleted = 0; $weightedSum = 0;
        foreach ($stats as $s) {
            if ($s['avg_minutes'] !== null && $s['completed'] > 0) {
                $weightedSum    += $s['avg_minutes'] * $s['completed'];
                $totalCompleted += $s['completed'];
            }
        }
        $kpi['avg_minutes'] = $totalCompleted > 0 ? (int)round($weightedSum / $totalCompleted) : null;
        $kpi['completion_rate'] = $kpi['total'] > 0
            ? (int)round(($kpi['completed'] / $kpi['total']) * 100)
            : null;

        view('dashboard/riders/stats', [
            'title'      => 'Statistiche Rider',
            'activeMenu' => 'riders',
            'stats'      => $stats,
            'kpi'        => $kpi,
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
        ], 'dashboard');
    }

    /**
     * POST /dashboard/orders/{id}/assign-rider — assegna o rimuove rider.
     * Logica chiamata dalla pagina ordini, non da quella riders, ma resta
     * qui per tenere la business logic rider in un unico posto.
     * rider_id = 0 o vuoto → rimuove l'assegnazione (rider_id = NULL).
     */
    public function assignOrder(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $orderId = (int)$request->param('id');
        $riderIdInput = $request->input('rider_id');
        $riderId = ($riderIdInput === '' || $riderIdInput === null) ? null : (int)$riderIdInput;

        $db = Database::getInstance();

        // Verifica che l'ordine sia del tenant (tenant isolation)
        $stmt = $db->prepare('SELECT id, status FROM orders WHERE id = :id AND tenant_id = :tid LIMIT 1');
        $stmt->execute(['id' => $orderId, 'tid' => $tenantId]);
        $order = $stmt->fetch();
        if (!$order) {
            $this->jsonOrFlash($request, false, 'Ordine non trovato.');
            return;
        }

        // Se viene specificato un rider, deve esistere ed essere attivo del tenant
        if ($riderId !== null) {
            $rider = (new Rider())->findById($riderId, $tenantId);
            if (!$rider || (int)$rider['is_active'] !== 1) {
                $this->jsonOrFlash($request, false, 'Rider non valido o disattivato.');
                return;
            }
            $stmt = $db->prepare(
                'UPDATE orders SET rider_id = :rid, rider_assigned_at = NOW()
                 WHERE id = :id AND tenant_id = :tid'
            );
            $stmt->execute(['rid' => $riderId, 'id' => $orderId, 'tid' => $tenantId]);
            $msg = "Ordine #{$orderId} assegnato a {$rider['name']}";
        } else {
            // Rimuove assegnazione (rider_id NULL + reset timestamp).
            $stmt = $db->prepare(
                'UPDATE orders SET rider_id = NULL, rider_assigned_at = NULL
                 WHERE id = :id AND tenant_id = :tid'
            );
            $stmt->execute(['id' => $orderId, 'tid' => $tenantId]);
            $msg = "Assegnazione rimossa dall'ordine #{$orderId}";
        }

        AuditLog::log('rider_assign', $msg, Auth::id(), $tenantId);
        $this->jsonOrFlash($request, true, $msg);
    }

    /** Validazione + normalizzazione dei campi rider. */
    private function validated(Request $request): ?array
    {
        $name  = trim((string)$request->input('name', ''));
        $phone = trim((string)$request->input('phone', ''));
        $color = trim((string)$request->input('color_hex', ''));
        $active = (string)$request->input('is_active', '0') === '1';

        if ($name === '') {
            flash('danger', 'Il nome del rider è obbligatorio.');
            return null;
        }
        if (mb_strlen($name) > 100) {
            flash('danger', 'Il nome è troppo lungo (max 100 caratteri).');
            return null;
        }
        if ($phone !== '' && mb_strlen($phone) > 30) {
            flash('danger', 'Numero di telefono troppo lungo (max 30 caratteri).');
            return null;
        }

        return [
            'name'      => $name,
            'phone'     => $phone,
            'color_hex' => $color,
            'is_active' => $active ? 1 : 0,
        ];
    }

    /** Risponde JSON se la chiamata e' AJAX, altrimenti flash + redirect. */
    private function jsonOrFlash(Request $request, bool $ok, string $message): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                  || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if ($isAjax) {
            Response::json(['ok' => $ok, 'message' => $message], $ok ? 200 : 400);
            return;
        }
        flash($ok ? 'success' : 'danger', $message);
        Response::redirect(url('dashboard/orders'));
    }
}
