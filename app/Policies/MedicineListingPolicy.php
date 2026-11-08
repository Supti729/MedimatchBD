<?php

namespace App\Policies;

use App\Enums\ListingStatus;
use App\Models\MedicineListing;
use App\Models\User;

class MedicineListingPolicy
{
    /** Public listings are visible to everyone; others only to the owner and staff. */
    public function view(?User $user, MedicineListing $listing): bool
    {
        return $listing->isPublic() || ($user && ($user->id === $listing->user_id || $user->isStaff()));
    }

    public function update(User $user, MedicineListing $listing): bool
    {
        return $user->id === $listing->user_id && $listing->status->ownerCanEdit();
    }

    public function changeStatus(User $user, MedicineListing $listing): bool
    {
        return $user->id === $listing->user_id;
    }

    /** Request the medicine / start a chat about it. */
    public function request(User $user, MedicineListing $listing): bool
    {
        return $user->id !== $listing->user_id
            && in_array($listing->status, [ListingStatus::Available, ListingStatus::Requested], true)
            && $listing->isPublic();
    }

    public function save(User $user, MedicineListing $listing): bool
    {
        return $user->id !== $listing->user_id && $listing->isPublic();
    }

    public function report(User $user, MedicineListing $listing): bool
    {
        return $user->id !== $listing->user_id;
    }

    public function moderate(User $user, MedicineListing $listing): bool
    {
        return $user->isAdmin();
    }
}
