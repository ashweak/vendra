<?php

namespace Tests\Feature;

use App\Models\BusinessPhoto;
use App\Support\Appearance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** A real JPEG carrying an EXIF block with a fake GPS marker, to prove metadata is dropped. */
    private function jpegWithMetadata(string $name = 'shop.jpg', int $w = 1200, int $h = 800): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 240, 180, 60));
        ob_start();
        imagejpeg($img, null, 85);
        $jpeg = (string) ob_get_clean();
        $exif = "Exif\0\0".'MM'."\0\x2A\0\0\0\x08\0\0".str_repeat("\0", 4).'GPS-SECRET-14.5995N-120.9842E';
        $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, substr($jpeg, 0, 2).$app1.substr($jpeg, 2));

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    public function test_owner_uploads_logo_and_cover_and_they_are_cleaned_and_shown(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.settings.photos'))->assertOk()->assertSee('Cover photo')->assertSee('Gallery');

        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'cover'), ['photo' => $this->jpegWithMetadata()])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'logo'), ['photo' => UploadedFile::fake()->image('logo.png', 900, 600)])->assertSessionHasNoErrors();
        $b->refresh();

        $cover = Storage::disk('public')->get($b->cover_path);
        $this->assertStringNotContainsString('GPS-SECRET', $cover);
        $this->assertStringNotContainsString('Exif', $cover);
        [$w, $h, $type] = getimagesizefromstring($cover);
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertLessThanOrEqual(2000, $w);
        [$lw, $lh] = getimagesizefromstring(Storage::disk('public')->get($b->logo_path));
        $this->assertSame([512, 512], [$lw, $lh], 'Logos are cropped square');

        foreach (array_keys(Appearance::LAYOUTS) as $layout) {
            $this->get(route('public.home', [$b, 'layout' => $layout]))->assertOk()
                ->assertSee($b->coverUrl(), false)->assertSee($b->logoUrl(), false);
        }

        // Replacing deletes the old file; removing clears it.
        $old = $b->cover_path;
        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'cover'), ['photo' => UploadedFile::fake()->image('c2.jpg', 800, 500)]);
        Storage::disk('public')->assertMissing($old);
        $this->actingAs($owner)->delete(route('dashboard.settings.photos.remove', 'cover'))->assertSessionHasNoErrors();
        $this->assertNull($b->fresh()->cover_path);
    }

    public function test_gallery_upload_caption_limit_and_delete(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $files = fn (int $n) => array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg", 640, 480), range(1, $n));

        $this->actingAs($owner)->post(route('dashboard.settings.photos.gallery'), ['photos' => $files(6)])->assertSessionHasNoErrors();
        $this->assertSame(6, $b->photos()->count());
        $this->actingAs($owner)->post(route('dashboard.settings.photos.gallery'), ['photos' => $files(3)])->assertSessionHasErrors('photos');
        $this->actingAs($owner)->post(route('dashboard.settings.photos.gallery'), ['photos' => $files(2)])->assertSessionHasNoErrors();
        $this->assertSame(BusinessPhoto::MAX, $b->photos()->count());
        $this->actingAs($owner)->post(route('dashboard.settings.photos.gallery'), ['photos' => $files(1)])->assertSessionHasErrors('photos');

        $photo = $b->photos()->first();
        $this->actingAs($owner)->put(route('dashboard.settings.photos.gallery.update', $photo->id), ['caption' => 'Our new <b>chairs</b>'])->assertSessionHasNoErrors();
        foreach (array_keys(Appearance::LAYOUTS) as $layout) {
            $this->get(route('public.home', [$b, 'layout' => $layout]))->assertOk()
                ->assertSee($photo->url(), false)->assertSee('Our new &lt;b&gt;chairs&lt;/b&gt;', false);
        }

        $this->actingAs($owner)->delete(route('dashboard.settings.photos.gallery.destroy', $photo->id))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($photo->path);
        $this->assertSame(BusinessPhoto::MAX - 1, $b->photos()->count());

        // Another business's photo can't be touched.
        $other = $this->makeBusiness();
        $foreign = $other->photos()->create(['path' => 'x.webp', 'sort' => 1]);
        $this->actingAs($owner)->delete(route('dashboard.settings.photos.gallery.destroy', $foreign->id))->assertNotFound();
    }

    public function test_non_images_and_fake_images_are_rejected(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');

        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'cover'), ['photo' => UploadedFile::fake()->create('menu.pdf', 20, 'application/pdf')])
            ->assertSessionHasErrors('photo');
        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'cover'), ['photo' => UploadedFile::fake()->createWithContent('shell.jpg', '<?php echo 1; ?>')])
            ->assertSessionHasErrors('photo');
        $this->actingAs($owner)->post(route('dashboard.settings.photos.upload', 'logo'), ['photo' => UploadedFile::fake()->image('big.jpg')->size(6000)])
            ->assertSessionHasErrors('photo');
        $this->assertNull($b->fresh()->cover_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_staff_cannot_manage_photos_and_the_public_demo_blocks_uploads(): void
    {
        $b = $this->makeBusiness();
        $staff = $this->addMember($b, 'staff');
        $this->actingAs($staff)->get(route('dashboard.settings.photos'))->assertForbidden();
        $this->actingAs($staff)->post(route('dashboard.settings.photos.upload', 'cover'), ['photo' => UploadedFile::fake()->image('c.jpg')])->assertForbidden();

        config(['vendra.public_demo' => true]);
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->post(route('dashboard.settings.photos.gallery'), ['photos' => [UploadedFile::fake()->image('c.jpg')]]);
        $this->actingAs($owner)->post(route('dashboard.account.avatar'), ['avatar' => UploadedFile::fake()->image('me.jpg')]);
        $this->assertSame(0, $b->photos()->count());
        $this->assertNull($owner->fresh()->avatar_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_catalog_item_photo_upload_replace_and_remove(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->post(route('dashboard.catalog.store'), [
            'type' => 'product', 'name' => 'Argan Oil', 'price' => '399', 'is_active' => '1', 'image' => UploadedFile::fake()->image('oil.jpg', 1600, 1600),
        ])->assertSessionHasNoErrors();
        $item = $b->catalogItems()->where('name', 'Argan Oil')->sole();
        $this->assertNotNull($item->image_path);
        [$w] = getimagesizefromstring(Storage::disk('public')->get($item->image_path));
        $this->assertSame(1000, $w);
        foreach (array_keys(Appearance::LAYOUTS) as $layout) {
            $this->get(route('public.home', [$b, 'layout' => $layout]))->assertOk()->assertSee($item->imageUrl(), false);
        }

        // A bad picture doesn't save a half-finished item.
        $this->actingAs($owner)->post(route('dashboard.catalog.store'), ['type' => 'product', 'name' => 'Broken', 'price' => '1', 'image' => UploadedFile::fake()->create('x.txt', 1)])
            ->assertSessionHasErrors('image');
        $this->assertFalse($b->catalogItems()->where('name', 'Broken')->exists());

        $old = $item->image_path;
        $this->actingAs($owner)->put(route('dashboard.catalog.update', $item->id), ['name' => 'Argan Oil', 'price' => '399', 'is_active' => '1', 'image' => UploadedFile::fake()->image('n.png', 300, 300)])
            ->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $this->actingAs($owner)->put(route('dashboard.catalog.update', $item->id), ['name' => 'Argan Oil', 'price' => '399', 'is_active' => '1', 'remove_image' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertNull($item->fresh()->image_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_profile_picture_upload_and_remove(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.account'))->assertOk()->assertSee('Profile picture');

        $this->actingAs($owner)->post(route('dashboard.account.avatar'), ['avatar' => $this->jpegWithMetadata('me.jpg', 900, 1200)])->assertSessionHasNoErrors();
        $path = $owner->fresh()->avatar_path;
        $bytes = Storage::disk('public')->get($path);
        $this->assertStringNotContainsString('GPS-SECRET', $bytes);
        $this->assertSame([400, 400], array_slice(getimagesizefromstring($bytes), 0, 2));
        $this->actingAs($owner)->get(route('dashboard.overview'))->assertSee($owner->fresh()->avatarUrl(), false);

        $this->actingAs($owner)->delete(route('dashboard.account.avatar.remove'))->assertSessionHasNoErrors();
        $this->assertNull($owner->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }
}
