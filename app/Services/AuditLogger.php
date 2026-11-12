<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/** Important edits record actor, timestamp, reason and changes (P5). Never log secrets. */
class AuditLogger
{
    private const REDACT = ['password', 'mfa_secret', 'remember_token', 'token_hash', 'public_token_hash', 'api_key'];

    public static function log(
        ?int $businessId,
        string $action,
        ?Model $subject = null,
        array $changes = [],
        ?string $reason = null,
        ?string $actorType = null,
        ?int $actorId = null,
    ): AuditLog {
        foreach (self::REDACT as $key) {
            if (array_key_exists($key, $changes)) {
                $changes[$key] = '[redacted]';
            }
        }

        $user = auth()->user();

        return AuditLog::create([
            'business_id' => $businessId,
            'actor_type' => $actorType ?? ($user ? ($user->is_platform_admin && ! $businessId ? 'platform' : 'user') : 'system'),
            'actor_id' => $actorId ?? $user?->id,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'changes' => $changes ?: null,
            'reason' => $reason,
            'ip' => request()?->ip(),
        ]);
    }
}
