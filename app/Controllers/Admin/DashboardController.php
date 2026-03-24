<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;

class DashboardController
{
    public function index(Request $request): void
    {
        $db = Database::getInstance();

        // --- Stat cards ---
        $totalTenants = (int)$db->query('SELECT COUNT(*) as c FROM tenants')->fetch()['c'];
        $activeTenants = (int)$db->query('SELECT COUNT(*) as c FROM tenants WHERE is_active = 1')->fetch()['c'];

        // Subscription-aware counts
        $expiredSubsCount = (int)$db->query(
            "SELECT COUNT(DISTINCT s.tenant_id) FROM subscriptions s
             JOIN tenants t ON t.id = s.tenant_id AND t.is_active = 1
             WHERE s.status IN ('active','trialing') AND s.current_period_end < CURDATE()"
        )->fetchColumn();
        $reallyActiveTenants = $activeTenants - $expiredSubsCount;

        // Reservations this month
        $monthReservations = (int)$db->query(
            'SELECT COUNT(*) as c FROM reservations
             WHERE YEAR(reservation_date) = YEAR(CURDATE())
             AND MONTH(reservation_date) = MONTH(CURDATE())
             AND status IN ("confirmed","pending","arrived")'
        )->fetch()['c'];

        // Reservations last month (for trend)
        $lastMonthReservations = (int)$db->query(
            'SELECT COUNT(*) as c FROM reservations
             WHERE YEAR(reservation_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)
             AND MONTH(reservation_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)
             AND status IN ("confirmed","pending","arrived")'
        )->fetch()['c'];

        // Covers this month
        $monthCovers = (int)$db->query(
            'SELECT COALESCE(SUM(party_size), 0) as c FROM reservations
             WHERE YEAR(reservation_date) = YEAR(CURDATE())
             AND MONTH(reservation_date) = MONTH(CURDATE())
             AND status IN ("confirmed","pending","arrived")'
        )->fetch()['c'];

        // Total users (non-admin)
        $totalUsers = (int)$db->query('SELECT COUNT(*) as c FROM users WHERE role != "super_admin"')->fetch()['c'];

        // --- Reservations last 7 days (for chart) ---
        $chartData = $db->query(
            'SELECT DATE(reservation_date) as day, COUNT(*) as cnt
             FROM reservations
             WHERE reservation_date >= CURDATE() - INTERVAL 6 DAY
             AND reservation_date <= CURDATE()
             AND status IN ("confirmed","pending","arrived")
             GROUP BY DATE(reservation_date)
             ORDER BY day ASC'
        )->fetchAll();

        // Build 7-day array with zero-fill
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $last7Days[$date] = 0;
        }
        foreach ($chartData as $row) {
            if (isset($last7Days[$row['day']])) {
                $last7Days[$row['day']] = (int)$row['cnt'];
            }
        }

        // --- Top 5 restaurants by reservations this month ---
        $topTenants = $db->query(
            'SELECT t.name, COUNT(r.id) as cnt
             FROM reservations r
             JOIN tenants t ON t.id = r.tenant_id
             WHERE YEAR(r.reservation_date) = YEAR(CURDATE())
             AND MONTH(r.reservation_date) = MONTH(CURDATE())
             AND r.status IN ("confirmed","pending","arrived")
             GROUP BY r.tenant_id, t.name
             ORDER BY cnt DESC
             LIMIT 5'
        )->fetchAll();

        // --- Recent activity (from audit_logs) ---
        $recentActivity = $db->query(
            'SELECT al.event, al.description, al.created_at, al.tenant_id,
                    u.first_name, u.last_name,
                    t.name as tenant_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             LEFT JOIN tenants t ON t.id = al.tenant_id
             ORDER BY al.created_at DESC
             LIMIT 8'
        )->fetchAll();

        // --- Alerts ---
        // Inactive tenants
        $inactiveTenants = $db->query(
            'SELECT name FROM tenants WHERE is_active = 0 ORDER BY updated_at DESC LIMIT 3'
        )->fetchAll();

        // Recent tenants (last 5, for backward compat)
        $recentTenants = $db->query(
            'SELECT id, name, slug, plan, is_active, created_at
             FROM tenants ORDER BY created_at DESC LIMIT 5'
        )->fetchAll();

        // Expired subscriptions
        $expiredSubs = $db->query(
            "SELECT s.id, t.name as tenant_name, s.current_period_end
             FROM subscriptions s
             JOIN tenants t ON t.id = s.tenant_id
             WHERE s.status = 'active' AND s.current_period_end < CURDATE()
             ORDER BY s.current_period_end ASC
             LIMIT 5"
        )->fetchAll();

        // Expiring subscriptions (next 30 days)
        $expiringSubs = $db->query(
            "SELECT s.id, t.name as tenant_name, s.current_period_end
             FROM subscriptions s
             JOIN tenants t ON t.id = s.tenant_id
             WHERE s.status = 'active'
             AND s.current_period_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY s.current_period_end ASC
             LIMIT 5"
        )->fetchAll();

        // Reservation trend percentage
        $resTrend = 0;
        if ($lastMonthReservations > 0) {
            $resTrend = round((($monthReservations - $lastMonthReservations) / $lastMonthReservations) * 100);
        }

        view('admin/dashboard', [
            'title'                 => 'Admin Dashboard',
            'activeMenu'            => 'admin-home',
            'totalTenants'          => $totalTenants,
            'activeTenants'         => $reallyActiveTenants,
            'expiredSubsCount'      => $expiredSubsCount,
            'monthReservations'     => $monthReservations,
            'monthCovers'           => $monthCovers,
            'totalUsers'            => $totalUsers,
            'resTrend'              => $resTrend,
            'last7Days'             => $last7Days,
            'topTenants'            => $topTenants,
            'recentActivity'        => $recentActivity,
            'inactiveTenants'       => $inactiveTenants,
            'recentTenants'         => $recentTenants,
            'expiredSubs'           => $expiredSubs,
            'expiringSubs'          => $expiringSubs,
        ], 'admin');
    }
}
