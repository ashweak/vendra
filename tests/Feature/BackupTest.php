<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Services\Backup;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Uses real migrations (not a wrapping transaction), the same way a restore runs in production. */
class BackupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backup_is_encrypted_and_restores_data_and_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $b = $this->makeBusiness(['name' => 'Lumina Secret Studio']);
        $this->addMember($b, 'owner');
        $this->customer($b, 'ana.private@example.test');
        Storage::disk('public')->put('businesses/'.$b->id.'/cover.webp', 'PICTURE-BYTES');
        Storage::disk('local')->put('attachments/'.$b->id.'/plan', 'PDF-BYTES');

        $this->artisan('vendra:backup')->assertSuccessful()->expectsOutputToContain('Backup saved: backups/vendra-');
        $name = app(Backup::class)->list()[0];
        $raw = Storage::disk('local')->get($name);
        $this->assertStringStartsWith("VNDRBK1\n", $raw);
        foreach (['Lumina Secret Studio', 'ana.private@example.test', 'PICTURE-BYTES'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
        $this->assertSame('vendra', app(Backup::class)->manifest($name)['app']);

        // Disaster: data and files change or disappear.
        Business::query()->update(['name' => 'Overwritten']);
        \App\Models\Customer::query()->delete();
        Storage::disk('public')->delete('businesses/'.$b->id.'/cover.webp');
        Storage::disk('public')->put('stray.txt', 'x');

        $this->artisan('vendra:restore', ['--force' => true])->assertSuccessful()->expectsOutputToContain('Restored');
        $this->assertSame('Lumina Secret Studio', $b->fresh()->name);
        $this->assertTrue(\App\Models\Customer::where('email', 'ana.private@example.test')->exists());
        $this->assertSame('PICTURE-BYTES', Storage::disk('public')->get('businesses/'.$b->id.'/cover.webp'));
        $this->assertSame('PDF-BYTES', Storage::disk('local')->get('attachments/'.$b->id.'/plan'));
        Storage::disk('public')->assertMissing('stray.txt');
        Storage::disk('local')->assertExists($name); // restoring never deletes the backups themselves
    }

    public function test_tampered_or_wrong_key_backups_are_refused_without_changing_anything(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $b = $this->makeBusiness(['name' => 'Original']);
        $this->artisan('vendra:backup')->assertSuccessful();
        $name = app(Backup::class)->list()[0];
        $b->update(['name' => 'Current']);

        $raw = Storage::disk('local')->get($name);
        $raw[strlen($raw) - 5] = chr(ord($raw[strlen($raw) - 5]) ^ 1);
        Storage::disk('local')->put($name, $raw);
        $this->artisan('vendra:restore', ['backup' => $name, '--force' => true])->assertFailed();
        $this->assertSame('Current', $b->fresh()->name);

        $this->artisan('vendra:backup')->assertSuccessful();
        config(['vendra.backup_key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->artisan('vendra:restore', ['--force' => true])->assertFailed()->expectsOutputToContain('wrong key');
        $this->assertSame('Current', $b->fresh()->name);
    }

    public function test_old_backups_are_pruned(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['vendra.backups_keep' => 2]);
        foreach (range(1, 3) as $i) {
            $this->travel(1)->minutes();
            $this->artisan('vendra:backup')->assertSuccessful();
        }
        $this->assertCount(2, app(Backup::class)->list());
        $this->artisan('vendra:backup', ['--key' => true])->expectsOutputToContain('VENDRA_BACKUP_KEY=base64:');
    }
}
