<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Order;
use App\Notifications\CustomerAccessLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerFlowTest extends TestCase
{
    use RefreshDatabase;

    private function bookingPayload($service, array $extra = []): array
    {
        return array_merge([
            'catalog_item_id' => $service->id, 'date' => '2026-11-16', 'time' => '10:00',
            'name' => 'Ana Cruz', 'email' => 'ana@example.test', 'phone' => '0917', 'notes' => '',
        ], $extra);
    }

    public function test_review_then_confirm_creates_one_booking_even_if_resubmitted(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $this->get(route('public.home', $b))->assertOk()->assertSee($s->name);
        $this->get(route('public.slots', [$b, 'service' => $s->id, 'date' => '2026-11-16']))->assertOk()->assertJsonFragment(['time' => '10:00']);

        $review = $this->post(route('public.book.review', $b), $this->bookingPayload($s));
        $review->assertOk()->assertSee('Review and confirm')->assertSee('Monday, November 16, 2026 at 10:00 AM');
        $this->assertSame(0, Appointment::count(), 'review does not submit');

        $key = (string) Str::uuid();
        $payload = $this->bookingPayload($s, ['idempotency_key' => $key, 'confirmed' => '1']);
        $res = $this->post(route('public.book.store', $b), $payload);
        $a = Appointment::first();
        $res->assertRedirect(route('public.result', [$b, $a->reference]));
        $this->post(route('public.book.store', $b), $payload); // double click / refresh
        $this->assertSame(1, Appointment::count());

        $this->get(route('public.result', [$b, $a->reference]))->assertOk()->assertSee($a->reference)->assertSee('confirmed');
    }

    public function test_confirmation_checkbox_is_required(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $this->post(route('public.book.store', $b), $this->bookingPayload($s, ['idempotency_key' => (string) Str::uuid()]))->assertSessionHasErrors('confirmed');
        $this->assertSame(0, Appointment::count());
    }

    public function test_reference_alone_never_unlocks_records(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $this->post(route('public.book.store', $b), $this->bookingPayload($s, ['idempotency_key' => (string) Str::uuid(), 'confirmed' => '1']));
        $ref = Appointment::first()->reference;

        $this->flushSession();
        $this->get(route('public.result', [$b, $ref]))->assertRedirect(route('public.track', $b));
        $this->get(route('public.my', $b))->assertRedirect(route('public.track', $b));
    }

    public function test_track_link_is_sent_only_for_matching_email_and_works_once(): void
    {
        Notification::fake();
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $this->post(route('public.book.store', $b), $this->bookingPayload($s, ['idempotency_key' => (string) Str::uuid(), 'confirmed' => '1']));
        $ref = Appointment::first()->reference;
        $this->flushSession();

        $this->post(route('public.track.send', $b), ['reference' => $ref, 'email' => 'wrong@example.test'])->assertSessionHas('status');
        Notification::assertNothingSent();

        $this->post(route('public.track.send', $b), ['reference' => $ref, 'email' => 'ana@example.test']);
        $url = null;
        Notification::assertSentOnDemand(CustomerAccessLink::class, function ($n) use (&$url) {
            $url = $n->url;

            return true;
        });
        $this->get($url)->assertRedirect(route('public.my', $b));
        $this->get(route('public.my', $b))->assertOk()->assertSee($ref);

        $this->flushSession();
        $this->get($url)->assertRedirect(route('public.track', $b)); // single use
    }

    public function test_customer_access_does_not_cross_businesses(): void
    {
        $a = $this->makeBusiness();
        $b = $this->makeBusiness();
        $s = $this->service($a);
        $this->post(route('public.book.store', $a), $this->bookingPayload($s, ['idempotency_key' => (string) Str::uuid(), 'confirmed' => '1']));
        $ref = Appointment::first()->reference;
        $this->get(route('public.result', [$b, $ref]))->assertRedirect(route('public.track', $b));
    }

    public function test_order_flow_with_delivery_needs_fee_review(): void
    {
        $b = $this->makeBusiness();
        $p = $this->product($b, [], 5);
        $data = ['items' => [$p->id => 2], 'fulfillment_method' => 'delivery', 'delivery_address' => 'Blk 1 Lot 2', 'name' => 'Ana', 'email' => 'ana@example.test', 'phone' => '0917'];
        $this->post(route('public.order.review', $b), $data)->assertOk()->assertSee('To be confirmed by staff');
        $this->post(route('public.order.store', $b), $data + ['reviewed_subtotal' => 100000, 'idempotency_key' => (string) Str::uuid(), 'confirmed' => '1']);
        $o = Order::first();
        $this->assertSame('pending_approval', $o->status);
        $this->assertNull($o->delivery_fee_cents);
        $this->assertSame(2, $p->fresh()->stock_reserved);
    }

    public function test_disabled_module_rejects_submissions(): void
    {
        $b = $this->makeBusiness(['modules' => ['appointments']]);
        $this->get(route('public.order', $b))->assertNotFound();
        $this->post(route('public.quote.review', $b), [])->assertNotFound();
    }

    public function test_unactivated_business_does_not_accept_submissions(): void
    {
        $b = $this->makeBusiness(['activated_at' => null]);
        $s = $this->service($b);
        $this->post(route('public.book.store', $b), $this->bookingPayload($s, ['idempotency_key' => (string) Str::uuid(), 'confirmed' => '1']))
            ->assertSessionHasErrors('rule');
        $this->assertSame(0, Appointment::count());
    }
}
