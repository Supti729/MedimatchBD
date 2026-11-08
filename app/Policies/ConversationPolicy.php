<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

/** Only the two participants can read or write a conversation. */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user) && ! $conversation->otherParticipant($user)->isSuspended();
    }
}
