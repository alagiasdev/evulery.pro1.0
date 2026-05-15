<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\DemoRequest;
use App\Services\AuditLog;
use App\Services\MailService;

class LeadsController
{
    /**
     * GET /admin/leads — lista lead con filtri
     */
    public function index(Request $request): void
    {
        $leadModel = new DemoRequest();

        // Filtri da query string
        $filters = [
            'status'               => $request->query('status', '') ?: null,
            'assigned_reseller_id' => $request->query('assigned', '') ?: null,
            'search'               => trim($request->query('search', '')) ?: null,
            'date_from'            => $this->resolveDateFrom($request->query('period', '30d')),
        ];
        $filters = array_filter($filters, fn($v) => $v !== null);

        // Paginazione
        $page  = max(1, (int)$request->query('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $leads      = $leadModel->listFiltered($filters, $limit, $offset);
        $totalCount = $leadModel->countFiltered($filters);
        $statusCounts = $leadModel->countByStatus();

        // Reseller list (placeholder per fase 2: vuota finche' non ci sono reseller)
        $resellers = $this->getResellers();

        view('admin/leads/index', [
            'title'        => 'Lead',
            'activeMenu'   => 'leads',
            'leads'        => $leads,
            'totalCount'   => $totalCount,
            'statusCounts' => $statusCounts,
            'statuses'     => DemoRequest::STATUSES,
            'filters'      => $filters,
            'page'         => $page,
            'limit'        => $limit,
            'resellers'    => $resellers,
        ], 'admin');
    }

    /**
     * GET /admin/leads/{id} — dettaglio lead
     */
    public function show(Request $request): void
    {
        $id = (int)$request->param('id');
        $leadModel = new DemoRequest();
        $lead = $leadModel->findById($id);

        if (!$lead) {
            flash('danger', 'Lead non trovato.');
            Response::redirect(url('admin/leads'));
        }

        $activities = $leadModel->getActivities($id);
        $resellers  = $this->getResellers();

        // Risolve nome reseller assegnato
        $assignedResellerName = null;
        if ($lead['assigned_reseller_id']) {
            foreach ($resellers as $r) {
                if ((int)$r['id'] === (int)$lead['assigned_reseller_id']) {
                    $assignedResellerName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                    break;
                }
            }
        }

        view('admin/leads/show', [
            'title'      => 'Lead: ' . $lead['restaurant'],
            'activeMenu' => 'leads',
            'lead'       => $lead,
            'activities' => $activities,
            'statuses'   => DemoRequest::STATUSES,
            'activityLabels' => DemoRequest::ACTIVITY_LABELS,
            'resellers'  => $resellers,
            'assignedResellerName' => $assignedResellerName,
        ], 'admin');
    }

    /**
     * POST /admin/leads/{id} — aggiorna lead (status, assignment, follow-up, nota)
     */
    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $leadModel = new DemoRequest();
        $lead = $leadModel->findById($id);

        if (!$lead) {
            flash('danger', 'Lead non trovato.');
            Response::redirect(url('admin/leads'));
        }

        $data = $request->all();
        $userId = Auth::id();
        $db = Database::getInstance();

        $newStatus     = $data['status'] ?? $lead['status'];
        $newAssigned   = isset($data['assigned_reseller_id']) && $data['assigned_reseller_id'] !== ''
            ? (int)$data['assigned_reseller_id'] : null;
        $newFollowup   = $data['next_followup_at'] ?? null;
        $newNote       = trim($data['note'] ?? '');

        // Valida status
        if (!array_key_exists($newStatus, DemoRequest::STATUSES)) {
            $newStatus = $lead['status'];
        }

        $changes = [];

        // Cambio status
        if ($newStatus !== $lead['status']) {
            $db->prepare('UPDATE demo_requests SET status = :s, status_changed_at = NOW() WHERE id = :id')
                ->execute(['s' => $newStatus, 'id' => $id]);
            $oldLabel = DemoRequest::STATUSES[$lead['status']] ?? $lead['status'];
            $newLabel = DemoRequest::STATUSES[$newStatus];
            $leadModel->logActivity(
                $id,
                'status_changed',
                "Stato cambiato: {$oldLabel} → {$newLabel}",
                $userId,
                ['old_status' => $lead['status'], 'new_status' => $newStatus]
            );
            $changes[] = 'stato';
        }

        // Cambio assignment
        if ($newAssigned !== ($lead['assigned_reseller_id'] !== null ? (int)$lead['assigned_reseller_id'] : null)) {
            $db->prepare(
                'UPDATE demo_requests
                 SET assigned_reseller_id = :ar, assigned_at = NOW(), assigned_by = :ab
                 WHERE id = :id'
            )->execute(['ar' => $newAssigned, 'ab' => $userId, 'id' => $id]);

            if ($newAssigned === null) {
                $leadModel->logActivity($id, 'reassigned', 'Assegnazione rimossa', $userId);
            } else {
                $type = $lead['assigned_reseller_id'] ? 'reassigned' : 'assigned';
                $leadModel->logActivity(
                    $id,
                    $type,
                    "Assegnato a reseller #{$newAssigned}",
                    $userId,
                    ['reseller_id' => $newAssigned]
                );

                // Notifica email al reseller (best-effort, non blocca su errori)
                try {
                    $stmtR = $db->prepare(
                        "SELECT id, first_name, last_name, email
                         FROM users
                         WHERE id = :id AND role = 'reseller' AND is_active = 1
                         LIMIT 1"
                    );
                    $stmtR->execute(['id' => $newAssigned]);
                    $reseller = $stmtR->fetch();
                    if ($reseller) {
                        MailService::sendLeadAssignedToReseller(
                            $reseller,
                            $lead,
                            Auth::user()['name'] ?? null
                        );
                    }
                } catch (\Throwable $e) {
                    app_log('Reseller assign email error: ' . $e->getMessage());
                }
            }
            $changes[] = 'assegnazione';
        }

        // Follow-up
        if ($newFollowup) {
            $db->prepare('UPDATE demo_requests SET next_followup_at = :f WHERE id = :id')
                ->execute(['f' => $newFollowup, 'id' => $id]);
        }

        // Nuova nota
        if ($newNote !== '') {
            // Append a notes esistenti (con separator) + activity log
            $existing = trim($lead['notes'] ?? '');
            $timestamp = date('d/m/Y H:i');
            $userName = Auth::user()['name'] ?? 'Admin';
            $entry = "[{$timestamp} - {$userName}] {$newNote}";
            $merged = $existing ? $existing . "\n\n" . $entry : $entry;

            $db->prepare('UPDATE demo_requests SET notes = :n, last_contact_at = NOW() WHERE id = :id')
                ->execute(['n' => $merged, 'id' => $id]);
            $leadModel->logActivity($id, 'note_added', $newNote, $userId);
            $changes[] = 'nota';
        }

        if (!empty($changes)) {
            flash('success', 'Aggiornato: ' . implode(', ', $changes));
        }
        Response::redirect(url("admin/leads/{$id}"));
    }

    /**
     * POST /admin/leads/{id}/contact — corregge l'anagrafica del lead
     * (nome, ristorante, email, telefono). Caso d'uso: typo in fase di inserimento.
     */
    public function updateContact(Request $request): void
    {
        $id = (int)$request->param('id');
        $leadModel = new DemoRequest();
        $lead = $leadModel->findById($id);

        if (!$lead) {
            flash('danger', 'Lead non trovato.');
            Response::redirect(url('admin/leads'));
            return;
        }

        $data = $request->all();
        $name       = trim($data['name'] ?? '');
        $restaurant = trim($data['restaurant'] ?? '');
        $email      = normalize_email($data['email'] ?? '');
        $phone      = trim($data['phone'] ?? '');

        if (!$name || !$restaurant || !$email) {
            flash('danger', 'Nome, ristorante ed email sono obbligatori.');
            Response::redirect(url("admin/leads/{$id}"));
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Email non valida.');
            Response::redirect(url("admin/leads/{$id}"));
            return;
        }

        // Se l'email cambia, verifica che non collida con un altro lead
        if ($email !== $lead['email']) {
            $other = $leadModel->emailUsedByOtherLead($email, $id);
            if ($other) {
                flash('danger', "Email già usata dal lead #{$other['id']} ({$other['restaurant']}).");
                Response::redirect(url("admin/leads/{$id}"));
                return;
            }
        }

        // Diff per activity log
        $changes = [];
        foreach (['name' => 'Nome', 'restaurant' => 'Ristorante', 'email' => 'Email', 'phone' => 'Telefono'] as $field => $label) {
            $newVal = ${$field};
            if ((string)$lead[$field] !== (string)$newVal) {
                $changes[] = "{$label}: \"{$lead[$field]}\" → \"{$newVal}\"";
            }
        }

        if (empty($changes)) {
            flash('info', 'Nessuna modifica all\'anagrafica.');
            Response::redirect(url("admin/leads/{$id}"));
            return;
        }

        $leadModel->updateContact($id, compact('name', 'restaurant', 'email', 'phone'));
        $leadModel->logActivity($id, 'note_added', 'Anagrafica aggiornata — ' . implode(' · ', $changes), Auth::id());

        flash('success', 'Anagrafica aggiornata.');
        Response::redirect(url("admin/leads/{$id}"));
    }

    /**
     * GET /admin/leads/{id}/convert — pre-compila form crea-tenant col lead
     * Redirect a /admin/tenants/create con campi pre-compilati via query string
     */
    public function convert(Request $request): void
    {
        $id = (int)$request->param('id');
        $leadModel = new DemoRequest();
        $lead = $leadModel->findById($id);

        if (!$lead) {
            flash('danger', 'Lead non trovato.');
            Response::redirect(url('admin/leads'));
        }

        // Pre-compila tramite query string
        $params = http_build_query([
            'name'  => $lead['restaurant'],
            'email' => $lead['email'],
            'phone' => $lead['phone'],
            'owner_first_name' => explode(' ', $lead['name'], 2)[0] ?? $lead['name'],
            'owner_last_name'  => explode(' ', $lead['name'], 2)[1] ?? '',
            'owner_email'      => $lead['email'],
            'lead_id'          => $id,
        ]);
        Response::redirect(url('admin/tenants/create') . '?' . $params);
    }

    private function resolveDateFrom(string $period): ?string
    {
        return match ($period) {
            '7d'    => date('Y-m-d', strtotime('-7 days')),
            '30d'   => date('Y-m-d', strtotime('-30 days')),
            'month' => date('Y-m-01'),
            'all', '' => null,
            default => date('Y-m-d', strtotime('-30 days')),
        };
    }

    private function getResellers(): array
    {
        return Database::getInstance()->query(
            "SELECT id, first_name, last_name, email, is_active
             FROM users
             WHERE role = 'reseller' AND is_active = 1
             ORDER BY first_name, last_name"
        )->fetchAll();
    }
}
