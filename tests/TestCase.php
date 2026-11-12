<?php

namespace Tests;

use App\Models\Business;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Membership;
use App\Models\StaffSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['vendra.enforce_mfa' => false, 'vendra.ai.provider' => 'rules']);
        // Monday 2026-11-16 08:00 Manila – deterministic availability.
        $this->travelTo(CarbonImmutable::parse('2026-11-16 08:00', 'Asia/Manila'));
    }

    protected function makeBusiness(array $attrs = [], array $settings = []): Business
    {
        static $n = 0;
        $n++;
        $b = Business::create(array_merge([
            'name' => "Test Biz $n", 'slug' => "test-biz-$n", 'category' => 'salon',
            'modules' => ['appointments', 'orders', 'quotations', 'service_requests'],
            'settings' => array_merge(config('vendra.default_settings'), ['booking_lead_minutes' => 0, 'fulfillment_methods' => ['pickup', 'delivery']], $settings),
            'activated_at' => now(),
        ], $attrs));
        foreach (range(0, 6) as $d) {
            $b->hours()->create(['weekday' => $d, 'opens_at' => '09:00', 'closes_at' => '18:00', 'is_closed' => $d === 0]);
        }

        return $b;
    }

    protected function addMember(Business $b, string $role, ?User $user = null): User
    {
        $user ??= User::factory()->create();
        $m = new Membership(['user_id' => $user->id, 'role' => $role]);
        $m->business_id = $b->id;
        $m->save();

        return $user;
    }

    protected function schedule(Business $b, User $u, string $from = '09:00', string $to = '18:00'): void
    {
        foreach (range(1, 6) as $d) {
            $s = new StaffSchedule(['user_id' => $u->id, 'weekday' => $d, 'starts_at' => $from, 'ends_at' => $to]);
            $s->business_id = $b->id;
            $s->save();
        }
    }

    protected function service(Business $b, array $attrs = []): CatalogItem
    {
        $i = new CatalogItem(array_merge(['type' => 'service', 'name' => 'Haircut', 'price_cents' => 25000, 'duration_minutes' => 60], $attrs));
        $i->business_id = $b->id;
        $i->save();

        return $i;
    }

    protected function product(Business $b, array $attrs = [], ?int $stock = 10): CatalogItem
    {
        $i = new CatalogItem(array_merge(['type' => 'product', 'name' => 'Serum', 'price_cents' => 50000], $attrs));
        $i->business_id = $b->id;
        $i->stock_on_hand = $stock;
        $i->save();

        return $i;
    }

    protected function customer(Business $b, string $email = 'ana@example.test'): Customer
    {
        $c = new Customer(['name' => 'Ana', 'email' => $email]);
        $c->business_id = $b->id;
        $c->save();

        return $c;
    }
}
