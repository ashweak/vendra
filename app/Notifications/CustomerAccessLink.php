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
            ->subject(__('Your :business request link', ['business' => $this->business->name]))
            ->line(__('Use this link to view your requests with :business. It expires in 30 minutes and works once.', ['business' => $this->business->name]))
            ->action(__('View my requests'), $this->url)
            ->line(__('If you did not ask for this, you can ignore this email.'));
    }
}
