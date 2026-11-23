<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Business;
use App\Notifications\AppointmentReminder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(Business $b, string $startsManila, string $status = 'confirmed', ?string $email = 'ana@example.test'): Appointment
    {
        $c = $this->customer($b, $email ?? 'none@example.test');
        if ($email === null) {
            $c->forceFill(['email' => null])->save();
        }
        $start = CarbonImmutable::parse($startsManila, 'Asia/Manila')->utc();
        $a = new Appointment(['customer_id' => $c->id, 'service_name' => 'Hair Color, long; "special"', 'price_cents' => 180000,
            'starts_at' => $start, 'ends_at' => $start->addMinutes(90), 'status' => $status]);
        $a->business_id = $b->id;
        $a->save();

        return $a;
    }

    public function test_day_before_reminder_is_sent_once_with_a_calendar_file(): void
    {
        Notification::fake();
        $b = $this->makeBusiness(['address' => '12 Sampaguita St., Quezon City', 'phone' => '0917 000 0101']);
        $ahead = $this->appointment($b, '2026-11-18 10:00');                 // booked 2 days ahead
        $soon = $this->appointment($b, '2026-11-18 09:00', email: 'late@example.test');
        $soon->forceFill(['created_at' => CarbonImmutable::parse('2026-11-17 11:00', 'Asia/Manila')])->save(); // booked less than a day ahead
        $pending = $this->appointment($b, '2026-11-18 11:00', 'pending_approval', 'p@example.test');
        $noEmail = $this->appointment($b, '2026-11-18 12:00', email: null);
        $off = $this->makeBusiness([], ['booking_reminders' => false]);
        $this->appointment($off, '2026-11-18 10:00', email: 'off@example.test');

        $this->travelTo(CarbonImmutable::parse('2026-11-17 12:00', 'Asia/Manila'));
        $this->artisan('vendra:remind')->assertSuccessful()->expectsOutput('Reminders sent: 1');
        $this->artisan('vendra:remind')->expectsOutput('Reminders sent: 0');

        Notification::assertSentOnDemandTimes(AppointmentReminder::class, 1);
        Notification::assertSentOnDemand(AppointmentReminder::class, function ($n, $channels, $notifiable) use ($ahead) {
            if ($notifiable->routes['mail'] !== 'ana@example.test' || $n->appointment->id !== $ahead->id) {
                return false;
            }
            $mail = $n->toMail(new AnonymousNotifiable);
            $ics = $mail->rawAttachments[0]['data'];

            return str_contains($mail->subject, 'Hair Color')
                && $mail->rawAttachments[0]['name'] === $ahead->reference.'.ics'
                && str_contains($ics, "DTSTART:20261118T020000Z\r\n")
                && str_contains($ics, "DTEND:20261118T033000Z\r\n");
        });
        $this->assertNotNull($ahead->fresh()->reminder_sent_at);
        $this->assertNull($soon->fresh()->reminder_sent_at);
        $this->assertNull($pending->fresh()->reminder_sent_at);
        $this->assertNull($noEmail->fresh()->reminder_sent_at);
    }

    public function test_reminder_uses_the_page_language_of_the_business(): void
    {
        Notification::fake();
        $b = $this->makeBusiness(['phone' => '0917 000 0101'], ['language' => 'fil']);
        $a = $this->appointment($b, '2026-11-18 10:00');
        $this->travelTo(CarbonImmutable::parse('2026-11-17 12:00', 'Asia/Manila'));
        $this->artisan('vendra:remind');
        Notification::assertSentOnDemand(AppointmentReminder::class, fn ($n) => $n->locale === 'fil');

        app()->setLocale('fil');
        $mail = (new AppointmentReminder($a->fresh()))->toMail(new AnonymousNotifiable);
        app()->setLocale('en');
        $this->assertSame('Paalala: Hair Color, long; "special" sa '.$b->name, $mail->subject);
        $this->assertSame('Tingnan o kanselahin ang booking ko', $mail->actionText);
        $this->assertStringContainsString('Miyerkules, Nobyembre 18, 2026 at 10:00 AM', implode(' ', $mail->introLines));
        $this->assertStringContainsString('Telepono: 0917 000 0101', implode(' ', $mail->outroLines));
    }

    public function test_calendar_file_is_valid_ics_and_only_for_the_signed_in_customer(): void
    {
        $b = $this->makeBusiness(['address' => 'Unit 2, Blk 3; Brgy. Malinis, Quezon City']);
        $s = $this->service($b, ['name' => 'Hair Color']);
        $this->post(route('public.book.store', $b), ['catalog_item_id' => $s->id, 'date' => '2026-11-16', 'time' => '10:00', 'name' => 'Ana', 'email' => 'ana@example.test',
            'idempotency_key' => (string) Str::uuid(), 'confirmed' => '1'])->assertRedirect();
        $a = Appointment::sole();

        $this->get(route('public.result', [$b, $a->reference]))->assertSee(route('public.my.appointment.ics', [$b, $a->reference]), false);
        $res = $this->get(route('public.my.appointment.ics', [$b, $a->reference]))->assertOk();
        $this->assertStringStartsWith('text/calendar', $res->headers->get('Content-Type'));
        $ics = $res->getContent();
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString('LOCATION:Unit 2\, Blk 3\; Brgy. Malinis\, Quezon City', str_replace("\r\n ", '', $ics));
        $this->assertStringContainsString('UID:'.strtolower($a->reference).'@', $ics);
        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }

        $this->flushSession();
        $this->get(route('public.my.appointment.ics', [$b, $a->reference]))->assertForbidden();
    }

    public function test_owner_can_turn_reminders_off(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.settings.workflow'))->assertSee('name="booking_reminders"', false);
        $this->actingAs($owner)->put(route('dashboard.settings.workflow'), array_merge($b->settings, ['modules' => $b->modules, 'booking_reminders' => '0']))->assertSessionHasNoErrors();
        $this->assertFalse($b->fresh()->setting('booking_reminders'));
    }
}
