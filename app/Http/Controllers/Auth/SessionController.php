<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();
        $request->session()->regenerate();
        $user->forceFill(['last_active_at' => now()])->saveQuietly();

        if (! $user->isVerifiedAccount()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended($user->isStaff() ? route('admin.dashboard') : route('dashboard'));
    }

    public function adminCreate(): View
    {
        return view('auth.admin-login');
    }

    /** Same credentials check, but only staff accounts may continue. */
    public function adminStore(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();
        if (! $user->isStaff()) {
            Auth::logout();
            throw ValidationException::withMessages(['login' => 'This account does not have administrator or reviewer access.']);
        }
        $request->session()->regenerate();
        $user->forceFill(['last_active_at' => now()])->saveQuietly();
        AuditLogger::log('admin.login', "{$user->role->label()} signed in");

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }
}
