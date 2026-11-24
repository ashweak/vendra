<?php

namespace Tests\Feature;

use App\Models\AssistantGap;
use App\Models\CatalogItem;
use App\Notifications\OwnerDailySummary;
use App\Services\Ai\AssistantService;
use App\Services\ConversationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OwnerToolsTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $body): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('catalog.csv', $body);
    }

    public function test_catalog_import_checks_every_row_first(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->service($b, ['name' => 'Haircut']);
        $this->actingAs($owner)->get(route('dashboard.catalog.import'))->assertOk()->assertSee('Download the template');
        $this->actingAs($owner)->get(route('dashboard.catalog.import.template'))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        // One bad row: nothing is imported, every problem is listed.
        $bad = "type,name,price,duration_minutes,stock\nservice,Hair Spa,600,60,\nproduct,Argan Oil,abc,,5\ngadget,Thing,10,,\n";
        $this->actingAs($owner)->post(route('dashboard.catalog.import.store'), ['file' => $this->csv($bad)])
            ->assertSessionHasErrors('file')->assertSessionHas('import', fn ($r) => count($r['errors']) === 2
                && str_starts_with($r['errors'][0], 'Row 3:') && str_starts_with($r['errors'][1], 'Row 4:'));
        $this->assertSame(1, CatalogItem::count());

        // Excel-style file: BOM, semicolons, peso signs, a formula, a duplicate and a blank line.
        $good = "\xEF\xBB\xBFType;Name;Category;Price;Duration;Stock;Description;Active\n"
            ."service;Hair Spa;Hair;₱1,250.50;90;;;yes\n"
            ."product;=Argan Oil;Hair care;450;;12;;\n\n"
            ."service;haircut;Hair;250;45;;;yes\n"
            ."product;Old Stock;;99;;;;no\n";
        $this->actingAs($owner)->post(route('dashboard.catalog.import.store'), ['file' => $this->csv($good)])
            ->assertRedirect(route('dashboard.catalog.index'))->assertSessionHas('status', 'Imported 3 items. Skipped 1 already in your catalog.');
        $spa = CatalogItem::where('name', 'Hair Spa')->sole();
        $this->assertSame(125050, $spa->price_cents);
        $this->assertSame(90, $spa->duration_minutes);
        $oil = CatalogItem::where('name', 'Argan Oil')->sole();
        $this->assertSame(12, $oil->stock_on_hand);
        $this->assertNull($oil->duration_minutes);
        $this->assertFalse(CatalogItem::where('name', 'Old Stock')->sole()->is_active);
        $this->assertSame($b->id, $spa->business_id);

        $staff = $this->addMember($b, 'staff');
        $this->actingAs($staff)->post(route('dashboard.catalog.import.store'), ['file' => $this->csv($good)])->assertForbidden();
    }

    public function test_owners_get_a_morning_summary_once_a_day(): void
    {
        Notification::fake();
        $b = $this->makeBusiness(['name' => 'Lumina']);
        $owner = $this->addMember($b, 'owner');
        $staff = $this->addMember($b, 'staff');
        $s = $this->service($b, ['name' => 'Haircut']);
        $this->product($b, ['name' => 'Argan Oil'], 2);
        $c = $this->customer($b, 'ana@example.test');
        $start = CarbonImmutable::parse('2026-11-16 14:00', 'Asia/Manila')->utc();
        $a = new \App\Models\Appointment(['customer_id' => $c->id, 'service_name' => 'Haircut', 'price_cents' => 25000, 'starts_at' => $start, 'ends_at' => $start->addHour(), 'status' => 'pending_approval']);
        $a->business_id = $b->id;
        $a->save();
        [$conv] = app(ConversationService::class)->start($b);
        app(ConversationService::class)->addMessage($conv, 'customer', 'Do you sell gift cards?');
        app(AssistantService::class)->reply($conv->fresh(), 'Do you sell gift cards?');
        $quiet = $this->makeBusiness();
        $this->addMember($quiet, 'owner');

        $this->travelTo(CarbonImmutable::parse('2026-11-16 06:10', 'Asia/Manila'));
        $this->artisan('vendra:daily-summary')->expectsOutput('Daily summaries sent: 0');
        $this->travelTo(CarbonImmutable::parse('2026-11-16 07:02', 'Asia/Manila'));
        $this->artisan('vendra:daily-summary')->expectsOutput('Daily summaries sent: 1');
        $this->artisan('vendra:daily-summary')->expectsOutput('Daily summaries sent: 0');

        Notification::assertSentTo($owner, OwnerDailySummary::class, function ($n) {
            $mail = $n->toMail(new AnonymousNotifiable);
            $text = implode("\n", array_merge($mail->introLines, $mail->outroLines));

            return $mail->subject === 'Lumina today: 1 booking, 1 to approve'
                && str_contains($text, '2:00 PM · Haircut · ')
                && str_contains($text, '1 waiting for your approval')
                && str_contains($text, 'Low stock: Argan Oil (2 left)')
                && str_contains($text, '1 unanswered question for your FAQ');
        });
        Notification::assertNotSentTo($staff, OwnerDailySummary::class);
        $this->assertSame(1, AssistantGap::count());
    }

    public function test_owner_can_turn_the_morning_summary_off(): void
    {
        Notification::fake();
        $b = $this->makeBusiness([], ['daily_summary' => false]);
        $this->addMember($b, 'owner');
        $this->product($b, ['name' => 'Argan Oil'], 1);
        $this->artisan('vendra:daily-summary', ['--now' => true])->expectsOutput('Daily summaries sent: 0');
        Notification::assertNothingSent();
    }
}
