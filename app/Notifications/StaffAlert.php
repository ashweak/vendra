<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** In-app (database) + locally captured email. A failed notification never reverses a saved transaction. */
class StaffAlert extends Notification
{
    public function __construct(public string $title, public string $body, public ?string $url = null, public int $businessId = 0) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = (new MailMessage)->subject('[Vendra] '.$this->title)->line($this->body);

        return $this->url ? $m->action('Open in Vendra', $this->url) : $m;
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url, 'business_id' => $this->businessId];
    }
}
