<?php

namespace App\Notifications;

use App\Models\Interaction;

class ListingRequested extends PlatformNotification
{
    public function __construct(public Interaction $interaction) {}

    protected function type(): string
    {
        return 'request';
    }

    protected function title(): string
    {
        return "{$this->interaction->requester->name} requested {$this->interaction->listing->name}";
    }

    protected function body(): string
    {
        return 'Reply in chat, then reserve the medicine if you can help.';
    }

    protected function url(): string
    {
        return route('messages.show', $this->interaction->conversation_id);
    }
}
