<?php

namespace Tests\Feature;

use App\Exceptions\RuleViolation;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(OrderService::class);
    }

    public function test_reserve_then_deduct_once_on_dispatch(): void
    {
        $b = $this->makeBusiness([], ['order_approval' => 'auto']);
        $owner = $this->addMember($b, 'owner');
        $p = $this->product($b, [], 10);
        $o = $this->svc->place($b, $this->customer($b), [$p->id => 3], 'delivery', 'Somewhere', null, 'k');
        $this->assertSame('pending_approval', $o->status); // unknown delivery fee forces review
        $this->assertSame(3, $p->fresh()->stock_reserved);
        $this->assertSame(7, $p->fresh()->availableStock());

        try {
            $this->svc->transition($o, 'confirmed', $owner);
            $this->fail('Fee required');
        } catch (RuleViolation) {
        }
        $this->svc->setDeliveryFee($o, 8000, $owner);
        $this->svc->transition($o->fresh(), 'confirmed', $owner);
        $this->svc->transition($o->fresh(), 'dispatched', $owner);
        $this->assertSame(7, $p->fresh()->stock_on_hand);
        $this->assertSame(0, $p->fresh()->stock_reserved);
        $this->svc->transition($o->fresh(), 'completed', $owner);
        $this->assertSame(7, $p->fresh()->stock_on_hand, 'no double deduction');
        $this->assertSame(158000, $o->fresh()->total_cents);
    }

    public function test_cancel_releases_reservation_and_cannot_cancel_after_handover(): void
    {
        $b = $this->makeBusiness([], ['order_approval' => 'auto']);
        $p = $this->product($b, [], 5);
        $o = $this->svc->place($b, $this->customer($b), [$p->id => 2], 'pickup', null, null, null);
        $this->assertSame('confirmed', $o->status);
        $this->svc->transition($o, 'cancelled');
        $this->assertSame(0, $p->fresh()->stock_reserved);
        $this->assertSame(5, $p->fresh()->stock_on_hand);

        $o2 = $this->svc->place($b, $this->customer($b), [$p->id => 1], 'pickup', null, null, null);
        $this->svc->transition($o2, 'ready_for_pickup');
        $this->svc->transition($o2->fresh(), 'completed');
        $this->assertSame(4, $p->fresh()->stock_on_hand);
        $this->expectException(RuleViolation::class);
        $this->svc->transition($o2->fresh(), 'cancelled');
    }

    public function test_cannot_oversell_reserved_stock(): void
    {
        $b = $this->makeBusiness();
        $p = $this->product($b, [], 3);
        $this->svc->place($b, $this->customer($b), [$p->id => 2], 'pickup', null, null, null);
        $this->expectException(RuleViolation::class);
        $this->svc->place($b, $this->customer($b, 'b@example.test'), [$p->id => 2], 'pickup', null, null, null);
    }

    public function test_changed_price_requires_reconfirmation(): void
    {
        $b = $this->makeBusiness();
        $p = $this->product($b);
        $reviewed = $this->svc->price($b, [$p->id => 1], 'pickup')['subtotal_cents'];
        $p->update(['price_cents' => 60000]);
        try {
            $this->svc->place($b, $this->customer($b), [$p->id => 1], 'pickup', null, $reviewed, 'k2');
            $this->fail('Expected price_changed');
        } catch (RuleViolation $e) {
            $this->assertSame('price_changed', $e->code_);
        }
        $this->assertSame(0, $p->fresh()->stock_reserved, 'failed submission reserves nothing');
    }

    public function test_expiry_releases_stock(): void
    {
        $b = $this->makeBusiness([], ['order_reservation_hours' => 1]);
        $p = $this->product($b, [], 4);
        $o = $this->svc->place($b, $this->customer($b), [$p->id => 4], 'pickup', null, null, null);
        $this->travel(2)->hours();
        $this->assertSame(1, $this->svc->expireOverdue());
        $this->assertSame('expired', $o->fresh()->status);
        $this->assertSame(4, $p->fresh()->availableStock());
    }

    public function test_stock_adjustment_cannot_go_below_reserved(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $p = $this->product($b, [], 5);
        $this->svc->place($b, $this->customer($b), [$p->id => 4], 'pickup', null, null, null);
        $this->expectException(RuleViolation::class);
        $this->svc->adjustStock($p, -3, $owner, 'damaged');
    }
}
