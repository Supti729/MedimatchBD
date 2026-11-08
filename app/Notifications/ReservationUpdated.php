<?php

namespace App\Notifications;

use App\Models\Interaction;

class ReservationUpdated extends PlatformNotification
{
    public function __construct(public Interaction $interaction, public string $headline) {}

    protected function type(): string
    {
        return 'reservation';
    }

    protected function title(): string
    {
        return $this->headline;
    }

    protected function body(): string
    {
        return "{$this->interaction->listing->name} · {$this->interaction->listing->quantity_label}";
    }

    protected function url(): string
    {
        return route('messages.show', $this->interaction->conversation_id);
    }
}
