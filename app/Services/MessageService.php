<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use Illuminate\Validation\ValidationException;

class MessageService
{
    /** Bangladeshi mobile numbers (with or without +880/0, spaces or dashes) and email addresses. */
    private const CONTACT_PATTERNS = [
        '/(?:\+?\s*8\s*8\s*0|\b0)?\s*1\s*[3-9](?:[\s\-.]*\d){8}\b/u',
        '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
    ];

    public function send(Conversation $conversation, User $sender, string $body, bool $notify = true): Message
    {
        $body = trim($body);
        if (self::containsContactDetails($body)) {
            throw ValidationException::withMessages(['body' => 'For your safety, please don\'t share phone numbers or email addresses. Keep the conversation on Medimatch BD.']);
        }

        $message = $conversation->messages()->create(['sender_id' => $sender->id, 'body' => $body]);
        $conversation->forceFill(['last_message_at' => $message->created_at])->save();
        $conversation->markReadFor($sender);

        if ($notify) {
            $recipient = $conversation->otherParticipant($sender);
            // One unread "new message" notification per conversation is enough.
            $url = route('messages.show', $conversation);
            $alreadyPending = $recipient->unreadNotifications()
                ->where('type', NewMessageReceived::class)
                ->get()
                ->contains(fn ($n) => ($n->data['url'] ?? null) === $url);
            if (! $alreadyPending) {
                $recipient->notify(new NewMessageReceived($conversation, $sender->name));
            }
        }

        return $message;
    }

    public static function containsContactDetails(string $text): bool
    {
        foreach (self::CONTACT_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}
