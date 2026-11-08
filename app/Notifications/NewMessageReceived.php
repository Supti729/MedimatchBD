<?php

namespace App\Notifications;

use App\Models\Conversation;

class NewMessageReceived extends PlatformNotification
{
    public function __construct(public Conversation $conversation, public string $senderName) {}

    protected function type(): string
    {
        return 'message';
    }

    protected function title(): string
    {
        return "New message from {$this->senderName}";
    }

    protected function body(): string
    {
        return "About {$this->conversation->listing->name}.";
    }

    protected function url(): string
    {
        return route('messages.show', $this->conversation);
    }
}
