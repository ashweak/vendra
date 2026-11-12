<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\CustomerAccessToken;
use App\Models\Business;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Applies the provisional retention settings (P7). These are product settings, not legal retention
 * periods. Transaction records are NOT deleted here – their retention is decided before real-data launch.
 */
class ApplyRetention extends Command
{
    protected $signature = 'vendra:retention {--dry-run}';

    protected $description = 'Remove data past its provisional retention period';

    public function handle(): int
    {
        $r = config('vendra.retention');
        $dry = $this->option('dry-run');
        $report = [];

        $convs = Conversation::where('last_activity_at', '<', now()->subMonths($r['conversations_months']));
        $report['conversations'] = $convs->count();
        $dry || $convs->each(fn ($c) => $c->delete());

        $files = Attachment::where('expires_at', '<', now())->whereHasMorph('attachable', [\App\Models\Quotation::class], fn ($q) => $q->whereNotIn('status', ['submitted', 'under_review', 'awaiting_owner_approval', 'sent', 'accepted']));
        $report['attachments'] = $files->count();
        $dry || $files->each(function ($a) {
            Storage::disk('local')->delete($a->path);
            $a->delete();
        });

        $stash = collect(Storage::disk('local')->allFiles('stash'))->filter(fn ($f) => Storage::disk('local')->lastModified($f) < now()->subDays(7)->timestamp);
        $report['abandoned uploads'] = $stash->count();
        $dry || Storage::disk('local')->delete($stash->all());

        $logs = AuditLog::where('created_at', '<', now()->subMonths($r['audit_logs_months']));
        $report['audit logs'] = $logs->count();
        $dry || $logs->delete();

        $tokens = CustomerAccessToken::where('expires_at', '<', now()->subDays($r['access_tokens_days']));
        $report['access tokens'] = $tokens->count();
        $dry || $tokens->delete();

        // Closed businesses past the 30-day recovery period: anonymize customers, keep transaction shells.
        $closing = Business::where('status', 'closing')->where('closing_requested_at', '<', now()->subDays(30))->get();
        $report['closed businesses anonymized'] = $closing->count();
        if (! $dry) {
            foreach ($closing as $b) {
                $b->customers()->update(['name' => 'Deleted customer', 'email' => null, 'phone' => null]);
                $b->conversations()->delete();
                $b->update(['status' => 'suspended']);
            }
        }

        foreach ($report as $k => $v) {
            $this->line(($dry ? '[dry-run] ' : '')."$k: $v");
        }

        return self::SUCCESS;
    }
}
