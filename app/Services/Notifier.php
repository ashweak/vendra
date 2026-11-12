<?php

namespace App\Services;

use App\Models\Business;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Log;
use Throwable;

class Notifier
{
    /** Notify owners/managers (and optionally one assignee). Failures are logged, never thrown. */
    public static function staff(Business $business, string $title, string $body, ?string $url = null, ?int $assigneeId = null): void
    {
        try {
            $users = $business->activeStaff()
                ->where(fn ($q) => $q->whereIn('memberships.role', ['owner', 'manager'])
                    ->when($assigneeId, fn ($q) => $q->orWhere('users.id', $assigneeId)))
                ->get();
            foreach ($users as $u) {
                $u->notify(new StaffAlert($title, $body, $url, $business->id));
            }
        } catch (Throwable $e) {
            Log::warning('Vendra notification failed', ['business' => $business->id, 'title' => $title, 'error' => $e->getMessage()]);
        }
    }
}
