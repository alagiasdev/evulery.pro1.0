<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\ReviewRequest;
use App\Services\AuditLog;
use App\Services\MailService;

class ReviewController
{
    private function gate(): bool
    {
        return gate_service('review_management', url('dashboard/reputation'));
    }

    /**
     * Panoramica: KPI + distribuzione voti + recent feedback.
     */
    public function index(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUse = tenant_can('review_management');

        $stats = [];
        $distribution = [];
        $recentFeedback = [];
        $monthlyStats = [];

        if ($canUse) {
            $model = new ReviewRequest();
            $tenantId = (int) $tenant['id'];
            $stats = $model->getStats($tenantId);
            $distribution = $model->getRatingDistribution($tenantId);
            $recentFeedback = $model->findByTenant($tenantId, ['has_feedback' => true], 5);
            $monthlyStats = $model->getMonthlyStats($tenantId, 4);
        }

        view('dashboard/reputation/index', [
            'title'           => 'Reputazione',
            'activeMenu'      => 'reputation',
            'tenant'          => $tenant,
            'canUse'          => $canUse,
            'stats'           => $stats,
            'distribution'    => $distribution,
            'recentFeedback'  => $recentFeedback,
            'monthlyStats'    => $monthlyStats,
        ], 'dashboard');
    }

    /**
     * Lista feedback con filtri + paginazione.
     */
    public function feedback(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $model = new ReviewRequest();

        $filters = [];
        if ($request->query('status')) {
            $filters['feedback_status'] = $request->query('status');
        }
        if ($request->query('rating_min')) {
            $filters['rating_min'] = $request->query('rating_min');
        }
        if ($request->query('rating_max')) {
            $filters['rating_max'] = $request->query('rating_max');
        }
        if ($request->query('search')) {
            $filters['search'] = $request->query('search');
        }
        // Always filter for feedback
        $filters['has_feedback'] = true;

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $items = $model->findByTenant($tenantId, $filters, $perPage, $offset);
        $total = $model->countByTenant($tenantId, $filters);
        $totalPages = max(1, (int) ceil($total / $perPage));

        view('dashboard/reputation/feedback', [
            'title'      => 'Feedback clienti',
            'activeMenu' => 'reputation',
            'tenant'     => TenantResolver::current(),
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'filters'    => $filters,
        ], 'dashboard');
    }

    /**
     * Timeline invii con paginazione.
     */
    public function history(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $model = new ReviewRequest();

        $filters = [];
        if ($request->query('source')) {
            $filters['source'] = $request->query('source');
        }
        if ($request->query('date_from')) {
            $filters['date_from'] = $request->query('date_from');
        }
        if ($request->query('date_to')) {
            $filters['date_to'] = $request->query('date_to');
        }
        if ($request->query('search')) {
            $filters['search'] = $request->query('search');
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $items = $model->findByTenant($tenantId, $filters, $perPage, $offset);
        $total = $model->countByTenant($tenantId, $filters);
        $totalPages = max(1, (int) ceil($total / $perPage));

        view('dashboard/reputation/history', [
            'title'      => 'Storico invii',
            'activeMenu' => 'reputation',
            'tenant'     => TenantResolver::current(),
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'filters'    => $filters,
        ], 'dashboard');
    }

    /**
     * JSON stats for polling.
     */
    public function apiStats(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $model = new ReviewRequest();
        $stats = $model->getStats($tenantId);

        header('Content-Type: application/json');
        echo json_encode($stats);
        exit;
    }

    /**
     * Salva risposta a feedback.
     */
    public function replyFeedback(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $model = new ReviewRequest();

        $rr = $model->findById($id, $tenantId);
        if (!$rr) {
            flash('danger', 'Feedback non trovato.');
            Response::redirect(url('dashboard/reputation/feedback'));
        }

        $reply = trim($request->input('reply', ''));
        if ($reply === '') {
            flash('danger', 'Inserisci una risposta.');
            Response::redirect(url('dashboard/reputation/feedback'));
        }

        $model->saveFeedbackReply($id, $reply);

        // Send reply email to customer (if email available — anonymous QR/NFC feedbacks have no email)
        $emailSent = false;
        try {
            $tenant = TenantResolver::current();
            $emailSent = MailService::sendFeedbackReply($rr, $tenant, $reply);
        } catch (\Throwable $e) {
            app_log("Feedback reply email failed: {$e->getMessage()}", 'error');
        }

        AuditLog::log(
            AuditLog::REVIEW_FEEDBACK_REPLIED,
            "Feedback #{$id}" . ($emailSent ? ' (email inviata)' : ''),
            Auth::id(),
            $tenantId
        );

        if ($emailSent) {
            flash('success', 'Risposta inviata al cliente.');
        } elseif (empty($rr['email'])) {
            flash('warning', 'Risposta salvata. Il cliente ha lasciato un feedback anonimo, non riceverà email.');
        } else {
            flash('warning', 'Risposta salvata, ma l\'invio email è fallito. Riprova o contatta il cliente direttamente.');
        }
        Response::redirect(url('dashboard/reputation/feedback'));
    }

    /**
     * Aggiorna stato feedback (mark read).
     */
    public function updateFeedbackStatus(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $model = new ReviewRequest();

        $rr = $model->findById($id, $tenantId);
        if (!$rr) {
            flash('danger', 'Feedback non trovato.');
            Response::redirect(url('dashboard/reputation/feedback'));
        }

        $status = $request->input('status', 'read');
        if (!in_array($status, ['new', 'read', 'replied'])) {
            $status = 'read';
        }

        $model->updateFeedbackStatus($id, $status);

        flash('success', 'Stato aggiornato.');
        Response::redirect(url('dashboard/reputation/feedback'));
    }
}
