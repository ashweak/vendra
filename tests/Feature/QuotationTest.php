<?php

namespace Tests\Feature;

use App\Exceptions\RuleViolation;
use App\Services\QuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_versioned_flow_accepts_only_current_version_and_converts_once(): void
    {
        $svc = app(QuotationService::class);
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $staff = $this->addMember($b, 'staff');
        $c = $this->customer($b);
        $q = $svc->submit($b, $c, 'Bridal hair for 5 people', null);

        $v1 = $svc->draftVersion($q, [['description' => 'Bridal hair', 'quantity' => 5, 'unit_price_cents' => 150000]], null, '2026-11-30', $staff);
        $this->assertSame(750000, $v1->total_cents);
        $svc->approveAndSend($q->fresh(), $owner);
        $svc->respond($q->fresh(), $v1->id, 'revise', $c, 'Can you include makeup?');
        $this->assertSame('under_review', $q->fresh()->status);

        $v2 = $svc->draftVersion($q->fresh(), [['description' => 'Hair + makeup', 'quantity' => 5, 'unit_price_cents' => 250000]], null, '2026-11-30', $staff);
        $svc->approveAndSend($q->fresh(), $owner);

        try {
            $svc->respond($q->fresh(), $v1->id, 'accept', $c);
            $this->fail('Stale version accepted');
        } catch (RuleViolation $e) {
            $this->assertSame('stale_version', $e->code_);
        }
        $svc->respond($q->fresh(), $v2->id, 'accept', $c);
        $this->assertSame($v2->id, $q->fresh()->accepted_version_id);
        $this->assertSame(750000, $v1->fresh()->total_cents, 'old version unchanged');

        $sr = $svc->convertToServiceRequest($q->fresh(), $owner);
        $this->assertSame($q->id, $sr->quotation_id);
        $this->expectException(RuleViolation::class);
        $svc->convertToServiceRequest($q->fresh(), $owner);
    }

    public function test_expired_quote_cannot_be_accepted(): void
    {
        $svc = app(QuotationService::class);
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $c = $this->customer($b);
        $q = $svc->submit($b, $c, 'Office cleaning quote', null);
        $v = $svc->draftVersion($q, [['description' => 'Cleaning', 'quantity' => 1, 'unit_price_cents' => 100000]], null, '2026-11-17', $owner);
        $svc->approveAndSend($q->fresh(), $owner);
        $this->travel(3)->days();
        $this->expectException(RuleViolation::class);
        $svc->respond($q->fresh(), $v->id, 'accept', $c);
    }
}
