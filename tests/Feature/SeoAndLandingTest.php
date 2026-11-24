<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\KnowledgeEntry;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoAndLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_and_sitemap_list_only_live_public_pages(): void
    {
        $live = $this->makeBusiness(['name' => 'Live Shop']);
        $setup = $this->makeBusiness(['name' => 'Not Ready', 'activated_at' => null]);

        $robots = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=utf-8')->getContent();
        $this->assertStringContainsString('Disallow: /app', $robots);
        $this->assertStringContainsString('Disallow: /b/*/my', $robots);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $robots);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8')->getContent();
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'valid XML');
        $this->assertStringContainsString('<loc>'.route('public.home', $live).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('public.book', $live).'</loc>', $xml);
        $this->assertStringContainsString('hreflang="fil" href="'.route('public.home', $live).'?lang=fil"', $xml);
        $this->assertStringNotContainsString(route('public.home', $setup), $xml);
        $this->assertStringNotContainsString('/app', $xml);
    }

    public function test_business_page_has_schema_canonical_and_language_links(): void
    {
        Storage::fake('public');
        $b = $this->makeBusiness(['name' => 'Lumina </script> Studio', 'phone' => '0917 000 0101', 'address' => '12 Sampaguita St.']);
        $this->service($b, ['name' => 'Haircut', 'price_cents' => 25000]);
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'cover'), ['photo' => UploadedFile::fake()->image('c.jpg', 1200, 700)]);
        auth()->logout();

        $html = $this->get(route('public.home', [$b, 'layout' => 'panel']))->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.route('public.home', $b).'">', $html);
        $this->assertStringContainsString('hreflang="fil" href="'.route('public.home', $b).'?lang=fil"', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.$b->fresh()->coverUrl().'">', $html);
        $this->assertStringNotContainsString('<meta name="robots"', $html);

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $data = json_decode($m[1], true);
        $this->assertSame('BeautySalon', $data['@type']);
        $this->assertSame('Lumina </script> Studio', $data['name']);
        $this->assertStringNotContainsString('</script> Studio', $m[1], 'the name cannot end the script tag');
        $this->assertSame('0917 000 0101', $data['telephone']);
        $this->assertSame('12 Sampaguita St.', $data['address']['streetAddress']);
        $this->assertSame(['@type' => 'Offer', 'price' => '250.00', 'priceCurrency' => 'PHP', 'itemOffered' => ['@type' => 'Service', 'name' => 'Haircut']],
            $data['hasOfferCatalog']['itemListElement'][0]);
        $this->assertNotEmpty($data['openingHoursSpecification']);

        // Private and one-off pages are kept out of search results.
        $this->post(route('public.quote.review', $b), ['requirements' => 'Bridal hair for five people on Dec 14', 'name' => 'Ana', 'email' => 'ana@example.test'])
            ->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
        $setup = $this->makeBusiness(['status' => 'setup']);
        $this->get(route('public.home', $setup))->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_landing_shows_demo_photos_and_switches_to_filipino(): void
    {
        Storage::fake('public');
        $b = $this->makeBusiness(['name' => 'Photo Shop']);
        $plain = $this->makeBusiness(['name' => 'Plain Shop']);
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'cover'), ['photo' => UploadedFile::fake()->image('c.jpg', 1200, 700)]);
        auth()->logout();

        $this->get('/')->assertOk()->assertSee($b->fresh()->coverUrl(), false)->assertSee('Plain Shop')
            ->assertSee('hreflang="fil"', false)->assertSee('Walk into a demo shop');
        $this->get('/?lang=fil')->assertOk()->assertSee('<html lang="fil"', false)
            ->assertSee('Pumasok sa isang demo shop')->assertSee('Magsimula nang libre')->assertSee('HAKBANG 1');
        $this->get('/?lang=en')->assertSee('Walk into a demo shop');
    }

    public function test_upload_fields_shrink_big_photos_in_the_browser(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.settings.photos'))->assertSee('data-shrink="2400"', false)->assertSee('js/shrink-images.js', false);
        $this->actingAs($owner)->get(route('dashboard.account'))->assertSee('data-shrink="1200"', false);
        $this->actingAs($owner)->get(route('dashboard.catalog.create'))->assertSee('data-shrink="2000"', false);
        $this->get(route('public.quote', $b))->assertSee('data-shrink-jpeg', false);
        $this->assertFileExists(public_path('js/shrink-images.js'));
    }

    public function test_staff_get_reply_suggestions_from_faq_prices_and_hours(): void
    {
        $b = $this->makeBusiness();
        $this->service($b, ['name' => 'Haircut', 'price_cents' => 25000, 'duration_minutes' => 45]);
        $e = new KnowledgeEntry(['kind' => 'faq', 'title' => 'Parking', 'body' => 'Free street parking in front.', 'is_published' => true]);
        $e->business_id = $b->id;
        $e->save();
        $owner = $this->addMember($b, 'owner', \App\Models\User::factory()->create(['name' => 'Lara Mendoza']));
        /** @var Conversation $c */
        [$c] = app(ConversationService::class)->start($b);
        app(ConversationService::class)->addMessage($c, 'customer', 'Magkano po haircut at may parking ba? Anong oras kayo bukas?');

        $html = $this->actingAs($owner)->get(route('dashboard.conversations.show', $c->id))->assertOk()->getContent();
        $this->assertStringContainsString('Suggested replies', $html);
        $this->assertStringContainsString('data-suggest="Free street parking in front."', $html);
        $this->assertStringContainsString('Haircut: ₱250.00 (45 min)', $html);
        $this->assertStringContainsString('Ang oras po namin:', $html);
        $this->assertStringContainsString('Si Lara po ito mula sa', $html);
    }
}
