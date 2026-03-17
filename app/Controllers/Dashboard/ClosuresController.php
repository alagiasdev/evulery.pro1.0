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

        view('dashboard/settings/closures', [
            'title'      => 'Chiusure e Ferie',
            'activeMenu' => 'closures',
            'upcoming'   => $upcoming,
            'past'       => array_reverse($past), // most recent first
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
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
}