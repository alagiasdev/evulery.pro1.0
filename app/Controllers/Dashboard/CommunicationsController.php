<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Services\AuditLog;
use App\Services\BroadcastService;

class CommunicationsController
{
    private function gate(): bool
    {
        return gate_service('email_broadcast');
    }

    private function getThresholds(array $tenant): array
    {
        return [
            'occ' => (int)($tenant['segment_occasionale'] ?? 2),
            'abi' => (int)($tenant['segment_abituale'] ?? 4),
            'vip' => (int)($tenant['segment_vip'] ?? 10),
        ];
    }

    public function index(Request $request): void
    {
        $canUse = tenant_can('email_broadcast');

        if (!$canUse) {
            view('dashboard/communications/index', [
                'title'      => 'Comunicazioni',
                'activeMenu' => 'communications',
                'canUse'     => false,
                'campaigns'  => [],
                'pagination' => null,
                'kpi'        => ['total_campaigns' => 0, 'sent_campaigns' => 0, 'total_sent' => 0],
                'credits'    => 0,
            ], 'dashboard');
            return;
        }

        $tenantId = Auth::tenantId();
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 15;
        $showArchived = $request->query('archived') === '1';

        $model = new EmailCampaign();
        $baseUrl = url('dashboard/communications') . ($showArchived ? '?archived=1' : '');
        $total = $model->countByTenant($tenantId, $showArchived);

        $paginator = new Paginator($total, $perPage, $page, $baseUrl);
        $campaigns = $model->findByTenant($tenantId, $paginator->limit(), $paginator->offset(), $showArchived);
        $kpi = $model->getKpi($tenantId);

        $tenant = TenantResolver::current();
        $credits = (int)($tenant['email_credits_balance'] ?? 0);
        $archivedCount = $showArchived ? 0 : $model->countByTenant($tenantId, true);

        view('dashboard/communications/index', [
            'title'         => 'Comunicazioni',
            'activeMenu'    => 'communications',
            'canUse'        => true,
            'campaigns'     => $campaigns,
            'pagination'    => $paginator->links(),
            'kpi'           => $kpi,
            'credits'       => $credits,
            'showArchived'  => $showArchived,
            'archivedCount' => $archivedCount,
        ], 'dashboard');
    }

