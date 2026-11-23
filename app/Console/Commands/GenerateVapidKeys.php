<?php

namespace App\Console\Commands;

use App\Services\WebPush;
use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'vendra:vapid';

    protected $description = 'Create the key pair for dashboard push alerts (add the printed lines to your environment)';

    public function handle(): int
    {
        $keys = WebPush::generateKeys();
        $this->line('VAPID_PUBLIC_KEY='.$keys['public']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['private']);
        $this->line('VAPID_SUBJECT=mailto:you@example.com');
        $this->newLine();
        $this->comment('Keep the private key secret. Changing the keys turns alerts off on devices that already enabled them.');

        return self::SUCCESS;
    }
}
