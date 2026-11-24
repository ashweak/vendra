<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Services\Ai\KnowledgeMatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/** A customer question the auto-assistant couldn't answer. Contact details are masked before saving. */
class AssistantGap extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_asked_at' => 'datetime'];
    }

    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }

    public function knowledgeEntry(): BelongsTo { return $this->belongsTo(KnowledgeEntry::class); }

    /** Emails, phone numbers, references and long numbers are replaced, so the list holds questions, not personal data. */
    public static function mask(string $text): string
    {
        $text = preg_replace('/[\p{L}\p{N}._%+-]+@[\p{L}\p{N}.-]+\.[a-z]{2,}/iu', '[email]', $text);
        $text = preg_replace('/\b(?:APT|ORD|QTE|SRQ)-[A-Z0-9]{4,}\b/i', '[reference]', $text);
        $text = preg_replace('/(\+?\d[\d\s().-]{6,}\d)/', '[number]', $text);

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), 290, '…');
    }

    public static function record(Conversation $c, string $question): ?self
    {
        $masked = self::mask($question);
        $words = KnowledgeMatcher::words($masked);
        $tokens = preg_split('/\s+/u', trim($masked), -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) === 0 || count($tokens) < 2) {
            return null; // "asdfgh", emoji, a lone number: nothing to answer
        }
        sort($words);
        $fingerprint = hash('sha256', implode(' ', $words));
        $attrs = ['business_id' => $c->business_id, 'fingerprint' => $fingerprint];
        $gap = self::where($attrs)->first();
        if ($gap) {
            $gap->forceFill([
                'times_asked' => $gap->times_asked + 1, 'last_asked_at' => now(), 'conversation_id' => $c->id, 'question' => $masked,
                // Asked again after it was answered or dismissed: the answer didn't cover it.
                'status' => 'open',
            ])->save();

            return $gap;
        }
        try {
            $gap = new self(['question' => $masked, 'last_asked_at' => now(), 'conversation_id' => $c->id]);
            $gap->forceFill($attrs)->save();

            return $gap;
        } catch (QueryException) {
            return self::where($attrs)->first(); // the same question arrived at the same moment
        }
    }

    /** The first staff reply after this question in its conversation: a ready-made answer to start from. */
    public function staffReply(): ?string
    {
        if (! $this->conversation_id) {
            return null;
        }

        return Message::where('conversation_id', $this->conversation_id)->where('sender_type', 'staff')->where('is_internal', false)
            ->where('created_at', '>=', $this->last_asked_at->copy()->subMinute())
            ->orderBy('id')->value('body');
    }
}
