<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerAccessLink extends Notification
{
    public function __construct(public Business $business, public string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your {$this->business->name} request link")
            ->line("Use this link to view your requests with {$this->business->name}. It expires in 30 minutes and works once.")
            ->action('View my requests', $this->url)
            ->line('If you did not ask for this, you can ignore this email.');
    }
}
