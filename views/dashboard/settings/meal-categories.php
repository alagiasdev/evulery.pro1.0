<h2 class="mb-4">Categorie Pasto</h2>
<p class="text-muted">Configura le fasce orarie raggruppate nel widget di prenotazione (es. Pranzo, Cena).</p>

<form method="POST" action="<?= url('dashboard/settings/meal-categories') ?>">
    <?= csrf_field() ?>
    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Chiave</th>
                        <th>Nome Visualizzato</th>
                        <th>Ora Inizio</th>
                        <th>Ora Fine</th>
                        <th style="width:80px">Ordine</th>
                        <th style="width:70px" class="text-center">Attivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $i => $cat): ?>
                    <tr>
                        <td>
                            <input type="hidden" name="categories[<?= $i ?>][name]" value="<?= e($cat['name']) ?>">
                            <code><?= e($cat['name']) ?></code>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                   name="categories[<?= $i ?>][display_name]"
                                   value="<?= e($cat['display_name']) ?>" required>
                        </td>
                        <td>
                            <input type="time" class="form-control form-control-sm"
                                   name="categories[<?= $i ?>][start_time]"
                                   value="<?= e(substr($cat['start_time'], 0, 5)) ?>" required>
                        </td>
                        <td>
                            <input type="time" class="form-control form-control-sm"
                                   name="categories[<?= $i ?>][end_time]"
                                   value="<?= e(substr($cat['end_time'], 0, 5)) ?>" required>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-center"
                                   name="categories[<?= $i ?>][sort_order]"
                                   value="<?= (int)$cat['sort_order'] ?>"
                                   min="0" max="20">
                        </td>
                        <td class="text-center align-middle">
                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                <input class="form-check-input" type="checkbox"
                                       name="categories[<?= $i ?>][is_active]"
                                       <?= $cat['is_active'] ? 'checked' : '' ?>>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i> Salva Categorie
    </button>
</form>
