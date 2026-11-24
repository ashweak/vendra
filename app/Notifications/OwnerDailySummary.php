<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Morning email to owners: today's bookings and what needs attention. Counts and names only, no customer contact details. */
class OwnerDailySummary extends Notification
{
    public function __construct(public Business $business, public array $summary) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->summary;
        $b = $this->business;
        $m = (new MailMessage)
            ->subject("{$b->name} today: ".count($s['bookings']).' '.str('booking')->plural(count($s['bookings'])).($s['approvals'] ? ", {$s['approvals']} to approve" : ''))
            ->greeting('Good morning!')
            ->line("Here’s {$b->name} for {$s['date']}.");
        if ($s['bookings']) {
            $m->line('**Today’s bookings**');
            foreach (array_slice($s['bookings'], 0, 12) as $line) {
                $m->line('• '.$line);
            }
            if (count($s['bookings']) > 12) {
                $m->line('…and '.(count($s['bookings']) - 12).' more.');
            }
        } else {
            $m->line('No bookings today.');
        }
        $todo = array_filter([
            $s['approvals'] ? "{$s['approvals']} waiting for your approval" : null,
            $s['orders_to_prepare'] ? "{$s['orders_to_prepare']} ".str('order')->plural($s['orders_to_prepare']).' to prepare' : null,
            $s['chats_waiting'] ? "{$s['chats_waiting']} ".str('chat')->plural($s['chats_waiting']).' waiting for staff' : null,
            $s['unanswered'] ? "{$s['unanswered']} unanswered ".str('question')->plural($s['unanswered']).' for your FAQ' : null,
            $s['low_stock'] ? 'Low stock: '.implode(', ', array_slice($s['low_stock'], 0, 8)) : null,
        ]);
        if ($todo) {
            $m->line('**Needs attention**');
            foreach ($todo as $t) {
                $m->line('• '.$t);
            }
        }

        return $m->action('Open the dashboard', route('dashboard.overview'))
            ->line('You get this email at 7:00 AM. Owners can turn it off in Settings → Modules & workflow.');
    }
}
