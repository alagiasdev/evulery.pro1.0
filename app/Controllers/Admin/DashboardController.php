<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;

class DashboardController
{
    public function index(Request $request): void
    {
        $db = Database::getInstance();

        // Count tenants
        $totalTenants = $db->query('SELECT COUNT(*) as c FROM tenants')->fetch()['c'];
        $activeTenants = $db->query('SELECT COUNT(*) as c FROM tenants WHERE is_active = 1')->fetch()['c'];

        // Count reservations today
        $stmt = $db->prepare('SELECT COUNT(*) as c FROM reservations WHERE reservation_date = CURDATE()');
        $stmt->execute();
        $todayReservations = $stmt->fetch()['c'];

        // Count total users
        $totalUsers = $db->query('SELECT COUNT(*) as c FROM users WHERE role != "super_admin"')->fetch()['c'];

        // Recent tenants (last 5)
        $recentTenants = $db->query('SELECT id, name, slug, plan, is_active, created_at FROM tenants ORDER BY created_at DESC LIMIT 5')->fetchAll();

        view('admin/dashboard', [
            'title'             => 'Admin Dashboard',
            'activeMenu'        => 'admin-home',
            'totalTenants'      => $totalTenants,
            'activeTenants'     => $activeTenants,
            'todayReservations' => $todayReservations,
            'totalUsers'        => $totalUsers,
            'recentTenants'     => $recentTenants,
        ], 'admin');
    }
}
