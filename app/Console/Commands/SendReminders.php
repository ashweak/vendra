<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Emails customers about confirmed bookings starting within the next 24 hours.
 * Skipped for bookings made less than a day ahead (the customer just booked), for customers
 * without an email, and for businesses that turned reminders off. Each booking gets one reminder.
 */
class SendReminders extends Command
{
    protected $signature = 'vendra:remind';

    protected $description = 'Email day-before reminders for confirmed bookings';

    public function handle(): int
    {
        $sent = 0;
        Appointment::query()
            ->where('status', 'confirmed')
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [now(), now()->addDay()])
            ->with(['business', 'customer'])
            ->chunkById(100, function ($appointments) use (&$sent) {
                foreach ($appointments as $a) {
                    if (! $a->business || ! $a->business->setting('booking_reminders', true)
                        || $a->business->status !== 'active'
                        || $a->created_at->diffInHours($a->starts_at, true) < 24
                        || ! filled($a->customer?->email)) {
                        continue;
                    }
                    // Mark first, so a mail failure can never cause repeated emails.
                    $a->forceFill(['reminder_sent_at' => now()])->save();
                    try {
                        Notification::route('mail', $a->customer->email)->notify((new AppointmentReminder($a))->locale($a->business->setting('language', 'en')));
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning('Reminder not sent for '.$a->reference.': '.$e->getMessage());
                    }
                }
            });
        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
