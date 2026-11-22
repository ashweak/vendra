<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Support\Themes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pages_use_the_business_theme_and_previews_are_safe(): void
    {
        $b = $this->makeBusiness(['theme' => 'roastery']);

        $this->get(route('public.home', $b))->assertOk()->assertSee('data-theme="roastery"', false);
        $this->get(route('public.book', $b))->assertOk()->assertSee('data-theme="roastery"', false);
        $this->get(route('public.home', [$b, 'theme' => 'ocean']))->assertSee('data-theme="ocean"', false);
        $this->get(route('public.home', [$b, 'theme' => '"><script>x</script>']))->assertSee('data-theme="roastery"', false)->assertDontSee('<script>x</script>', false);
        // The dashboard keeps Vendra's own look.
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.overview'))->assertSee('data-theme="studio"', false);
    }

    public function test_owner_changes_the_theme_anytime_and_unknown_themes_are_rejected(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->assertSame(Themes::DEFAULT, $b->fresh()->theme);

        $this->actingAs($owner)->get(route('dashboard.settings.appearance'))->assertOk()
            ->assertSee('Market')->assertSee('Roastery')->assertSee(route('public.home', [$b, 'theme' => 'slate']), false);

        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['theme' => 'market'])->assertSessionHasNoErrors();
        $this->assertSame('market', $b->fresh()->theme);

        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['theme' => 'neon'])->assertSessionHasErrors('theme');
        $this->assertSame('market', $b->fresh()->theme);

        // Themes stay changeable in the public demo (only colors and fonts change).
        config(['vendra.public_demo' => true]);
        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['theme' => 'coral'])->assertSessionHasNoErrors();
        $this->assertSame('coral', $b->fresh()->theme);
    }

    public function test_staff_cannot_change_the_theme(): void
    {
        $b = $this->makeBusiness();
        $staff = $this->addMember($b, 'staff');

        $this->actingAs($staff)->put(route('dashboard.settings.appearance'), ['theme' => 'ocean'])->assertForbidden();
        $this->assertSame(Themes::DEFAULT, $b->fresh()->theme);
    }

    public function test_theme_is_picked_when_creating_a_business(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('onboarding.business'))->assertOk()->assertSee('Website look');

        $this->actingAs($user)->post(route('onboarding.business'), ['name' => 'Kape ni Lola', 'category' => 'general', 'theme' => 'roastery'])
            ->assertSessionHasNoErrors();
        $this->assertSame('roastery', Business::where('name', 'Kape ni Lola')->value('theme'));
    }

    public function test_every_theme_has_css_tokens(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        foreach (Themes::keys() as $key) {
            if ($key === Themes::DEFAULT) {
                continue;
            }
            $this->assertStringContainsString('[data-theme="'.$key.'"] {', $css, "Missing CSS for theme {$key}");
        }
    }
}
