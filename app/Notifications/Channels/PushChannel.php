<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\WebPush;
use Illuminate\Notifications\Notification;

/** Sends a data-less push; the service worker fetches the alert text from /app/push/latest. */
class PushChannel
{
    public function __construct(private WebPush $push) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if ($notifiable instanceof User) {
            $this->push->notifyUser($notifiable);
        }
    }
}