    public function credits(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 20;

        $db = Database::getInstance();

        // Count total
        $stmt = $db->prepare('SELECT COUNT(*) FROM email_credit_transactions WHERE tenant_id = :tid');
        $stmt->execute(['tid' => $tenantId]);
        $total = (int)$stmt->fetchColumn();

        $baseUrl = url('dashboard/communications/credits');
        $paginator = new Paginator($total, $perPage, $page, $baseUrl);

        // Fetch transactions
        $stmt = $db->prepare(
            'SELECT t.*, ec.subject AS campaign_subject
             FROM email_credit_transactions t
             LEFT JOIN email_campaigns ec ON ec.id = t.campaign_id
             WHERE t.tenant_id = :tid
             ORDER BY t.created_at DESC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue('tid', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $paginator->limit(), \PDO::PARAM_INT);
        $stmt->bindValue('off', $paginator->offset(), \PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll();

        $tenant = TenantResolver::current();
        $credits = (int)($tenant['email_credits_balance'] ?? 0);

        view('dashboard/communications/credits', [
            'title'        => 'Storico Crediti',
            'activeMenu'   => 'communications',
            'transactions' => $transactions,
            'pagination'   => $paginator->links(),
            'credits'      => $credits,
            'total'        => $total,
        ], 'dashboard');
    }

    public function create(Request $request): void
    {
        if ($this->gate()) return;

        $tenant = TenantResolver::current();
        $credits = (int)($tenant['email_credits_balance'] ?? 0);

        view('dashboard/communications/create', [
            'title'      => 'Nuova Comunicazione',
            'activeMenu' => 'communications',
            'credits'    => $credits,
        ], 'dashboard');
    }

    /**
     * JSON endpoint: preview recipient count for given segment.
     */
    public function preview(Request $request): void
    {
        if (!tenant_can('email_broadcast')) {
            Response::json(['count' => 0]);
            return;
        }

        $tenantId = Auth::tenantId();
        $segment = $request->query('segment', 'all');
        $inactiveDays = $request->query('inactive_days', null);
        $inactiveDays = $inactiveDays !== null ? (int)$inactiveDays : null;

        $tenant = TenantResolver::current();
        $thresholds = $this->getThresholds($tenant);

        $validSegments = ['all', 'nuovo', 'occasionale', 'abituale', 'vip', 'inactive'];
        if (!in_array($segment, $validSegments)) {
            $segment = 'all';
        }

        $count = BroadcastService::countRecipients($tenantId, $segment, $inactiveDays, $thresholds);
        Response::json(['count' => $count]);
    }

    public function store(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $tenant = TenantResolver::current();
        $thresholds = $this->getThresholds($tenant);

        $subject = trim($request->input('subject', ''));
        $bodyText = trim($request->input('body_text', ''));
        $segment = $request->input('segment_filter', 'all');
        $inactiveDays = $request->input('inactive_days', null);
        $inactiveDays = $inactiveDays !== null ? (int)$inactiveDays : null;

        // Validation
        if (empty($subject) || mb_strlen($subject) > 255) {
            flash('danger', 'L\'oggetto è obbligatorio (max 255 caratteri).');
            Response::redirect(url('dashboard/communications/create'));
            return;
        }

        if (empty($bodyText) || mb_strlen($bodyText) > 5000) {
            flash('danger', 'Il corpo del messaggio è obbligatorio (max 5000 caratteri).');
            Response::redirect(url('dashboard/communications/create'));
            return;
        }

        $validSegments = ['all', 'nuovo', 'occasionale', 'abituale', 'vip', 'inactive'];
        if (!in_array($segment, $validSegments)) {
            $segment = 'all';
        }

        // Rate limit: max 1/day
        $campaignModel = new EmailCampaign();
        if (!$campaignModel->canSendToday($tenantId)) {
            flash('danger', 'Puoi inviare massimo una comunicazione al giorno.');
            Response::redirect(url('dashboard/communications/create'));
            return;
        }

        // Get recipients
        $recipients = BroadcastService::getRecipients($tenantId, $segment, $inactiveDays, $thresholds);
        $recipientCount = count($recipients);

        if ($recipientCount === 0) {
            flash('warning', 'Nessun destinatario trovato per il segmento selezionato.');
            Response::redirect(url('dashboard/communications/create'));
            return;
        }

        // Check credits
        $credits = (int)($tenant['email_credits_balance'] ?? 0);
        if ($credits < $recipientCount) {
            flash('danger', "Crediti insufficienti. Servono {$recipientCount} crediti, ne hai {$credits}.");
            Response::redirect(url('dashboard/communications/create'));
            return;
        }

        // Create campaign
        $campaignId = $campaignModel->create([
            'tenant_id'      => $tenantId,
            'subject'        => $subject,
            'body_text'      => $bodyText,
            'segment_filter' => $segment,
            'inactive_days'  => $segment === 'inactive' ? $inactiveDays : null,
            'created_by'     => Auth::id(),
        ]);

        // Insert recipients
        $campaignModel->insertRecipients($campaignId, $recipients);
        $campaignModel->updateTotalRecipients($campaignId, $recipientCount, $recipientCount);

        // Deduct credits
        $tenantModel = new Tenant();
        $tenantModel->deductCredits($tenantId, $recipientCount);

        // Log credit transaction
        $db = Database::getInstance();
        $db->prepare(
            'INSERT INTO email_credit_transactions (tenant_id, amount, type, description, campaign_id, created_at)
             VALUES (:tid, :amount, :type, :desc, :cid, NOW())'
        )->execute([
            'tid'    => $tenantId,
            'amount' => -$recipientCount,
            'type'   => 'usage',
            'desc'   => "Campagna: {$subject} ({$recipientCount} destinatari)",
            'cid'    => $campaignId,
        ]);

        // Queue campaign
        $campaignModel->updateStatus($campaignId, 'queued');

        AuditLog::log(
            AuditLog::EMAIL_BROADCAST_CREATED,
            "Campagna \"{$subject}\" — {$recipientCount} destinatari, segmento: {$segment}",
            Auth::id(),
            $tenantId
        );

        // Refresh tenant cache
        TenantResolver::refreshCurrent();

        flash('success', "Comunicazione in coda! Verrà inviata a {$recipientCount} destinatari.");
        Response::redirect(url('dashboard/communications'));
    }

    public function show(Request $request): void
    {
        if ($this->gate()) return;

        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $model = new EmailCampaign();
        $campaign = $model->findById($id);

        if (!$campaign || (int)$campaign['tenant_id'] !== $tenantId) {
            flash('danger', 'Comunicazione non trovata.');
            Response::redirect(url('dashboard/communications'));
            return;
        }

        view('dashboard/communications/show', [
            'title'      => 'Dettaglio Comunicazione',
            'activeMenu' => 'communications',
            'campaign'   => $campaign,
        ], 'dashboard');
    }

    public function destroy(Request $request): void
    {
        if ($this->gate()) return;

        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $model = new EmailCampaign();
        $campaign = $model->findById($id);

        if (!$campaign || (int)$campaign['tenant_id'] !== $tenantId) {
            flash('danger', 'Comunicazione non trovata.');
            Response::redirect(url('dashboard/communications'));
            return;
        }

        if (!in_array($campaign['status'], ['draft', 'queued'])) {
            flash('danger', 'Solo le bozze e le campagne in coda possono essere eliminate.');
            Response::redirect(url('dashboard/communications'));
            return;
        }

        // Refund unused credits for queued campaigns
        $refunded = 0;
        if ($campaign['status'] === 'queued') {
            $refunded = $model->countPendingRecipients($id);
            if ($refunded > 0) {
                $tenantModel = new Tenant();
                $tenantModel->addCredits($tenantId, $refunded);

                $db = Database::getInstance();
                $db->prepare(
                    'INSERT INTO email_credit_transactions (tenant_id, amount, type, description, campaign_id, created_at)
                     VALUES (:tid, :amount, :type, :desc, :cid, NOW())'
                )->execute([
                    'tid'    => $tenantId,
                    'amount' => $refunded,
                    'type'   => 'refund',
                    'desc'   => "Annullamento campagna: \"{$campaign['subject']}\" ({$refunded} crediti rimborsati)",
                    'cid'    => $id,
                ]);
            }
        }

        $model->delete($id);

        AuditLog::log(
            AuditLog::EMAIL_BROADCAST_DELETED,
            "Campagna \"{$campaign['subject']}\" eliminata" . ($refunded > 0 ? " ({$refunded} crediti rimborsati)" : ''),
            Auth::id(),
            $tenantId
        );

        // Refresh tenant cache for updated credits
        TenantResolver::refreshCurrent();

        $msg = 'Comunicazione eliminata.';
        if ($refunded > 0) {
            $msg .= " {$refunded} crediti rimborsati.";
        }
        flash('success', $msg);
        Response::redirect(url('dashboard/communications'));
    }

    public function archive(Request $request): void
    {
        if ($this->gate()) return;

        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $model = new EmailCampaign();
        $campaign = $model->findById($id);

        if (!$campaign || (int)$campaign['tenant_id'] !== $tenantId) {
            flash('danger', 'Comunicazione non trovata.');
            Response::redirect(url('dashboard/communications'));
            return;
        }

        if (!in_array($campaign['status'], ['sent', 'failed'])) {
            flash('danger', 'Solo le campagne inviate o fallite possono essere archiviate.');
            Response::redirect(url('dashboard/communications'));
            return;
        }

        $model->archive($id);

        flash('success', 'Comunicazione archiviata.');
        Response::redirect(url('dashboard/communications'));
    }

    public function retryFailed(Request $request): void
    {
        if ($this->gate()) return;

        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $model = new EmailCampaign();
        $campaign = $model->findById($id);

        if (!$campaign || (int)$campaign['tenant_id'] !== $tenantId) {
            flash('danger', 'Comunicazione non trovata.');
            Response::redirect(url('dashboard/communications'));
            return;
        }

        if ($campaign['status'] !== 'sent') {
            flash('danger', 'Solo le campagne inviate possono essere ritentate.');
            Response::redirect(url("dashboard/communications/{$id}"));
            return;
        }

        $failedCount = $model->countFailedRecipients($id);
        if ($failedCount === 0) {
            flash('info', 'Nessun destinatario fallito da ritentare.');
            Response::redirect(url("dashboard/communications/{$id}"));
            return;
        }

        // Reset failed recipients to pending
        $model->resetFailedToPending($id);

        // Extend timeout
        set_time_limit(300);

        $tenant = TenantResolver::current();
        $db = Database::getInstance();

        $model->updateStatus($id, 'sending');

        $mailer = BroadcastService::createBroadcastMailer();
        $sent = 0;
        $failed = 0;

        while (true) {
            $recipients = $model->getPendingRecipients($id, 50);
            if (empty($recipients)) break;

            foreach ($recipients as $recipient) {
                $token = BroadcastService::generateUnsubscribeToken($tenantId, $recipient['email']);
                $unsubscribeUrl = rtrim(env('APP_URL', ''), '/') . '/email/unsubscribe/' . $token;
                $html = BroadcastService::buildEmailHtml($campaign['body_text'], $tenant, $unsubscribeUrl);

                $ok = BroadcastService::sendOne(
                    $mailer,
                    $recipient['email'],
                    $campaign['subject'],
                    $html,
                    $tenant['name'],
                    $tenant['email'] ?? null
                );

                if ($ok) {
                    $model->updateRecipientStatus((int)$recipient['id'], 'sent');
                    $sent++;
                } else {
                    $model->updateRecipientStatus((int)$recipient['id'], 'failed');
                    $failed++;
                }

                usleep(100000);
            }
        }

        $newSentTotal = (int)$campaign['sent_count'] + $sent;
        $model->updateCounts($id, $newSentTotal, $failed);
        $db->prepare('UPDATE email_campaigns SET status = :status WHERE id = :id')
           ->execute(['status' => 'sent', 'id' => $id]);

        AuditLog::log(
            AuditLog::EMAIL_BROADCAST_CREATED,
            "Campagna \"{$campaign['subject']}\" — ritentati {$failedCount} falliti: {$sent} OK, {$failed} ancora falliti",
            Auth::id(),
            $tenantId
        );

        flash('success', "Ritentato invio: {$sent} email inviate" . ($failed > 0 ? ", {$failed} ancora fallite" : '') . '.');
        Response::redirect(url("dashboard/communications/{$id}"));
    }

    public function sendNow(Request $request): void
    {
        if ($this->gate()) return;

        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $model = new EmailCampaign();
        $campaign = $model->findById($id);

        if (!$campaign || (int)$campaign['tenant_id'] !== $tenantId) {
            flash('danger', 'Comunicazione non trovata.');
            Response::redirect(url('dashboard/communications'));
            return;
        }

        if ($campaign['status'] !== 'queued') {
            flash('danger', 'Solo le campagne in coda possono essere inviate.');
            Response::redirect(url("dashboard/communications/{$id}"));
            return;
        }

        // Extend timeout for sending
        set_time_limit(300);

        $tenant = TenantResolver::current();
        $db = Database::getInstance();

        // Mark as sending
        $model->updateStatus($id, 'sending');

        $mailer = BroadcastService::createBroadcastMailer();
        $sent = 0;
        $failed = 0;

        while (true) {
            $recipients = $model->getPendingRecipients($id, 50);
            if (empty($recipients)) break;

            foreach ($recipients as $recipient) {
                $token = BroadcastService::generateUnsubscribeToken($tenantId, $recipient['email']);
                $unsubscribeUrl = rtrim(env('APP_URL', ''), '/') . '/email/unsubscribe/' . $token;
                $html = BroadcastService::buildEmailHtml($campaign['body_text'], $tenant, $unsubscribeUrl);

                $ok = BroadcastService::sendOne(
                    $mailer,
                    $recipient['email'],
                    $campaign['subject'],
                    $html,
                    $tenant['name'],
                    $tenant['email'] ?? null
                );

                if ($ok) {
                    $model->updateRecipientStatus((int)$recipient['id'], 'sent');
                    $sent++;
                } else {
                    $model->updateRecipientStatus((int)$recipient['id'], 'failed');
                    $failed++;
                }

                usleep(100000); // 100ms rate limit
            }
        }

        $model->updateCounts($id, $sent, $failed);
        $db->prepare('UPDATE email_campaigns SET status = :status, sent_at = NOW() WHERE id = :id')
           ->execute(['status' => 'sent', 'id' => $id]);

        AuditLog::log(
            AuditLog::EMAIL_BROADCAST_CREATED,
            "Campagna \"{$campaign['subject']}\" inviata manualmente — {$sent} inviate, {$failed} fallite",
            Auth::id(),
            $tenantId
        );

        flash('success', "Comunicazione inviata! {$sent} email inviate" . ($failed > 0 ? ", {$failed} fallite" : '') . '.');
        Response::redirect(url("dashboard/communications/{$id}"));
    }
}
