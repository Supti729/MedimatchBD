<?php

namespace App\Policies;

use App\Enums\InteractionStatus;
use App\Models\Interaction;
use App\Models\User;

class InteractionPolicy
{
    /** Only the donor decides who receives the medicine. */
    public function reserve(User $user, Interaction $interaction): bool
    {
        return $user->id === $interaction->donor_id && $interaction->status === InteractionStatus::Requested;
    }

    public function complete(User $user, Interaction $interaction): bool
    {
        return in_array($user->id, [$interaction->donor_id, $interaction->requester_id], true) && $interaction->status === InteractionStatus::Reserved;
    }

    public function cancel(User $user, Interaction $interaction): bool
    {
        return in_array($user->id, [$interaction->donor_id, $interaction->requester_id], true) && $interaction->status->isOpen();
    }
}
