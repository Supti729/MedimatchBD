<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * One-time codes that confirm a member controls their email or phone.
 *
 * Honest limits of the prototype: email goes through Laravel's configured mailer
 * ("log" in development, so nothing leaves the machine) and there is no SMS
 * provider. In development the code is also shown on screen, clearly labelled.
 */
class AccountVerificationService
{
    public function issue(User $user): string
    {
        $code = (string) random_int(100000, 999999);
        $user->forceFill([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => now()->addMinutes(config('medimatch.verification_code_ttl_minutes')),
            'verification_attempts' => 0,
        ])->save();

        if ($user->email) {
            $user->notify(new VerificationCodeNotification($code));
        }
        if (config('medimatch.show_dev_codes')) {
            Log::info("[DEV ONLY] Verification code for user #{$user->id}: {$code}");
        }

        return $code;
    }

    public function verify(User $user, string $code): void
    {
        if (! $user->verification_code || $user->verification_code_expires_at?->isPast()) {
            throw ValidationException::withMessages(['code' => 'This code has expired. Request a new one.']);
        }
        if ($user->verification_attempts >= config('medimatch.verification_max_attempts')) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Request a new code.']);
        }

        $user->increment('verification_attempts');
        if (! Hash::check($code, $user->verification_code)) {
            throw ValidationException::withMessages(['code' => 'That code is not correct.']);
        }

        $user->forceFill([
            // The code is delivered to the email when there is one, otherwise it stands in for an SMS.
            $user->email ? 'email_verified_at' : 'phone_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
            'verification_attempts' => 0,
        ]);
        if ($user->status === AccountStatus::Pending) {
            $user->status = AccountStatus::Active;
        }
        $user->save();
    }

    /** Channel the code goes to, for the on-screen message. */
    public function channelFor(User $user): string
    {
        return $user->email ? 'email' : 'phone';
    }
}
