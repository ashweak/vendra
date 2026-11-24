<?php

namespace App\Services\Ai;

use App\Models\Business;
use App\Models\KnowledgeEntry;
use Illuminate\Support\Collection;

/**
 * Finds the owner's published FAQ / policy entries that answer a customer's question, by word overlap
 * (English, Tagalog and Taglish; no outside service). Used by the auto-assistant and staff reply suggestions.
 */
class KnowledgeMatcher
{
    private const STOP = [
        // English
        'the', 'and', 'for', 'you', 'your', 'are', 'can', 'what', 'how', 'when', 'where', 'which', 'who', 'why', 'with', 'this', 'that',
        'have', 'has', 'does', 'did', 'will', 'would', 'could', 'should', 'there', 'about', 'from', 'any', 'our', 'ours', 'yes', 'please',
        'is', 'do', 'of', 'to', 'in', 'on', 'at', 'it', 'a', 'an', 'i', 'my', 'me', 'we', 'be', 'or', 'if', 'so', 'hi', 'hello',
        // Tagalog / Taglish
        'po', 'ba', 'ang', 'ng', 'sa', 'na', 'mga', 'ko', 'kayo', 'ninyo', 'niyo', 'nyo', 'namin', 'natin', 'yung', 'iyong', 'ito', 'iyan',
        'may', 'meron', 'mayroon', 'wala', 'ano', 'paano', 'saan', 'kailan', 'pwede', 'puwede', 'lang', 'naman', 'din', 'rin', 'pa', 'ho',
        'opo', 'oo', 'si', 'ni', 'kay', 'at', 'para', 'ay', 'kung', 'nga', 'sana', 'tanong', 'question',
    ];

    /** @return string[] meaningful, lightly normalised words */
    public static function words(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $text);
        $out = [];
        foreach (preg_split('/[\s-]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) as $w) {
            if (mb_strlen($w) < 3 || in_array($w, self::STOP, true) || is_numeric($w)) {
                continue;
            }
            $out[] = preg_replace('/(ies|es|s)$/u', '', $w) ?: $w; // fees → fee, deliveries → deliver
        }

        return array_values(array_unique($out));
    }

    /**
     * Best matching published entries, best first.
     *
     * @return Collection<int, array{entry: KnowledgeEntry, score: float}>
     */
    public static function match(Business $business, string $question, int $limit = 3): Collection
    {
        $q = self::words($question);
        if (! $q) {
            return collect();
        }

        return $business->knowledgeEntries()->where('is_published', true)->get()
            ->map(function (KnowledgeEntry $e) use ($q) {
                $title = self::words($e->title);
                $body = self::words($e->body);
                $inTitle = count(array_intersect($q, $title));
                $inBody = count(array_intersect($q, $body));
                // Title words carry most weight; how much of the title the question covers matters too.
                $score = $title ? ($inTitle / count($title)) * 0.7 + min(1, ($inTitle * 2 + $inBody) / max(2, count($q) * 2)) * 0.3 : 0;
                if ($inTitle === 0 && $inBody < 2) {
                    $score = 0;
                }

                return ['entry' => $e, 'score' => round($score, 3)];
            })
            ->filter(fn ($m) => $m['score'] >= 0.35)
            ->sortByDesc('score')->values()->take($limit);
    }

    public static function best(Business $business, string $question): ?KnowledgeEntry
    {
        return self::match($business, $question, 1)->first()['entry'] ?? null;
    }
}
