<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\AccountVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, AccountVerificationService $verification): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = new User([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password'), // hashed by the model cast
                'location_id' => $request->location()->id,
            ]);
            // Every new account starts as a regular, unverified member. Roles are never taken from input.
            $user->role = Role::User;
            $user->status = AccountStatus::Pending;
            $user->save();

            if ($request->hasFile('avatar')) {
                $user->forceFill(['avatar_path' => $request->file('avatar')->store('avatars', 'public')])->save();
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $code = $verification->issue($user);

        return redirect()->route('verification.notice')->with('success', 'Account created. Enter the 6-digit code to verify your account.')
            ->with('dev_code', config('medimatch.show_dev_codes') ? $code : null);
    }
}
