<?php
$giorniIt = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio', 5 => 'Ven', 6 => 'Sab', 7 => 'Dom'];
?>
<div class="av-page">

    <div class="page-back">
        <a href="<?= url('dashboard/reservations') ?>"><i class="bi bi-arrow-left"></i> Torna alle prenotazioni</a>
    </div>

    <div class="av-head">
        <h1>Disponibilità online</h1>
        <p class="av-sub">Ferma le prenotazioni <strong>online</strong> per un giorno o un servizio quando sei pieno. Il locale resta aperto: le prenotazioni già ricevute non vengono toccate e puoi sempre aggiungerne a mano.</p>
    </div>

    <!-- Form: chiudi una data / intervallo -->
    <div class="av-card">
        <div class="av-card-h"><i class="bi bi-slash-circle"></i> Chiudi le prenotazioni online</div>
        <form method="POST" action="<?= url('dashboard/reservations/availability/close') ?>" class="av-form">
            <?= csrf_field() ?>
            <div class="av-form-row">
                <label class="av-fg">
                    <span class="av-lbl">Data</span>
                    <input type="date" name="date_from" class="av-inp" required min="<?= e($today) ?>" value="<?= e($today) ?>">
                </label>
                <label class="av-fg">
                    <span class="av-lbl">Al <span class="av-opt">(facoltativo, per un intervallo)</span></span>
                    <input type="date" name="date_to" class="av-inp" min="<?= e($today) ?>">
                </label>
                <label class="av-fg">
                    <span class="av-lbl">Ambito</span>
                    <select name="scope" class="av-inp">
                        <option value="day">Tutto il giorno</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['name']) ?>">Solo <?= e($cat['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="av-btn-close"><i class="bi bi-check-lg"></i> Chiudi le prenotazioni</button>
            </div>
            <p class="av-hint">
                <i class="bi bi-info-circle"></i>
                Lascia vuoto “Al” per un solo giorno. Le fasce dell'ambito valgono per gli orari che esistono davvero nella data scelta.
            </p>
        </form>
    </div>

    <!-- Elenco date con online chiuso -->
    <div class="av-card">
        <div class="av-card-h"><i class="bi bi-calendar-x"></i> Date con prenotazioni online chiuse</div>
        <?php if (empty($closedDates)): ?>
        <div class="av-empty">
            <i class="bi bi-calendar3"></i>
            <div class="av-empty-t">Nessuna data con prenotazioni online chiuse</div>
            <div class="av-empty-s">Quando sei al completo, chiudi una data qui sopra.</div>
        </div>
        <?php else: ?>
        <div class="av-sub-count"><?= count($closedDates) ?> <?= count($closedDates) === 1 ? 'data' : 'date' ?> · dalla più vicina</div>
        <?php foreach ($closedDates as $cd): ?>
            <?php $wd = $giorniIt[(int)date('N', strtotime($cd['date']))] ?? ''; ?>
        <div class="av-row">
            <div class="av-date"><?= $wd ?> <?= format_date($cd['date'], 'd M') ?><small><?= format_date($cd['date'], 'Y') ?></small></div>
            <span class="av-pill"><?= e($cd['label']) ?></span>
            <span class="av-meta"><?= (int)$cd['slots'] ?> orari<?= $cd['whole'] ? '' : ' bloccati' ?></span>
            <span class="av-spacer"></span>
            <form method="POST" action="<?= url('dashboard/reservations/availability/reopen') ?>" class="av-reopen-form">
                <?= csrf_field() ?>
                <input type="hidden" name="date" value="<?= e($cd['date']) ?>">
                <button type="submit" class="av-reopen"><i class="bi bi-arrow-counterclockwise"></i> Riapri</button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Distinzione dalla chiusura straordinaria -->
    <div class="av-note-diff">
        <span class="av-note-ic"><i class="bi bi-exclamation-octagon"></i></span>
        <div>
            <strong>Non è una chiusura.</strong> Qui il locale resta <strong>aperto</strong> (il cliente vede “tutto prenotato”). Per un imprevisto che chiude davvero — guasto, ferie, emergenza — usa
            <a href="<?= url('dashboard/emergency-closure') ?>">Chiusura straordinaria</a> (il cliente vede “chiuso”).
        </div>
    </div>

</div>
