<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Support\Str;

class AnnouncementPublished extends PlatformNotification
{
    public function __construct(public Announcement $announcement) {}

    protected function type(): string
    {
        return 'announcement';
    }

    protected function title(): string
    {
        return $this->announcement->title;
    }

    protected function body(): string
    {
        return Str::limit($this->announcement->body, 160);
    }

    protected function url(): string
    {
        return route('notifications.index');
    }
}
