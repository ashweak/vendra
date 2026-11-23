<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** In-app (database) + locally captured email + optional device push. A failed notification never reverses a saved transaction. */
class StaffAlert extends Notification
{
    public function __construct(public string $title, public string $body, public ?string $url = null, public int $businessId = 0) {}

    public function via(object $notifiable): array
    {
        // Also wake the user's devices that turned on alerts (the push itself carries no details).
        return \App\Services\WebPush::enabled() && \App\Models\PushSubscription::where('user_id', $notifiable->id ?? 0)->exists()
            ? ['database', 'mail', \App\Notifications\Channels\PushChannel::class]
            : ['database', 'mail'];
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
