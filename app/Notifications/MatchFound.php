<?php

namespace App\Notifications;

use App\Models\MedicineListing;
use App\Models\MedicineRequest;

class MatchFound extends PlatformNotification
{
    public function __construct(public MedicineRequest $request, public MedicineListing $listing) {}

    protected function type(): string
    {
        return 'match';
    }

    protected function title(): string
    {
        return 'New match for your request';
    }

    protected function body(): string
    {
        return "{$this->listing->name} in {$this->listing->upazila}, {$this->listing->district} looks relevant to your request.";
    }

    protected function url(): string
    {
        return route('requests.show', $this->request);
    }
}
