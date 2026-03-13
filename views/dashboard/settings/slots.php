<h2 class="mb-4">Orari e Coperti</h2>

<form method="POST" action="<?= url('dashboard/settings/slots') ?>">
    <?= csrf_field() ?>

    <div class="card mb-4">
        <div class="card-body">
            <p class="text-muted mb-3">
                Imposta i coperti massimi per ogni fascia oraria. Lascia vuoto o 0 per chiudere quella fascia.
                Step attuale: <strong><?= (int)$tenant['time_step'] ?> min</strong> | Durata tavolo: <strong><?= (int)$tenant['table_duration'] ?> min</strong>
            </p>

            <?php
            $timeStep = (int)$tenant['time_step'];
            // $startHour is passed from controller (dynamic, based on meal categories)
            $endHour = 23;
            $times = [];
            for ($h = $startHour; $h <= $endHour; $h++) {
                for ($m = 0; $m < 60; $m += $timeStep) {
                    $times[] = sprintf('%02d:%02d', $h, $m);
                }
            }

            // Build inactive category lookup: for each time, check if it falls in an inactive category
            $inactiveRanges = [];
            foreach ($allCategories as $cat) {
                if (!$cat['is_active']) {
                    $inactiveRanges[] = [
                        'name'  => $cat['display_name'],
                        'start' => (int)substr($cat['start_time'], 0, 2) * 60 + (int)substr($cat['start_time'], 3, 2),
                        'end'   => (int)substr($cat['end_time'], 0, 2) * 60 + (int)substr($cat['end_time'], 3, 2),
                    ];
                }
            }
            function getInactiveCategory(string $time, array $ranges): ?string {
                $parts = explode(':', $time);
                $mins = (int)$parts[0] * 60 + (int)$parts[1];
                foreach ($ranges as $r) {
                    if ($mins >= $r['start'] && $mins < $r['end']) {
                        return $r['name'];
                    }
                }
                return null;
            }

            // Detect phantom slots in DB (from old time_step) not in current grid
            $timesSet = array_flip($times);
            $phantomTimes = [];
            foreach ($slotsByDay as $daySlots) {
                foreach ($daySlots as $s) {
                    $t = substr($s['slot_time'], 0, 5);
                    if (!isset($timesSet[$t]) && !isset($phantomTimes[$t])) {
                        $phantomTimes[$t] = true;
                    }
                }
            }
            if (!empty($phantomTimes)) {
                $times = array_unique(array_merge($times, array_keys($phantomTimes)));
                sort($times);
            }
            ?>

            <?php if (!empty($phantomTimes)): ?>
            <div class="alert alert-warning mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Trovati <strong><?= count($phantomTimes) ?></strong> orari non standard (da un vecchio step).
                Sono evidenziati in giallo e disabilitati. Clicca <strong>Salva</strong> per rimuoverli e allineare tutto allo step attuale di <?= $timeStep ?> min.
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Orario</th>
                            <?php foreach ($dayNames as $i => $name): ?>
                            <th class="text-center"><?= e($name) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($times as $time): ?>
                        <?php
                            $isPhantom = isset($phantomTimes[$time]);
                            $inactiveCat = getInactiveCategory($time, $inactiveRanges);
                            $isInactive = ($inactiveCat !== null);
                            $rowClass = '';
                            if ($isPhantom) $rowClass = 'table-warning';
                            elseif ($isInactive) $rowClass = 'slot-inactive';
                        ?>
                        <tr<?= $rowClass ? ' class="' . $rowClass . '"' : '' ?>>
                            <td class="fw-semibold<?= $isInactive ? ' text-muted' : '' ?>">
                                <?= $time ?>
                                <?php if ($isPhantom): ?>
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                <?php elseif ($isInactive): ?>
                                    <small class="text-muted fw-normal ms-1"><?= e($inactiveCat) ?></small>
                                <?php endif; ?>
                            </td>
                            <?php for ($day = 0; $day < 7; $day++): ?>
                            <?php
                                $currentVal = 0;
                                if (isset($slotsByDay[$day])) {
                                    foreach ($slotsByDay[$day] as $s) {
                                        if (substr($s['slot_time'], 0, 5) === $time && $s['is_active']) {
                                            $currentVal = (int)$s['max_covers'];
                                            break;
                                        }
                                    }
                                }
                                $isDisabled = $isPhantom || $isInactive;
                            ?>
                            <td class="text-center">
                                <input type="number" class="form-control form-control-sm text-center"
                                       <?= $isDisabled ? '' : 'name="slots[' . $day . '][' . $time . ']"' ?>
                                       value="<?= $isInactive ? 0 : $currentVal ?>"
                                       min="0" max="200" style="width: 70px; margin: 0 auto;"
                                       <?php if ($isPhantom): ?>disabled title="Verrà rimosso al salvataggio"
                                       <?php elseif ($isInactive): ?>readonly tabindex="-1" title="Categoria &quot;<?= e($inactiveCat) ?>&quot; disattivata"
                                       <?php endif; ?>>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i> Salva Configurazione
    </button>
</form>
