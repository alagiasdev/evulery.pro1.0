<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLog;

class ActivityLogController
{
    public function index(Request $request): void
    {
        $db = Database::getInstance();
        $perPage = 50;
        $page = max(1, (int)$request->query('page', 1));

        // Filters
        $event    = $request->query('event', '');
        $tenantId = $request->query('tenant', '');
        $from     = $request->query('from', '');
        $to       = $request->query('to', '');

        // Build WHERE
        $where  = [];
        $params = [];

        if ($event !== '') {
            $where[]         = 'a.event = :event';
            $params['event'] = $event;
        }
        if ($tenantId !== '') {
            $where[]            = 'a.tenant_id = :tenant_id';
            $params['tenant_id'] = (int)$tenantId;
        }
        if ($from !== '') {
            $where[]        = 'a.created_at >= :from_date';
            $params['from_date'] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[]      = 'a.created_at <= :to_date';
            $params['to_date'] = $to . ' 23:59:59';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM audit_logs a {$whereSql}");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Build base URL preserving filters
        $baseParams = [];
        if ($event !== '')    $baseParams[] = 'event=' . urlencode($event);
        if ($tenantId !== '') $baseParams[] = 'tenant=' . urlencode($tenantId);
        if ($from !== '')     $baseParams[] = 'from=' . urlencode($from);
        if ($to !== '')       $baseParams[] = 'to=' . urlencode($to);
        $baseUrl = url('admin/activity-log') . ($baseParams ? '?' . implode('&', $baseParams) : '');

        $paginator = new Paginator($total, $perPage, $page, $baseUrl);

        // Fetch logs
        $stmt = $db->prepare(
            "SELECT a.*, u.first_name, u.last_name, u.email as user_email,
                    t.name as tenant_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN tenants t ON t.id = a.tenant_id
             {$whereSql}
             ORDER BY a.created_at DESC
             LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $paginator->limit(), \PDO::PARAM_INT);
        $stmt->bindValue('off', $paginator->offset(), \PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        // KPI
        $eventsToday = (int)$db->query(
            "SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn();

        $loginsToday = (int)$db->query(
            "SELECT COUNT(*) FROM audit_logs WHERE event = 'login_success' AND DATE(created_at) = CURDATE()"
        )->fetchColumn();

        $events7d = (int)$db->query(
            "SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )->fetchColumn();

        // Unique events for filter dropdown
        $eventList = $db->query(
            "SELECT DISTINCT event FROM audit_logs ORDER BY event"
        )->fetchAll(\PDO::FETCH_COLUMN);

        // Tenants for filter dropdown
        $tenants = $db->query(
            "SELECT id, name FROM tenants ORDER BY name"
        )->fetchAll();

        // Total logs count (for purge info)
        $totalLogs = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

        view('admin/activity-log/index', [
            'title'       => 'Log Attività',
            'activeMenu'  => 'activity-log',
            'logs'        => $logs,
            'pagination'  => $paginator->links(),
            'eventsToday' => $eventsToday,
            'loginsToday' => $loginsToday,
            'events7d'    => $events7d,
            'eventList'   => $eventList,
            'tenants'     => $tenants,
            'totalLogs'   => $totalLogs,
            'filter'      => [
                'event'  => $event,
                'tenant' => $tenantId,
                'from'   => $from,
                'to'     => $to,
            ],
        ], 'admin');
    }

    public function purge(Request $request): void
    {
        $months = max(1, min(24, (int)$request->input('months', 6)));

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :months MONTH)"
        );
        $stmt->bindValue('months', $months, \PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount();

        AuditLog::log('log_purged', "Eliminati {$deleted} log più vecchi di {$months} mesi", Auth::id());

        flash('success', "Eliminati {$deleted} eventi più vecchi di {$months} mesi.");
        Response::redirect(url('admin/activity-log'));
    }
}
