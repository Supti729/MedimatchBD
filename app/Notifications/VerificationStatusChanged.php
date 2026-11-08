<?php

namespace App\Notifications;

use App\Models\VerificationSubmission;

class VerificationStatusChanged extends PlatformNotification
{
    public function __construct(public VerificationSubmission $submission) {}

    protected function type(): string
    {
        return 'verification';
    }

    protected function title(): string
    {
        return "{$this->submission->kind_label}: ".strtolower($this->submission->status->label());
    }

    protected function body(): string
    {
        return $this->submission->reviewer_note ?: "Your submission status changed to {$this->submission->status->label()}.";
    }

    protected function url(): string
    {
        return route('verification.index');
    }
}
