<?php

namespace Tests\Feature;

use App\Models\PrivacyRequest;
use App\Notifications\StaffAlert;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedSession($business, $customer): array
    {
        return ['vendra.customer_access' => [$business->id => ['customer_id' => $customer->id, 'until' => now()->addHour()->timestamp]]];
    }

    public function test_business_page_links_a_privacy_notice_with_its_details(): void
    {
        $b = $this->makeBusiness(['legal_name' => 'Test Biz Trading Inc.', 'registration_no' => 'DTI-123456', 'address' => '1 Rizal St., Manila', 'email' => 'hello@biz.test', 'privacy_contact' => 'privacy@biz.test']);

        $this->get(route('public.home', $b))->assertOk()->assertSee(route('public.privacy', $b))->assertSee('DTI-123456')->assertSee('1 Rizal St., Manila');
        $this->get(route('public.privacy', $b))->assertOk()
            ->assertSee('Test Biz Trading Inc.')->assertSee('privacy@biz.test')->assertSee('National Privacy Commission')
            ->assertSee('doesn’t send your messages to any outside AI service', false);
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Data Privacy Act');
        $this->get(route('legal.terms'))->assertOk()->assertSee('not official invoices', false);
    }

    public function test_verified_customer_downloads_only_their_own_data(): void
    {
        $b = $this->makeBusiness();
        $customers = app(CustomerService::class);
        $me = $customers->resolve($b, ['name' => 'Rina Flores', 'email' => 'rina@example.test']);
        $other = $customers->resolve($b, ['name' => 'Other Person', 'email' => 'other@example.test']);

        $this->get(route('public.my.data', $b))->assertForbidden();

        $r = $this->withSession($this->verifiedSession($b, $me))->get(route('public.my.data', $b));
        $r->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="my-data-'.$b->slug.'.json"');
        $r->assertJsonPath('customer.email', 'rina@example.test');
        $this->assertStringNotContainsString('other@example.test', $r->getContent());
        $this->assertStringNotContainsString($other->name, $r->getContent());
    }

    public function test_privacy_request_reaches_owner_and_can_be_closed(): void
    {
        Notification::fake();
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $me = app(CustomerService::class)->resolve($b, ['name' => 'Rina Flores', 'email' => 'rina@example.test']);

        $this->withSession($this->verifiedSession($b, $me))
            ->post(route('public.my.privacy', $b), ['type' => 'correction', 'details' => 'New mobile: 0917 000 0000'])
            ->assertSessionHas('status');
        $pr = PrivacyRequest::forBusiness($b)->sole();
        $this->assertSame('open', $pr->status);
        Notification::assertSentTo($owner, StaffAlert::class);

        // A second identical open request is not duplicated.
        $this->withSession($this->verifiedSession($b, $me))->post(route('public.my.privacy', $b), ['type' => 'correction']);
        $this->assertSame(1, PrivacyRequest::forBusiness($b)->count());

        $this->actingAs($owner)->get(route('dashboard.customers.show', $me->id))->assertOk()->assertSee('New mobile: 0917 000 0000');
        $this->actingAs($owner)->post(route('dashboard.customers.privacy', [$me->id, $pr->id]), ['status' => 'done', 'resolution' => 'Updated the number.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('done', $pr->fresh()->status);
        $this->assertSame($owner->id, $pr->fresh()->handled_by);
    }

    public function test_privacy_request_needs_a_verified_customer(): void
    {
        $b = $this->makeBusiness();
        $this->post(route('public.my.privacy', $b), ['type' => 'deletion'])->assertForbidden();
        $this->assertSame(0, PrivacyRequest::count());
    }

    public function test_error_pages_are_branded_and_pages_have_share_previews(): void
    {
        $this->get('/b/no-such-business-here')->assertNotFound()->assertSee('Page not found');
        $b = $this->makeBusiness(['description' => 'Neighborhood salon for cuts and color.']);
        $this->get(route('public.home', $b))->assertSee('<meta property="og:description" content="Neighborhood salon for cuts and color.', false);
    }
}
