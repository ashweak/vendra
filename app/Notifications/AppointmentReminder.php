<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Support\Ics;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Day-before reminder to the customer, with the booking attached as a calendar file. */
class AppointmentReminder extends Notification
{
    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->appointment;
        $b = $a->business;
        $when = local_time($a->starts_at, $b->timezone, 'l, F j, Y \a\t g:i A');

        return (new MailMessage)
            ->subject(__('Reminder: :service at :business', ['service' => $a->service_name, 'business' => $b->name]))
            ->greeting(__('Hi :name,', ['name' => $a->customer?->name ?: __('there')]))
            ->line(__('This is a reminder of your booking at :business.', ['business' => $b->name]))
            ->line('**'.$a->service_name.'** · '.$when)
            ->line(__('Reference: :ref', ['ref' => $a->reference]).($b->address ? ' · '.$b->address : ''))
            ->action(__('View or cancel my booking'), route('public.track', $b))
            ->line(__('Can’t make it? Please cancel or message us so someone else can take the slot.').($b->phone ? ' '.__('Phone: :phone', ['phone' => $b->phone]) : ''))
            ->line(__('The attached calendar file adds this booking to your phone’s calendar.'))
            ->attachData(Ics::appointment($a), $a->reference.'.ics', ['mime' => 'text/calendar; charset=utf-8']);
    }
}
