<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Services\ImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_pictures_get_smaller_copies_listed_in_srcset_and_deleted_together(): void
    {
        Storage::fake('public');
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'cover'), ['photo' => UploadedFile::fake()->image('c.jpg', 2400, 1500)])->assertSessionHasNoErrors();
        $path = $b->fresh()->cover_path;

        $this->assertMatchesRegularExpression('#^businesses/\d+/\w{32}-1920w\.webp$#', $path);
        $variants = ImageStore::variants($path);
        $this->assertSame([480, 960], array_keys($variants));
        foreach ($variants as $w => $v) {
            Storage::disk('public')->assertExists($v);
            $this->assertSame($w, getimagesizefromstring(Storage::disk('public')->get($v))[0]);
        }
        $html = $this->get(route('public.home', [$b, 'layout' => 'brochure']))->assertOk()->getContent();
        $this->assertStringContainsString(e(ImageStore::url($variants[480])).' 480w', $html);
        $this->assertStringContainsString(e(ImageStore::url($path)).' 1920w', $html);
        $this->assertStringContainsString('sizes="(min-width: 1152px) 1152px, 100vw"', $html);

        // Small pictures get no copies and no srcset.
        $this->assertSame([], ImageStore::variants('avatars/'.Str::random(32).'-400w.webp'));
        $this->assertSame('', (string) ImageStore::attrs('avatars/x-400w.webp', '40px'));

        $this->actingAs($owner)->delete(route('dashboard.settings.photos.remove', 'cover'));
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_pictures_can_live_on_cloudflare_r2(): void
    {
        config(['vendra.media_disk' => 'r2', 'filesystems.disks.r2.url' => 'https://media.example.com']);
        Storage::fake('r2', ['url' => 'https://media.example.com']);
        Storage::fake('public');
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->post(route('dashboard.settings.photos.gallery'), ['photos' => [UploadedFile::fake()->image('g.jpg', 1200, 900)]])->assertSessionHasNoErrors();
        $photo = $b->photos()->first();

        Storage::disk('r2')->assertExists($photo->path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
        $this->assertStringStartsWith('https://media.example.com/businesses/', $photo->url());
        $res = $this->get(route('public.home', $b))->assertOk()->assertSee($photo->url(), false);
        $this->assertStringContainsString("img-src 'self' data: https://media.example.com", $res->headers->get('Content-Security-Policy'));

        $this->actingAs($owner)->delete(route('dashboard.settings.photos.gallery.destroy', $photo->id));
        $this->assertEmpty(Storage::disk('r2')->allFiles());
    }

    public function test_customer_attachments_go_to_the_private_files_disk(): void
    {
        config(['vendra.files_disk' => 'r2_private']);
        Storage::fake('r2_private');
        Storage::fake('local');
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $payload = ['requirements' => 'Deep cleaning for a 2-bedroom condo next week', 'name' => 'Ana Cruz', 'email' => 'ana@example.test'];

        $review = $this->post(route('public.quote.review', $b), $payload + ['attachment' => UploadedFile::fake()->create('floor plan.pdf', 120, 'application/pdf')])->assertOk();
        preg_match('/name="attachment_token" value="([^"]+)"/', $review->getContent(), $m);
        $this->assertNotEmpty($m[1] ?? null);
        $this->post(route('public.quote.store', $b), $payload + ['attachment_token' => $m[1], 'idempotency_key' => (string) Str::uuid(), 'confirmed' => '1'])->assertRedirect();

        $a = Attachment::sole();
        Storage::disk('r2_private')->assertExists($a->path);
        $this->assertEmpty(Storage::disk('local')->allFiles(), 'the temporary copy is removed');

        $file = $this->get(route('public.my.file', [$b, $a->id]))->assertOk();
        $this->assertStringContainsString('inline', $file->headers->get('Content-Disposition'));
        $this->assertStringContainsString('floor plan.pdf', $file->headers->get('Content-Disposition'));
        $this->actingAs($owner)->get(route('dashboard.quotations.file', [$a->attachable_id, $a->id]))->assertOk();

        // Someone else's session can't open it.
        $this->flushSession();
        auth()->logout();
        $this->get(route('public.my.file', [$b, $a->id]))->assertNotFound();
    }
}
