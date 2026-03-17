<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

class ProfileController
{
    public function show(Request $request): void
    {
        $user = (new User())->findById(Auth::id());

        $layout = Auth::isSuperAdmin() ? 'admin' : 'dashboard';
        $view = Auth::isSuperAdmin() ? 'admin/profile' : 'dashboard/profile';

        view($view, [
            'title'      => 'Profilo',
            'activeMenu' => 'profile',
            'user'       => $user,
        ], $layout);
    }

    public function update(Request $request): void
    {
        $userId = Auth::id();
        $data = $request->all();
        $redirectUrl = Auth::isSuperAdmin() ? url('admin/profile') : url('dashboard/profile');

        $v = Validator::make($data)
            ->required('first_name', 'Nome')
            ->required('last_name', 'Cognome')
            ->required('email', 'Email')
            ->email('email', 'Email');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect($redirectUrl);
        }

        // Check email uniqueness (exclude current user)
        $existing = (new User())->findByEmail($data['email']);
        if ($existing && (int)$existing['id'] !== $userId) {
            flash('danger', 'Questa email è già utilizzata da un altro account.');
            Response::redirect($redirectUrl);
        }

        $updateData = [
            'first_name' => trim($data['first_name']),
            'last_name'  => trim($data['last_name']),
            'email'      => trim($data['email']),
        ];

        // Password change (optional)
        if (!empty($data['new_password'])) {
            if (mb_strlen($data['new_password']) < 8) {
                flash('danger', 'La nuova password deve avere almeno 8 caratteri.');
                Response::redirect($redirectUrl);
            }
            if (!preg_match('/[A-Z]/', $data['new_password']) || !preg_match('/[0-9]/', $data['new_password'])) {
                flash('danger', 'La password deve contenere almeno una maiuscola e un numero.');
                Response::redirect($redirectUrl);
            }
            if ($data['new_password'] !== ($data['confirm_password'] ?? '')) {
                flash('danger', 'Le password non coincidono.');
                Response::redirect($redirectUrl);
            }

            // Verify current password
            $user = (new User())->findById($userId);
            if (!password_verify($data['current_password'] ?? '', $user['password_hash'])) {
                flash('danger', 'La password attuale non è corretta.');
                Response::redirect($redirectUrl);
            }

            $updateData['password'] = $data['new_password'];
        }

        (new User())->update($userId, $updateData);

        // Refresh session data
        Session::set('user_name', $updateData['first_name'] . ' ' . $updateData['last_name']);
        Session::set('user_email', $updateData['email']);

        flash('success', 'Profilo aggiornato.');
        Response::redirect($redirectUrl);
    }
}