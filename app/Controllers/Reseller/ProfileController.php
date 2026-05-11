<?php

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ResellerProfile;
use App\Models\User;
use App\Services\AuditLog;

/**
 * Profilo reseller: dati account + commissioni concordate (readonly).
 * Solo l'admin può modificare le commissioni.
 */
class ProfileController
{
    public function show(Request $request): void
    {
        $userId = Auth::id();
        $user = (new User())->findById($userId);
        $profile = (new ResellerProfile())->findByUserId($userId);

        view('reseller/profile', [
            'title'      => 'Profilo',
            'activeMenu' => 'reseller-profile',
            'user'       => $user,
            'profile'    => $profile,
        ], 'reseller');
    }

    public function update(Request $request): void
    {
        $userId = Auth::id();
        $data = $request->all();
        $redirectUrl = url('reseller/profile');

        $v = Validator::make($data)
            ->required('first_name', 'Nome')
            ->required('last_name', 'Cognome')
            ->required('email', 'Email')
            ->email('email', 'Email');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect($redirectUrl);
            return;
        }

        $existing = (new User())->findByEmail($data['email']);
        if ($existing && (int)$existing['id'] !== $userId) {
            flash('danger', 'Questa email è già utilizzata da un altro account.');
            Response::redirect($redirectUrl);
            return;
        }

        $updateData = [
            'first_name' => trim($data['first_name']),
            'last_name'  => trim($data['last_name']),
            'email'      => trim($data['email']),
        ];

        if (!empty($data['new_password'])) {
            if (mb_strlen($data['new_password']) < 8) {
                flash('danger', 'La nuova password deve avere almeno 8 caratteri.');
                Response::redirect($redirectUrl);
                return;
            }
            if (!preg_match('/[A-Z]/', $data['new_password']) || !preg_match('/[0-9]/', $data['new_password'])) {
                flash('danger', 'La password deve contenere almeno una maiuscola e un numero.');
                Response::redirect($redirectUrl);
                return;
            }
            if ($data['new_password'] !== ($data['confirm_password'] ?? '')) {
                flash('danger', 'Le password non coincidono.');
                Response::redirect($redirectUrl);
                return;
            }

            $user = (new User())->findById($userId);
            if (!password_verify($data['current_password'] ?? '', $user['password_hash'])) {
                flash('danger', 'La password attuale non è corretta.');
                Response::redirect($redirectUrl);
                return;
            }

            $updateData['password'] = $data['new_password'];
        }

        (new User())->update($userId, $updateData);
        AuditLog::log(AuditLog::PROFILE_UPDATED, null, $userId);

        Session::set('user_name', $updateData['first_name'] . ' ' . $updateData['last_name']);
        Session::set('user_email', $updateData['email']);

        flash('success', 'Profilo aggiornato.');
        Response::redirect($redirectUrl);
    }
}
