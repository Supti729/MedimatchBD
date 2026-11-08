<?php

namespace App\Notifications;

use App\Models\Interaction;

class InteractionCompleted extends PlatformNotification
{
    public function __construct(public Interaction $interaction) {}

    protected function type(): string
    {
        return 'completion';
    }

    protected function title(): string
    {
        return 'Hand-over completed';
    }

    protected function body(): string
    {
        return "{$this->interaction->listing->name} was marked as handed over. Thank you for helping.";
    }

    protected function url(): string
    {
        return route('history');
    }
}
