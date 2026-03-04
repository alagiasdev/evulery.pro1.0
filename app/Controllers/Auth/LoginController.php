<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class LoginController
{
    public function showForm(Request $request): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirectByRole();
        }

        view('auth/login', ['title' => 'Accedi'], 'auth');
    }

    public function login(Request $request): void
    {
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');

        if (!$email || !$password) {
            flash('danger', 'Inserisci email e password.');
            Session::flash('old_input', ['email' => $email]);
            Response::redirect(url('auth/login'));
        }

        if (Auth::attempt($email, $password)) {
            $this->redirectByRole();
        }

        flash('danger', 'Email o password non validi.');
        Session::flash('old_input', ['email' => $email]);
        Response::redirect(url('auth/login'));
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        flash('success', 'Logout effettuato con successo.');
        Response::redirect(url('auth/login'));
    }

    private function redirectByRole(): void
    {
        if (Auth::isSuperAdmin()) {
            Response::redirect(url('admin'));
        }
        Response::redirect(url('dashboard'));
    }
}
