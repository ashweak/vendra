<?php

namespace App\Services\Ai;

use App\Models\CatalogItem;
use App\Models\Conversation;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * Reply ideas for staff, built only from the business's own published data (FAQ, catalog, hours)
 * and a few templates. Staff choose, edit and send; nothing is sent automatically.
 */
class ReplySuggester
{
    /** @return array<int, array{label: string, text: string}> */
    public static function for(Conversation $c, User $staff, int $limit = 4): array
    {
        $b = $c->business;
        $last = $c->messages()->customerVisible()->where('sender_type', 'customer')->latest('id')->value('body');
        $question = (string) $last;
        $t = mb_strtolower($question);
        $tl = (bool) preg_match('/\b(po|ba|magkano|pwede|puwede|ako|kayo|sana|naman|lang|bukas|ngayon|saan|ano|gusto|paano|salamat|opo|meron|wala)\b/u', $t);
        $first = Str::before((string) $staff->name, ' ') ?: 'the team';
        $customer = Str::before((string) ($c->customer?->name ?? ''), ' ');
        $out = [];

        if ($question !== '') {
            // 1. The owner's published answers.
            foreach (KnowledgeMatcher::match($b, $question, 2) as $m) {
                $out[] = ['label' => 'FAQ: '.Str::limit($m['entry']->title, 40), 'text' => $m['entry']->body];
            }

            // 2. Live prices for items the customer mentioned.
            $items = CatalogItem::forBusiness($b)->active()->get()->filter(function ($i) use ($t) {
                $name = mb_strtolower($i->name);
                if (str_contains($t, $name)) {
                    return true;
                }
                foreach (preg_split('/[\s\-–&,\/()]+/u', $name) as $w) {
                    if (mb_strlen($w) >= 4 && preg_match('/\b'.preg_quote(rtrim($w, 's'), '/').'s?\b/u', $t)) {
                        return true;
                    }
                }

                return false;
            })->take(3);
            if ($items->isNotEmpty()) {
                $lines = $items->map(fn ($i) => $i->name.': '.Money::format($i->price_cents).($i->type === 'service' && $i->duration_minutes ? " ({$i->duration_minutes} min)" : ''))->implode("\n");
                $link = $items->first()->type === 'service' && $b->moduleEnabled('appointments') ? route('public.book', $b)
                    : ($b->moduleEnabled('orders') ? route('public.order', $b) : route('public.home', $b));
                $out[] = ['label' => 'Prices', 'text' => $tl
                    ? "Ito po ang presyo:\n{$lines}\nPwede po kayong mag-book o mag-order dito: {$link}"
                    : "Here are the prices:\n{$lines}\nYou can book or order here: {$link}"];
            }

            // 3. Opening hours.
            if (preg_match('/\b(open|close|closing|hours|oras|sarado|bukas ba|anong oras|what time)\b/u', $t)) {
                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                $hours = $b->hours()->orderBy('weekday')->get()->map(fn ($h) => $days[$h->weekday].': '.($h->is_closed || ! $h->opens_at
                    ? ($tl ? 'Sarado' : 'Closed')
                    : date('g:i A', strtotime($h->opens_at)).' – '.date('g:i A', strtotime($h->closes_at))))->implode("\n");
                if ($hours !== '') {
                    $out[] = ['label' => 'Opening hours', 'text' => ($tl ? "Ang oras po namin:\n" : "Our hours:\n").$hours];
                }
            }

            // 4. Existing requests: never handled in chat (identity is checked by email link).
            if (preg_match('/\b(cancel|resched|reschedule|status|track|nasaan|ilipat|(apt|ord|qte|srq)-)/u', $t)) {
                $url = route('public.track', $b);
                $out[] = ['label' => 'Track my request', 'text' => $tl
                    ? "Para i-check o baguhin ang request ninyo, buksan po ang “Track my request” at i-verify gamit ang email ninyo: {$url}"
                    : "To check or change your request, please open “Track my request” and verify with your email: {$url}"];
            }
        }

        // 5. A friendly opener when staff join.
        $hi = $customer !== '' ? $customer : ($tl ? 'po' : 'there');
        $out[] = ['label' => 'Greeting', 'text' => $tl
            ? "Hi {$hi}! Si {$first} po ito mula sa {$b->name}. Salamat sa paghihintay — paano ko po kayo matutulungan?"
            : "Hi {$hi}! This is {$first} from {$b->name}. Thanks for waiting — how can I help?"];

        $seen = [];

        return array_values(array_slice(array_filter($out, function ($s) use (&$seen) {
            $key = md5($s['text']);
            if (isset($seen[$key])) {
                return false;
            }

            return $seen[$key] = true;
        }), 0, $limit));
    }
}
