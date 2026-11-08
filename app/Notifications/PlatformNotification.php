<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Base class for in-platform (database) notifications.
 * Every notification stores: type (icon key), title, body and url.
 * Email/SMS channels are intentionally not used: no provider is configured.
 */
abstract class PlatformNotification extends Notification
{
    use Queueable;

    abstract protected function type(): string;

    abstract protected function title(): string;

    abstract protected function body(): string;

    abstract protected function url(): string;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => $this->type(), 'title' => $this->title(), 'body' => $this->body(), 'url' => $this->url()];
    }
}
