<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Counts messages from other people that arrived after the member last opened each conversation. */
class UnreadMessages
{
    public static function countFor(User $user): int
    {
        $id = $user->id;

        return DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('messages.sender_id', '!=', $id)
            ->where(function ($q) use ($id) {
                $q->where(fn ($q) => $q->where('conversations.donor_id', $id)
                    ->where(fn ($q) => $q->whereNull('conversations.donor_read_at')->orWhereColumn('messages.created_at', '>', 'conversations.donor_read_at')))
                    ->orWhere(fn ($q) => $q->where('conversations.requester_id', $id)
                        ->where(fn ($q) => $q->whereNull('conversations.requester_read_at')->orWhereColumn('messages.created_at', '>', 'conversations.requester_read_at')));
            })
            ->count();
    }
}
