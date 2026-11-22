<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Public demo only: wipes everything and reseeds the fictional data. */
class ResetDemo extends Command
{
    protected $signature = 'vendra:demo-reset';

    protected $description = 'Reseed the fictional demo data (only when VENDRA_PUBLIC_DEMO=true)';

    public function handle(): int
    {
        if (! config('vendra.public_demo')) {
            $this->warn('Not a public demo (VENDRA_PUBLIC_DEMO is off). Nothing was changed.');

            return self::SUCCESS;
        }

        // Uploaded pictures go too, so nothing a visitor added survives the reset.
        foreach (['businesses', 'avatars'] as $dir) {
            Storage::disk('public')->deleteDirectory($dir);
        }
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->info('Demo data reset.');

        return self::SUCCESS;
    }
}
