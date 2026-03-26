<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Core\Validator;
use App\Models\SlotOverride;

class ClosuresController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $closures = (new SlotOverride())->findClosuresByTenant($tenantId);

        // Separate past and upcoming
        $today = date('Y-m-d');
        $upcoming = [];
        $past = [];
        foreach ($closures as $c) {
            if ($c['override_date'] >= $today) {
                $upcoming[] = $c;
            } else {
                $past[] = $c;
            }
        }

        // Group consecutive dates with same note into ranges
        $upcomingGroups = $this->groupConsecutive($upcoming);
        $pastGroups = $this->groupConsecutive(array_reverse($past), true);

        view('dashboard/settings/closures', [
            'title'      => 'Chiusure e Ferie',
            'activeMenu' => 'closures',
            'upcoming'   => $upcoming,
            'past'       => array_reverse($past),
            'upcomingGroups' => $upcomingGroups,
            'pastGroups'     => $pastGroups,
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }

    /**
     * Group consecutive dates with the same note into ranges.
     * Each group: [date_from, date_to, note, count, ids[]]
     */
    private function groupConsecutive(array $closures, bool $reverseOrder = false): array
    {
        if (empty($closures)) return [];

        // Ensure sorted by date ASC for grouping
        $sorted = $closures;
        usort($sorted, fn($a, $b) => $a['override_date'] <=> $b['override_date']);

        $groups = [];
        $current = [
            'date_from' => $sorted[0]['override_date'],
            'date_to'   => $sorted[0]['override_date'],
            'note'      => $sorted[0]['note'] ?? '',
            'count'     => 1,
            'ids'       => [$sorted[0]['id']],
        ];

        for ($i = 1; $i < count($sorted); $i++) {
            $prevDate = $current['date_to'];
            $thisDate = $sorted[$i]['override_date'];
            $thisNote = $sorted[$i]['note'] ?? '';

            // Consecutive date AND same note → extend group
            // Use DateTime to avoid DST issues with strtotime arithmetic
            $prev = new \DateTime($prevDate);
            $next = new \DateTime($thisDate);
            $dayDiff = (int)$prev->diff($next)->days;
            if ($dayDiff === 1 && $thisNote === $current['note']) {
                $current['date_to'] = $thisDate;
                $current['count']++;
                $current['ids'][] = $sorted[$i]['id'];
            } else {
                $groups[] = $current;
                $current = [
                    'date_from' => $thisDate,
                    'date_to'   => $thisDate,
                    'note'      => $thisNote,
                    'count'     => 1,
                    'ids'       => [$sorted[$i]['id']],
                ];
            }
        }
        $groups[] = $current;

        if ($reverseOrder) {
            $groups = array_reverse($groups);
        }

        return $groups;
    }

    public function store(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();

        $v = Validator::make($data)
            ->required('date_from', 'Data inizio')
            ->date('date_from', 'Data inizio');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url('dashboard/settings/closures'));
        }

        $dateFrom = $data['date_from'];
        $dateTo = !empty($data['date_to']) ? $data['date_to'] : $dateFrom;
        $note = !empty($data['note']) ? substr($data['note'], 0, 255) : null;

        // Validate date_to is not before date_from
        if ($dateTo < $dateFrom) {
            flash('danger', 'La data di fine non può essere prima della data di inizio.');
            Response::redirect(url('dashboard/settings/closures'));
        }

        // Limit range to 90 days to prevent abuse
        $daysDiff = (strtotime($dateTo) - strtotime($dateFrom)) / 86400;
        if ($daysDiff > 90) {
            flash('danger', 'Il range massimo è di 90 giorni.');
            Response::redirect(url('dashboard/settings/closures'));
        }

        $model = new SlotOverride();
        $count = $model->addClosureRange($tenantId, $dateFrom, $dateTo, $note);

        if ($count > 0) {
            $msg = $count === 1
                ? 'Chiusura aggiunta per il ' . date('d/m/Y', strtotime($dateFrom)) . '.'
                : "Aggiunte {$count} giornate di chiusura.";
            flash('success', $msg);
        } else {
            flash('info', 'Le date selezionate erano già impostate come chiuse.');
        }

        Response::redirect(url('dashboard/settings/closures'));
    }

    public function delete(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $model = new SlotOverride();
        if ($model->deleteClosure($id, $tenantId)) {
            flash('success', 'Chiusura rimossa.');
        } else {
            flash('danger', 'Chiusura non trovata.');
        }

        Response::redirect(url('dashboard/settings/closures'));
    }

    public function deleteGroup(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';

        if (!$dateFrom || !$dateTo) {
            flash('danger', 'Dati mancanti.');
            Response::redirect(url('dashboard/settings/closures'));
            return;
        }

        $model = new SlotOverride();
        $count = $model->deleteClosureRange($tenantId, $dateFrom, $dateTo);

        if ($count > 0) {
            $msg = $count === 1
                ? 'Chiusura rimossa.'
                : "{$count} chiusure rimosse.";
            flash('success', $msg);
        } else {
            flash('danger', 'Nessuna chiusura trovata nel periodo indicato.');
        }

        Response::redirect(url('dashboard/settings/closures'));
    }
}