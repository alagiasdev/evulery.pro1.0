<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\MealCategory;
use App\Models\TimeSlot;
use App\Services\AuditLog;

class SlotsController
{
    private array $dayNames = ['Lunedi', 'Martedi', 'Mercoledi', 'Giovedi', 'Venerdi', 'Sabato', 'Domenica'];

    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $slots = (new TimeSlot())->findAllByTenant($tenantId);
        $tenant = TenantResolver::current();

        // Determine start hour: minimum of 9 (reasonable floor), active categories, and existing DB slots
        $startHour = 9;
        $categories = (new MealCategory())->findActiveByTenant($tenantId);
        if (!empty($categories)) {
            $earliest = min(array_map(fn($c) => (int)substr($c['start_time'], 0, 2), $categories));
            $startHour = min($startHour, $earliest);
        }
        // Also consider existing slots in DB (never truncate data already saved)
        if (!empty($slots)) {
            $earliestSlot = (int)substr($slots[0]['slot_time'], 0, 2); // slots ordered by slot_time
            $startHour = min($startHour, $earliestSlot);
        }

        // Organize by day
        $slotsByDay = [];
        foreach ($slots as $slot) {
            $slotsByDay[$slot['day_of_week']][] = $slot;
        }

        // Load all categories (active + inactive) for visual hints in the grid
        $allCategories = (new MealCategory())->findAllByTenant($tenantId);

        view('dashboard/settings/slots', [
            'title'         => 'Orari e Coperti',
            'activeMenu'    => 'slots',
            'slotsByDay'    => $slotsByDay,
            'dayNames'      => $this->dayNames,
            'tenant'        => $tenant,
            'startHour'     => $startHour,
            'allCategories' => $allCategories,
        ], 'dashboard');
    }

    public function update(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();
        $timeSlotModel = new TimeSlot();
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // Clear ALL existing slots (removes phantom records from old time_step too)
            $timeSlotModel->deleteByTenant($tenantId);

            // Re-insert only slots with covers > 0
            if (isset($data['slots']) && is_array($data['slots'])) {
                foreach ($data['slots'] as $day => $times) {
                    foreach ($times as $time => $covers) {
                        $covers = (int)$covers;
                        if ($covers > 0) {
                            $timeSlotModel->upsert($tenantId, (int)$day, $time, $covers);
                        }
                    }
                }
            }

            $db->commit();
            AuditLog::log(AuditLog::SLOTS_UPDATED, null, Auth::id(), $tenantId);
            flash('success', 'Orari e coperti aggiornati.');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('danger', 'Errore nel salvataggio degli orari. Riprova.');
        }

        Response::redirect(url('dashboard/settings/slots'));
    }
}
