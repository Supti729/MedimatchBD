<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent by email through the configured mailer ("log" in development). */
class VerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(private string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Medimatch BD verification code')
            ->line("Your verification code is: {$this->code}")
            ->line('It expires in '.config('medimatch.verification_code_ttl_minutes').' minutes. If you did not create an account, ignore this email.');
    }
}
