<?php

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\DemoRequest;
use PDO;

/**
 * Pagine lead per il reseller.
 * Visibilità ristretta: vede SOLO i lead a lui assegnati e SOLO dati
 * essenziali (no IP, no referrer, no UTM, no note admin, no assignment data).
 * Può: aggiornare stato (escluso "customer", solo admin converte),
 * aggiungere note, impostare next_followup_at.
 */
class LeadsController
{
    // Status che il reseller può impostare (NO "customer" — solo admin convert)
    private const ALLOWED_STATUSES = [
        'new', 'contacted', 'demo_scheduled', 'demo_done', 'negotiating', 'lost',
    ];

    public function index(Request $request): void
    {
        $userId = Auth::id();
        $leadModel = new DemoRequest();

        $statusFilter = $request->query('status', '') ?: null;
        $search = trim($request->query('search', '')) ?: null;
        $page = max(1, (int)$request->query('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [
            'assigned_reseller_id' => $userId,
        ];
        if ($statusFilter && array_key_exists($statusFilter, DemoRequest::STATUSES)) {
            $filters['status'] = $statusFilter;
        }
        if ($search) {
            $filters['search'] = $search;
        }

        $leads = $leadModel->listFiltered($filters, $limit, $offset);
        $totalCount = $leadModel->countFiltered($filters);

        // Counter per status (solo lead miei)
        $statusCounts = $this->myStatusCounts($userId);

        view('reseller/leads/index', [
            'title'        => 'I miei lead',
            'activeMenu'   => 'reseller-leads',
            'leads'        => $leads,
            'totalCount'   => $totalCount,
            'statusCounts' => $statusCounts,
            'statuses'     => DemoRequest::STATUSES,
            'filterStatus' => $statusFilter,
            'filterSearch' => $search,
            'page'         => $page,
            'limit'        => $limit,
        ], 'reseller');
    }

    public function show(Request $request): void
    {
        $id = (int)$request->param('id');
        $userId = Auth::id();
        $leadModel = new DemoRequest();
        $lead = $leadModel->findById($id);

        if (!$lead || (int)($lead['assigned_reseller_id'] ?? 0) !== $userId) {
            flash('danger', 'Lead non trovato o non assegnato a te.');
            Response::redirect(url('reseller/leads'));
            return;
        }

        // Attività complete (anche admin private vengono mostrate al reseller?
        // Politica: mostriamo solo le attività che il reseller stesso ha generato
        // + cambi di stato, per coerenza con la regola "no note admin private".
        $activities = $this->filterActivitiesForReseller(
            $leadModel->getActivities($id),
            $userId
        );

        view('reseller/leads/show', [
            'title'          => 'Lead: ' . $lead['restaurant'],
            'activeMenu'     => 'reseller-leads',
            'lead'           => $lead,
            'activities'     => $activities,
            'statuses'       => DemoRequest::STATUSES,
            'allowedStatuses'=> self::ALLOWED_STATUSES,
            'activityLabels' => DemoRequest::ACTIVITY_LABELS,
        ], 'reseller');
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $userId = Auth::id();
        $leadModel = new DemoRequest();
        $lead = $leadModel->findById($id);

        if (!$lead || (int)($lead['assigned_reseller_id'] ?? 0) !== $userId) {
            flash('danger', 'Lead non trovato o non assegnato a te.');
            Response::redirect(url('reseller/leads'));
            return;
        }

        $data = $request->all();
        $db = Database::getInstance();

        $newStatus   = $data['status'] ?? $lead['status'];
        $newFollowup = $data['next_followup_at'] ?? null;
        $newNote     = trim($data['note'] ?? '');

        // Valida status: solo quelli permessi al reseller
        if (!in_array($newStatus, self::ALLOWED_STATUSES, true)) {
            $newStatus = $lead['status'];
        }

        $changes = [];

        if ($newStatus !== $lead['status']) {
            $db->prepare('UPDATE demo_requests SET status = :s, status_changed_at = NOW() WHERE id = :id')
                ->execute(['s' => $newStatus, 'id' => $id]);
            $oldLabel = DemoRequest::STATUSES[$lead['status']] ?? $lead['status'];
            $newLabel = DemoRequest::STATUSES[$newStatus];
            $leadModel->logActivity(
                $id,
                'status_changed',
                "Stato: {$oldLabel} → {$newLabel}",
                $userId,
                ['old_status' => $lead['status'], 'new_status' => $newStatus]
            );
            $changes[] = 'stato';
        }

        if ($newFollowup !== null && $newFollowup !== '') {
            // Accetta solo datetime-local 'Y-m-d\TH:i' o 'Y-m-d H:i'
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $newFollowup)
                ?: \DateTime::createFromFormat('Y-m-d H:i', $newFollowup)
                ?: \DateTime::createFromFormat('Y-m-d H:i:s', $newFollowup);
            if ($dt) {
                $db->prepare('UPDATE demo_requests SET next_followup_at = :f WHERE id = :id')
                    ->execute(['f' => $dt->format('Y-m-d H:i:s'), 'id' => $id]);
                $changes[] = 'follow-up';
            }
        }

        if ($newNote !== '') {
            $existing = trim($lead['notes'] ?? '');
            $timestamp = date('d/m/Y H:i');
            $userName = Auth::user()['name'] ?? 'Reseller';
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
        Response::redirect(url("reseller/leads/{$id}"));
    }

    private function myStatusCounts(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT status, COUNT(*) AS cnt
             FROM demo_requests
             WHERE assigned_reseller_id = :uid
             GROUP BY status"
        );
        $stmt->execute(['uid' => $userId]);
        $out = array_fill_keys(array_keys(DemoRequest::STATUSES), 0);
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['status']] = (int)$row['cnt'];
        }
        return $out;
    }

    /**
     * Mostriamo al reseller solo:
     * - eventi di sistema utili (status_changed, assigned, converted)
     * - eventi creati da lui stesso (note_added, contacted, ecc.)
     * Nascondiamo note/eventi creati da super_admin che potrebbero essere private.
     */
    private function filterActivitiesForReseller(array $activities, int $resellerId): array
    {
        $publicTypes = ['status_changed', 'assigned', 'reassigned', 'converted', 'created'];
        return array_filter($activities, function ($a) use ($publicTypes, $resellerId) {
            // Tutti i tipi pubblici visibili
            if (in_array($a['type'], $publicTypes, true)) {
                return true;
            }
            // Note e contatti: solo se creati dal reseller stesso
            return (int)($a['performed_by'] ?? 0) === $resellerId;
        });
    }
}
