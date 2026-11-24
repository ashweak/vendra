<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Notifications\OwnerDailySummary;
use App\Services\DailySummary;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** Runs hourly; each active business gets one summary at 7 AM in its own timezone (owners only). */
class SendDailySummaries extends Command
{
    protected $signature = 'vendra:daily-summary {--now : Send now regardless of the local time (for testing)}';

    protected $description = 'Email owners a morning summary of today’s bookings and what needs attention';

    public function handle(): int
    {
        $sent = 0;
        Business::query()->where('status', 'active')->whereNotNull('activated_at')->each(function (Business $b) use (&$sent) {
            $local = CarbonImmutable::now($b->timezone);
            if (! $this->option('now') && $local->hour !== 7) {
                return;
            }
            if (! $b->setting('daily_summary', true)) {
                return;
            }
            // One per business per local day, even if the scheduler runs twice.
            if (! Cache::add('daily-summary:'.$b->id.':'.$local->toDateString(), 1, now()->addDay())) {
                return;
            }
            $summary = DailySummary::for($b, $local);
            if (DailySummary::isEmpty($summary)) {
                return; // nothing to say: no email
            }
            $owners = $b->memberships()->where('role', 'owner')->where('is_active', true)->with('user')->get()->pluck('user')->filter();
            foreach ($owners as $owner) {
                try {
                    $owner->notify(new OwnerDailySummary($b, $summary));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning("Daily summary not sent for business {$b->id}: ".$e->getMessage());
                }
            }
        });
        $this->info("Daily summaries sent: {$sent}");

        return self::SUCCESS;
    }
}
