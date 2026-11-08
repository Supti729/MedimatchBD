<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Log in with an email address or a Bangladeshi phone number. */
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->input('login'));
        $credentials = str_contains($login, '@')
            ? ['email' => strtolower($login)]
            : ['phone' => User::normalisePhone($login) ?? $login];
        $credentials['password'] = $this->input('password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);
            throw ValidationException::withMessages(['login' => 'These details don\'t match an account. Check your email/phone and password.']);
        }

        /** @var User $user */
        $user = Auth::user();
        if ($user->isSuspended()) {
            Auth::logout();
            throw ValidationException::withMessages(['login' => 'This account is suspended. Contact us if you think this is a mistake.']);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }
        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages(['login' => "Too many attempts. Try again in {$seconds} seconds."]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
