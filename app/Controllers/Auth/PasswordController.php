<?php

namespace App\Controllers\Auth;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class PasswordController
{
    public function showForgot(Request $request): void
    {
        view('auth/forgot-password', ['title' => 'Recupera Password'], 'auth');
    }

    public function sendReset(Request $request): void
    {
        $email = trim($request->input('email', ''));
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Always show success to prevent email enumeration
        flash('success', 'Se l\'email è registrata, riceverai un link di reset.');

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $db->prepare(
                'INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)'
            );
            $stmt->execute([
                'user_id'    => $user['id'],
                'token'      => $token,
                'expires_at' => $expiresAt,
            ]);

            // TODO: send email with reset link via url("auth/reset-password/{$token}")
            app_log("Password reset requested for user {$user['id']}");
        }

        Response::redirect(url('auth/forgot-password'));
    }

    public function showReset(Request $request): void
    {
        $token = $request->param('token');
        $db = Database::getInstance();

        $stmt = $db->prepare(
            'SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            flash('danger', 'Link di reset non valido o scaduto.');
            Response::redirect(url('auth/forgot-password'));
        }

        view('auth/reset-password', ['title' => 'Reimposta Password', 'token' => $token], 'auth');
    }

    public function doReset(Request $request): void
    {
        $token = $request->input('token', '');
        $password = $request->input('password', '');
        $confirmation = $request->input('password_confirmation', '');

        if (strlen($password) < 8) {
            flash('danger', 'La password deve avere almeno 8 caratteri.');
            Response::redirect(url('auth/reset-password/' . $token));
        }

        if ($password !== $confirmation) {
            flash('danger', 'Le password non coincidono.');
            Response::redirect(url('auth/reset-password/' . $token));
        }

        $db = Database::getInstance();

        $stmt = $db->prepare(
            'SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            flash('danger', 'Link di reset non valido o scaduto.');
            Response::redirect(url('auth/forgot-password'));
        }

        // Update password
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $reset['user_id']]);

        // Mark token as used
        $stmt = $db->prepare('UPDATE password_resets SET used = 1 WHERE id = :id');
        $stmt->execute(['id' => $reset['id']]);

        flash('success', 'Password reimpostata con successo. Puoi accedere.');
        Response::redirect(url('auth/login'));
    }
}
