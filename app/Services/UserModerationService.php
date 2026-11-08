<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Admin-only account changes. Every change is audit-logged. */
class UserModerationService
{
    public function changeStatus(User $user, AccountStatus $status, string $reason): void
    {
        $this->guard($user);
        $from = $user->status;
        $user->status = $status;
        $user->save();

        if ($status === AccountStatus::Suspended) {
            // End all of the member's sessions immediately.
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        AuditLogger::log('user.'.($status === AccountStatus::Active ? ($from === AccountStatus::Suspended ? 'restored' : 'activated') : $status->value), "User #{$user->id} {$user->name}", $user, $reason);
    }

    public function changeRole(User $user, Role $role, string $reason): void
    {
        $this->guard($user);
        $from = $user->role;
        $user->role = $role;
        $user->save();
        AuditLogger::log('user.role_changed', "User #{$user->id} {$user->name}: {$from->label()} → {$role->label()}", $user, $reason);
    }

    private function guard(User $user): void
    {
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages(['status' => 'You cannot change your own account here.']);
        }
    }
}
