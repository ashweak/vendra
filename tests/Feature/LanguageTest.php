<?php

namespace Tests\Feature;

use App\Support\Appearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_switch_the_customer_pages_to_filipino_and_it_sticks(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b, ['name' => 'Haircut', 'duration_minutes' => 45]);
        $this->get(route('public.home', $b))->assertOk()->assertSee('<html lang="en"', false)->assertSee('Book an appointment')->assertSee('hreflang="fil"', false);

        $this->get(route('public.home', [$b, 'lang' => 'fil']))->assertOk()->assertSee('<html lang="fil"', false)
            ->assertSee('Mag-book ng appointment')->assertSee('45 min')->assertDontSee('Book an appointment');
        $this->get(route('public.book', $b))->assertOk()->assertSee('Mag-book ng appointment')->assertSee('Pumili ng serbisyo…')
            ->assertSee('Tinitingnan ang mga bakanteng oras…', false);
        $this->get(route('public.track', $b))->assertSee('I-track ang request ko');
        $this->get(route('public.privacy', $b))->assertSee('Iyong mga karapatan');

        // Review step: labels, dates and validation messages in Filipino.
        $this->post(route('public.book.review', $b), ['catalog_item_id' => $s->id, 'date' => '2026-11-16', 'time' => '10:00', 'name' => 'Ana', 'email' => 'ana@example.test'])
            ->assertOk()->assertSee('Suriin at kumpirmahin')->assertSee('Kailan')->assertSee('Lunes, Nobyembre 16, 2026 at 10:00 AM');
        $this->post(route('public.book.review', $b), ['catalog_item_id' => $s->id, 'date' => '2026-11-16', 'time' => '10:00', 'email' => 'ana@example.test'])
            ->assertSessionHasErrors(['name' => 'Kailangan ang pangalan.']);

        $this->get(route('public.home', [$b, 'lang' => 'en']))->assertSee('Book an appointment');
        $this->get(route('public.book', $b))->assertSee('Choose a service…');
    }

    public function test_every_layout_renders_in_filipino(): void
    {
        $b = $this->makeBusiness();
        $this->service($b);
        $this->product($b, ['name' => 'Argan Oil'], 2);
        $this->get(route('public.home', [$b, 'lang' => 'fil']));
        foreach (array_keys(Appearance::LAYOUTS) as $layout) {
            $html = $this->get(route('public.home', [$b, 'layout' => $layout]))->assertOk()->getContent();
            $this->assertStringContainsString('<html lang="fil"', $html, $layout);
            $this->assertStringContainsString('Magtanong sa amin', $html, $layout); // chat button
            $this->assertStringContainsString('2 na lang ang natitira', $html, $layout);
            $this->assertStringNotContainsString('Only 2 left', $html, $layout);
        }
    }

    public function test_business_default_language_and_browser_preference(): void
    {
        $fil = $this->makeBusiness([], ['language' => 'fil']);
        $en = $this->makeBusiness();
        $this->get(route('public.home', $fil))->assertSee('<html lang="fil"', false);
        $this->get(route('public.home', $en))->assertSee('<html lang="en"', false);
        $this->withHeader('Accept-Language', 'fil-PH,fil;q=0.9,en;q=0.8')->get(route('public.home', $en))->assertSee('<html lang="fil"', false);
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get(route('public.home', $fil))->assertSee('<html lang="fil"', false);

        $owner = $this->addMember($en, 'owner');
        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['layout' => 'classic', 'palette' => 'sunny', 'language' => 'fil'])->assertSessionHasNoErrors();
        $this->assertSame('fil', $en->fresh()->setting('language'));
        $this->actingAs($owner)->put(route('dashboard.settings.appearance'), ['layout' => 'classic', 'palette' => 'sunny', 'language' => 'xx'])->assertSessionHasErrors('language');
        // The dashboard stays in English.
        $this->actingAs($owner)->get(route('dashboard.overview'))->assertSee('<html lang="en"', false);
    }

    public function test_every_filipino_translation_keeps_its_placeholders(): void
    {
        $fil = json_decode(file_get_contents(lang_path('fil.json')), true);
        $this->assertGreaterThan(400, count($fil));
        foreach ($fil as $en => $tl) {
            preg_match_all('/:[a-zA-Z]+/', $en, $a);
            preg_match_all('/:[a-zA-Z]+/', $tl, $b);
            sort($a[0]);
            sort($b[0]);
            $this->assertSame($a[0], $b[0], $en);
        }
    }
}
