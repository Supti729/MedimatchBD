<?php

namespace App\Notifications;

use App\Models\Report;

class ReportStatusUpdated extends PlatformNotification
{
    public function __construct(public Report $report) {}

    protected function type(): string
    {
        return 'report';
    }

    protected function title(): string
    {
        return 'Update on your report';
    }

    protected function body(): string
    {
        return "Your report ({$this->report->category}) is now ".strtolower($this->report->status->label()).'.';
    }

    protected function url(): string
    {
        return route('notifications.index');
    }
}
