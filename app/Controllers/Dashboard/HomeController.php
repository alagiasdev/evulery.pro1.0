<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\TenantResolver;
use App\Models\Reservation;

class HomeController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $date = $request->query('date', date('Y-m-d'));

        $reservationModel = new Reservation();
        $stats = $this->getStatsForDate($tenantId, $date);
        $reservations = $reservationModel->findByTenantAndDate($tenantId, $date);

        // Get upcoming reservations count (next 7 days)
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT reservation_date, COUNT(*) as count, SUM(party_size) as covers
             FROM reservations
             WHERE tenant_id = :tenant_id
             AND reservation_date >= CURDATE()
             AND reservation_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             AND status IN ("confirmed", "pending")
             GROUP BY reservation_date
             ORDER BY reservation_date ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $upcoming = $stmt->fetchAll();

        view('dashboard/home', [
            'title'        => 'Dashboard',
            'activeMenu'   => 'home',
            'stats'        => $stats,
            'reservations' => $reservations,
            'date'         => $date,
            'upcoming'     => $upcoming,
        ], 'dashboard');
    }

    private function getStatsForDate(int $tenantId, string $date): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT COUNT(*) as total,
                    COALESCE(SUM(party_size), 0) as covers,
                    SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "arrived" THEN 1 ELSE 0 END) as arrived,
                    SUM(CASE WHEN status = "noshow" THEN 1 ELSE 0 END) as noshow,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
             FROM reservations
             WHERE tenant_id = :tenant_id AND reservation_date = :date'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date]);
        return $stmt->fetch();
    }
}
