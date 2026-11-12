<?php

namespace App\Services;

use App\Exceptions\RuleViolation;
use App\Models\Business;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Str;

/** Inbox states: AI active → Waiting for staff → Staff active → Closed (P5). */
class ConversationService
{
    /** Returns [Conversation, plainToken]. Only the hash is stored. */
    public function start(Business $business): array
    {
        $token = Str::random(48);
        $c = new Conversation([
            'public_token_hash' => hash('sha256', $token),
            'state' => $business->ai_enabled ? 'ai_active' : 'waiting_for_staff',
            'last_activity_at' => now(),
        ]);
        $c->business_id = $business->id;
        $c->save();

        return [$c, $token];
    }

    public function findByToken(Business $business, ?string $token): ?Conversation
    {
        if (! $token) {
            return null;
        }

        return Conversation::forBusiness($business)->where('public_token_hash', hash('sha256', $token))->first();
    }

    public function addMessage(Conversation $c, string $senderType, string $body, ?User $user = null, bool $internal = false, ?array $meta = null): Message
    {
        $m = new Message([
            'conversation_id' => $c->id,
            'sender_type' => $senderType,
            'user_id' => $user?->id,
            'body' => $body,
            'is_internal' => $internal,
            'meta' => $meta,
        ]);
        $m->business_id = $c->business_id;
        $m->save();
        $c->last_activity_at = now();
        if ($c->state === 'closed' && $senderType === 'customer') {
            $c->state = $c->business->ai_enabled ? 'ai_active' : 'waiting_for_staff';
        }
        $c->save();

        return $m;
    }

    /** Handoff pauses automated replies and records a summary of unresolved details. */
    public function handoff(Conversation $c, string $summary, string $by = 'ai'): Conversation
    {
        $c->state = 'waiting_for_staff';
        $c->handoff_summary = Str::limit($summary, 1000);
        $c->save();
        $this->addMessage($c, 'system', 'Handed over to staff. Summary: '.$c->handoff_summary, internal: true);
        AuditLogger::log($c->business_id, 'conversation.handoff', $c, [], $summary, $by === 'ai' ? 'ai' : 'customer');

        return $c;
    }

    public function takeOver(Conversation $c, User $user): Conversation
    {
        $c->state = 'staff_active';
        $c->assigned_to ??= $user->id;
        $c->save();
        AuditLogger::log($c->business_id, 'conversation.taken_over', $c);

        return $c;
    }

    public function staffReply(Conversation $c, User $user, string $body): Message
    {
        if ($c->state !== 'staff_active') {
            $this->takeOver($c, $user);
        }

        return $this->addMessage($c, 'staff', $body, $user);
    }

    public function note(Conversation $c, User $user, string $body): Message
    {
        return $this->addMessage($c, 'staff', $body, $user, internal: true);
    }

    /** AI resumes only by an explicit staff action. */
    public function resumeAi(Conversation $c, User $user): Conversation
    {
        if (! $c->business->ai_enabled) {
            throw new RuleViolation('The AI assistant is turned off for this business.');
        }
        $c->state = 'ai_active';
        $c->save();
        $this->addMessage($c, 'system', $user->name.' resumed the AI assistant.', internal: true);
        AuditLogger::log($c->business_id, 'conversation.ai_resumed', $c);

        return $c;
    }

    public function assign(Conversation $c, ?int $userId): Conversation
    {
        if ($userId && ! $c->business->memberships()->where('user_id', $userId)->where('is_active', true)->exists()) {
            throw new RuleViolation('That person is not an active team member.');
        }
        $c->assigned_to = $userId;
        $c->save();
        AuditLogger::log($c->business_id, 'conversation.assigned', $c, ['assigned_to' => $userId]);

        return $c;
    }

    public function close(Conversation $c): Conversation
    {
        $c->state = 'closed';
        $c->save();
        AuditLogger::log($c->business_id, 'conversation.closed', $c);

        return $c;
    }
}
