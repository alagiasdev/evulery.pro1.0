<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLog;
use App\Services\Migrator;

/**
 * Pagina Admin → Migration: visualizza stato + applica pending via 1 click.
 * Alternativa al cron-job una-tantum CLI per applicare nuove migration
 * direttamente dal browser dopo "Update from Remote" su cPanel.
 *
 * Sicurezza: protetto dal middleware admin (vedi config/routes.php).
 * CSRF: richiesto sul POST. Lock GET_LOCK lato Migrator previene chiamate concorrenti.
 */
class MigrationsController
{
    public function index(Request $request): void
    {
        $status = (new Migrator())->status();

        view('admin/migrations', [
            'title'      => 'Migration Database',
            'activeMenu' => 'migrations',
            'status'     => $status,
            'lastResult' => \App\Core\Session::flash('migration_result'),
        ]);
    }

    public function run(Request $request): void
    {
        $migrator = new Migrator();
        $result = $migrator->applyPending();

        if ($result['success']) {
            $count = count($result['applied']);
            if ($count === 0) {
                flash('info', 'Nessuna migration da applicare. Tutto allineato.');
            } else {
                $files = array_column($result['applied'], 'filename');
                flash('success', "Applicate {$count} migration: " . implode(', ', $files));
                AuditLog::log(
                    AuditLog::SETTINGS_UPDATED,
                    "Applicate {$count} migration via web: " . implode(', ', $files),
                    Auth::id(),
                    null
                );
            }
            \App\Core\Session::flash('migration_result', [
                'success' => true,
                'applied' => $result['applied'],
            ]);
        } else {
            $fileMsg = $result['error_file'] ? " (file: {$result['error_file']})" : '';
            flash('danger', "Errore migration{$fileMsg}: " . $result['error']);
            \App\Core\Session::flash('migration_result', [
                'success'    => false,
                'error'      => $result['error'],
                'error_file' => $result['error_file'],
                'applied'    => $result['applied'],
            ]);
            AuditLog::log(
                AuditLog::SETTINGS_UPDATED,
                "Errore migration via web: " . $result['error'],
                Auth::id(),
                null
            );
        }

        Response::redirect(url('admin/migrations'));
    }
}
