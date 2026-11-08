<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Authentication screens. Part 2 replaces the form handlers with real Laravel auth. */
class AuthPageController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function resetPassword(string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function verifyAccount(): View
    {
        return view('auth.verify-account');
    }

    public function adminLogin(): View
    {
        return view('auth.admin-login');
    }
}
