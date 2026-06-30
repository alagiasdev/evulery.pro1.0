<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\User;
use App\Services\AuditLog;

/**
 * Gestione collaboratori (utenti 'staff') del ristoratore.
 * Sezione owner-only, gated dal servizio `staff_accounts` (Enterprise).
 *
 * Sicurezza: gli utenti staff NON raggiungono questa pagina (la StaffMiddleware
 * blocca tutto `/settings`). In creazione, `tenant_id` e `role` sono FORZATI
 * lato server — mai presi dal form — per evitare escalation.
 */
class CollaboratorsController
{
    private const PASSWORD_HINT = 'Minimo 8 caratteri, una maiuscola e un numero.';

    public function index(Request $request): void
    {
        if (gate_service('staff_accounts', url('dashboard/settings'))) return;

        $tenant   = TenantResolver::current();
        $tenantId = (int)Auth::tenantId();
        $staff    = $this->staffOf($tenantId);
        $limit    = tenant_staff_limit($tenant);

        view('dashboard/settings/collaborators', [
            'title'      => 'Collaboratori',
            'activeMenu' => 'collaborators',
            'tenant'     => $tenant,
            'staff'      => $staff,
            'limit'      => $limit,
            'canAddMore' => count($staff) < $limit,
            'passwordHint' => self::PASSWORD_HINT,
        ], 'dashboard');
    }

    public function store(Request $request): void
    {
        if (gate_service('staff_accounts', url('dashboard/settings'))) return;

        $tenant   = TenantResolver::current();
        $tenantId = (int)Auth::tenantId();
        $userModel = new User();
        $back = url('dashboard/settings/collaborators');

        $firstName = trim($request->input('first_name', ''));
        $lastName  = trim($request->input('last_name', ''));
        $email     = strtolower(trim($request->input('email', '')));
        $password  = (string)$request->input('password', '');

        // Su errore ripopola i campi (tranne password) invece di svuotare il form.
        $fail = function (string $msg) use ($firstName, $lastName, $email, $back) {
            \App\Core\Session::flash('old_input', ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email]);
            flash('danger', $msg);
            Response::redirect($back);
        };

        // Limite per piano (override max_staff)
        $limit = tenant_staff_limit($tenant);
        if (count($this->staffOf($tenantId)) >= $limit) {
            $fail("Hai raggiunto il limite di {$limit} collaboratori. Contatta il supporto per aumentarlo.");
        }
        if (!$firstName || !$lastName || !$email || !$password) {
            $fail('Compila tutti i campi.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fail('Indirizzo email non valido.');
        }
        // Policy password (coerente col cambio password del profilo)
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $fail('Password troppo debole. ' . self::PASSWORD_HINT);
        }
        // Email univoca globale (il login è per email)
        if ($userModel->findByEmail($email)) {
            $fail('Questa email è già in uso. Chi lavora in più ristoranti deve usare email diverse.');
        }

        // GUARDIE: tenant e ruolo forzati lato server, mai dal form.
        $userModel->create([
            'tenant_id'  => $tenantId,
            'email'      => $email,
            'password'   => $password,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'role'       => 'staff',
            'is_active'  => 1,
        ]);

        AuditLog::log(AuditLog::USER_CREATED, "Collaboratore staff creato: {$email}", Auth::id(), $tenantId);
        flash('success', "Collaboratore \"{$firstName} {$lastName}\" creato.");
        Response::redirect($back);
    }

    public function toggle(Request $request): void
    {
        if (gate_service('staff_accounts', url('dashboard/settings'))) return;
        $target = $this->findOwnStaff((int)$request->param('id'));
        if (!$target) return;

        $newActive = empty($target['is_active']) ? 1 : 0;
        (new User())->update((int)$target['id'], ['is_active' => $newActive], true);
        flash('success', $newActive ? 'Collaboratore riattivato.' : 'Collaboratore disattivato.');
        Response::redirect(url('dashboard/settings/collaborators'));
    }

    public function destroy(Request $request): void
    {
        if (gate_service('staff_accounts', url('dashboard/settings'))) return;
        $target = $this->findOwnStaff((int)$request->param('id'));
        if (!$target) return;

        (new User())->deleteStaff((int)$target['id'], (int)Auth::tenantId());
        AuditLog::log(AuditLog::USER_UPDATED, "Collaboratore staff rimosso: {$target['email']}", Auth::id(), (int)Auth::tenantId());
        flash('success', 'Collaboratore rimosso.');
        Response::redirect(url('dashboard/settings/collaborators'));
    }

    public function resetPassword(Request $request): void
    {
        if (gate_service('staff_accounts', url('dashboard/settings'))) return;
        $target = $this->findOwnStaff((int)$request->param('id'));
        if (!$target) return;

        $back = url('dashboard/settings/collaborators');
        $password = (string)$request->input('password', '');
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            flash('danger', 'Password troppo debole. ' . self::PASSWORD_HINT);
            Response::redirect($back);
            return;
        }
        (new User())->update((int)$target['id'], ['password' => $password]);
        AuditLog::log(AuditLog::USER_UPDATED, "Password collaboratore aggiornata: {$target['email']}", Auth::id(), (int)Auth::tenantId());
        flash('success', "Password aggiornata per {$target['first_name']} {$target['last_name']}.");
        Response::redirect($back);
    }

    /** Solo i collaboratori 'staff' del tenant corrente. */
    private function staffOf(int $tenantId): array
    {
        $users = (new User())->findByTenant($tenantId);
        return array_values(array_filter($users, fn($u) => ($u['role'] ?? '') === 'staff'));
    }

    /**
     * Carica uno staff verificando che sia del tenant corrente; altrimenti
     * flash + redirect e ritorna null (il chiamante fa `return`).
     */
    private function findOwnStaff(int $id): ?array
    {
        $target = (new User())->findById($id);
        $tenantId = (int)Auth::tenantId();
        if (!$target || (int)$target['tenant_id'] !== $tenantId || ($target['role'] ?? '') !== 'staff') {
            flash('danger', 'Collaboratore non trovato.');
            Response::redirect(url('dashboard/settings/collaborators'));
            return null;
        }
        return $target;
    }
}
