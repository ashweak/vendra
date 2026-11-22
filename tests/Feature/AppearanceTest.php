<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Support\Appearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pages_use_the_saved_layout_and_palette_and_previews_are_safe(): void
    {
        $b = $this->makeBusiness(['layout' => 'cafe', 'palette' => 'lavender']);

        $this->get(route('public.home', $b))->assertOk()->assertSee('data-layout="cafe" data-palette="lavender"', false);
        $this->get(route('public.book', $b))->assertOk()->assertSee('data-layout="cafe" data-palette="lavender"', false);
        $this->get(route('public.home', [$b, 'palette' => 'berry']))->assertSee('data-layout="cafe" data-palette="berry"', false);
        $this->get(route('public.home', [$b, 'preview' => 'off']));
        $this->get(route('public.home', [$b, 'layout' => '"><script>x</script>', 'palette' => ['x']]))
            ->assertSee('data-layout="cafe" data-palette="lavender"', false)->assertDontSee('<script>x</script>', false);
        // The dashboard keeps Vendra's own look.
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.overview'))->assertSee('data-layout="classic" data-palette="sunny"', false);
    }

    public function test_a_preview_follows_the_visitor_to_every_page_until_turned_off(): void
    {
        $b = $this->makeBusiness(['layout' => 'cafe', 'palette' => 'lavender']);
        $other = $this->makeBusiness(['layout' => 'shop', 'palette' => 'ocean']);
        $look = fn (string $layout, string $palette) => 'data-layout="'.$layout.'" data-palette="'.$palette.'"';

        $this->get(route('public.home', [$b, 'layout' => 'panel', 'palette' => 'garden']))->assertSee($look('panel', 'garden'), false)->assertSee('Exit preview');
        foreach (['public.book', 'public.order', 'public.quote', 'public.request', 'public.track', 'public.privacy'] as $page) {
            $this->get(route($page, $b))->assertOk()->assertSee($look('panel', 'garden'), false)->assertSee('Exit preview');
        }
        // Changing one part keeps the other previewed part.
        $this->get(route('public.book', [$b, 'palette' => 'berry']))->assertSee($look('panel', 'berry'), false);
        $this->get(route('public.home', $b))->assertSee($look('panel', 'berry'), false);
        // Other businesses are not affected.
        $this->get(route('public.home', $other))->assertSee($look('shop', 'ocean'), false)->assertDontSee('Exit preview');

        $this->get(route('public.book', [$b, 'preview' => 'off']))->assertSee($look('cafe', 'lavender'), false)->assertDontSee('Exit preview');
        $this->get(route('public.home', $b))->assertSee($look('cafe', 'lavender'), false);
    }

    public function test_saving_the_look_ends_the_preview(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('public.home', [$b, 'layout' => 'brochure', 'palette' => 'coral']))->assertSee('Use this look');
        $this->actingAs($owner)->get(route('dashboard.settings.appearance', ['layout' => 'brochure', 'palette' => 'coral']))
            ->assertOk()->assertSee('value="brochure" class="peer sr-only" checked', false);
        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['layout' => 'brochure', 'palette' => 'coral'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('public.home', $b))->assertSee('data-layout="brochure" data-palette="coral"', false)->assertDontSee('Exit preview');
    }

    public function test_owner_changes_layout_and_colors_separately_and_invalid_values_are_rejected(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->assertSame([Appearance::DEFAULT_LAYOUT, Appearance::DEFAULT_PALETTE], [$b->fresh()->layout, $b->fresh()->palette]);

        $page = $this->actingAs($owner)->get(route('dashboard.settings.appearance'))->assertOk();
        foreach (array_merge(Appearance::LAYOUTS, Appearance::PALETTES) as $item) {
            $page->assertSee($item['name']);
        }

        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['layout' => 'shop', 'palette' => 'espresso'])->assertSessionHasNoErrors();
        $this->assertSame(['shop', 'espresso'], [$b->fresh()->layout, $b->fresh()->palette]);

        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['layout' => 'shop', 'palette' => 'neon'])->assertSessionHasErrors('palette');
        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['layout' => 'grid', 'palette' => 'sunny'])->assertSessionHasErrors('layout');
        $this->assertSame(['shop', 'espresso'], [$b->fresh()->layout, $b->fresh()->palette]);

        // Still changeable in the public demo (only the look changes).
        config(['vendra.public_demo' => true]);
        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['layout' => 'classic', 'palette' => 'berry'])->assertSessionHasNoErrors();
        $this->assertSame(['classic', 'berry'], [$b->fresh()->layout, $b->fresh()->palette]);
    }

    public function test_staff_cannot_change_the_appearance(): void
    {
        $b = $this->makeBusiness();
        $staff = $this->addMember($b, 'staff');

        $this->actingAs($staff)->put(route('dashboard.settings.appearance'), ['layout' => 'panel', 'palette' => 'ocean'])->assertForbidden();
        $this->assertSame(Appearance::DEFAULT_LAYOUT, $b->fresh()->layout);
    }

    public function test_layout_and_colors_are_picked_when_creating_a_business(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('onboarding.business'))->assertOk()->assertSee('Page layout')->assertSee('Lavender');

        $this->actingAs($user)->post(route('onboarding.business'), ['name' => 'Kape ni Lola', 'category' => 'general', 'layout' => 'cafe', 'palette' => 'garden'])
            ->assertSessionHasNoErrors();
        $b = Business::where('name', 'Kape ni Lola')->sole();
        $this->assertSame(['cafe', 'garden'], [$b->layout, $b->palette]);
    }

    public function test_every_palette_and_layout_has_css_and_views(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        foreach (array_keys(Appearance::PALETTES) as $key) {
            $this->assertStringContainsString('[data-palette="'.$key.'"] {', $css, "Missing CSS for palette {$key}");
        }
        foreach (array_keys(Appearance::LAYOUTS) as $key) {
            $this->assertFileExists(resource_path("views/public/home/{$key}.blade.php"));
            $this->assertArrayHasKey($key, Appearance::CHROME);
        }
    }

    public function test_old_themes_map_to_the_same_layout_and_palette(): void
    {
        $migration = require database_path('migrations/2026_11_22_000200_split_theme_into_layout_and_palette.php');
        $migration->down();
        $id = DB::table('businesses')->insertGetId(['name' => 'Old', 'slug' => 'old', 'category' => 'general', 'modules' => '[]', 'settings' => '{}', 'theme' => 'roastery', 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();
        $row = DB::table('businesses')->find($id);
        $this->assertSame(['cafe', 'espresso'], [$row->layout, $row->palette]);
    }

    public function test_every_layout_renders_the_full_catalog_in_any_palette(): void
    {
        $b = $this->makeBusiness(['address' => '1 Rizal St., Manila', 'phone' => '0917 000 0000', 'email' => 'hi@biz.test']);
        $b->catalogItems()->create(['type' => 'service', 'name' => 'Classic Haircut', 'category' => 'Hair', 'price_cents' => 25000, 'duration_minutes' => 45]);
        $b->catalogItems()->create(['type' => 'product', 'name' => 'Argan Hair Oil', 'category' => 'Retail', 'price_cents' => 39900, 'stock_on_hand' => 2]);
        $b->catalogItems()->create(['type' => 'product', 'name' => 'Sold Out Serum', 'category' => 'Retail', 'price_cents' => 10000, 'stock_on_hand' => 0]);
        $b->knowledgeEntries()->create(['kind' => 'policy', 'title' => 'Late arrivals', 'body' => 'We hold your slot for 15 minutes.', 'is_published' => true]);
        $serviceId = $b->catalogItems()->where('name', 'Classic Haircut')->value('id');
        $structures = [];
        $palettes = array_keys(Appearance::PALETTES);

        foreach (array_keys(Appearance::LAYOUTS) as $i => $key) {
            $palette = $palettes[$i % count($palettes)];
            $r = $this->get(route('public.home', [$b, 'layout' => $key, 'palette' => $palette]));
            $r->assertOk()
                ->assertSee('data-layout="'.$key.'" data-palette="'.$palette.'"', false)
                ->assertSee('Classic Haircut')->assertSee('Argan Hair Oil')->assertSee('₱250.00')->assertSee('45 min')->assertSee('Only 2 left')
                ->assertSee(route('public.book', [$b, 'service' => $serviceId]), false)
                ->assertSee('Late arrivals')->assertSee('1 Rizal St., Manila')
                ->assertSee(route('public.privacy', $b), false)
                ->assertSee('data-open-chat', false)
                ->assertDontSee('Order Sold Out Serum', false)
                ->assertDontSee('__raw_block', false)->assertDontSee('$item', false)->assertDontSee('@php', false);
            $this->assertSame(1, substr_count($r->getContent(), '<h1'), "Layout {$key} must have exactly one h1");

            $this->get(route('public.home', [$b, 'layout' => $key, 'q' => 'zzz-nothing']))->assertOk()->assertSee('Nothing found');
            $this->get(route('public.book', [$b, 'layout' => $key]))->assertOk()->assertSee('data-layout="'.$key.'"', false);

            $structures[$key] = md5(preg_replace('/\s+/', '', strip_tags($r->getContent(), '<section><article><aside><div>')));
        }
        $this->assertCount(count(Appearance::LAYOUTS), array_unique($structures), 'Each layout should render a different page structure');

        // The palette changes colors only: same layout with another palette → same page structure.
        $structure = fn (string $palette) => md5(preg_replace('/\s+/', '', strip_tags(
            $this->get(route('public.home', [$b, 'layout' => 'shop', 'palette' => $palette]))->getContent(), '<section><article><aside><div>')));
        $this->assertSame($structure('sunny'), $structure('berry'));
    }

    public function test_every_layout_works_with_an_empty_catalog(): void
    {
        $b = $this->makeBusiness();
        foreach (array_keys(Appearance::LAYOUTS) as $key) {
            $this->get(route('public.home', [$b, 'layout' => $key]))->assertOk()->assertSee('Nothing found');
        }
    }
}
