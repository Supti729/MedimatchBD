<?php

namespace App\Policies;

use App\Models\MedicineRequest;
use App\Models\User;

/** Requests are private to their owner (admins see them in the admin area). */
class MedicineRequestPolicy
{
    public function view(User $user, MedicineRequest $request): bool
    {
        return $user->id === $request->user_id;
    }

    public function update(User $user, MedicineRequest $request): bool
    {
        return $user->id === $request->user_id && $request->status->isOpen();
    }

    public function cancel(User $user, MedicineRequest $request): bool
    {
        return $this->update($user, $request);
    }
}
