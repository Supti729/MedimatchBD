<?php

namespace App\Notifications;

use App\Models\MedicineListing;

class ListingStatusChanged extends PlatformNotification
{
    public function __construct(public MedicineListing $listing, public string $headline, public ?string $note = null) {}

    protected function type(): string
    {
        return 'listing';
    }

    protected function title(): string
    {
        return $this->headline;
    }

    protected function body(): string
    {
        return $this->note ?: "{$this->listing->name} is now ".strtolower($this->listing->status->label()).'.';
    }

    protected function url(): string
    {
        return route('listings.index');
    }
}
