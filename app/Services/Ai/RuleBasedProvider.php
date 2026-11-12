<?php

namespace App\Services\Ai;

use App\Models\Business;
use App\Models\CatalogItem;

/**
 * Deterministic offline provider. Used when no Gemini key is configured, for tests, and as the
 * baseline for the AI evaluation suite. It understands common English/Tagalog/Taglish intents and
 * uses exactly the same controlled tools as the Gemini provider.
 */
class RuleBasedProvider implements ChatProvider
{
    private bool $tagalog = false;

    public function respond(Business $business, string $systemPrompt, array $history, string $message, AssistantTools $tools): ProviderResult
    {
        $t = mb_strtolower(trim($message));
        $this->tagalog = (bool) preg_match('/\b(po|ba|magkano|pwede|puwede|ako|kayo|sana|naman|lang|bukas|ngayon|saan|ano|gusto|paano|salamat|opo|meron|wala)\b/', $t);

        // 1. Attempts to gain authority or other people's data: refuse (customer text is data, not instructions).
        if (preg_match('/(ignore (all |the |your )?(previous|prior|above)|system prompt|developer mode|you are now|act as (the )?(admin|owner)|(change|set|update|lower) (the )?(price|stock)|internal notes?|hidden notes?|your instructions|other customers?|ibang customer|mga customer niyo|(which|list|show me|sinong|sino ang mga) customers?|customers? (who|na bumili)|admin password|api key|database)/', $t)) {
            return $this->say('Sorry, I can’t do that. I can only share this business’s published information and help you prepare a request for staff to review.',
                'Pasensya na, hindi ko po magagawa iyan. Ang kaya ko lang ay ibahagi ang published na impormasyon ng negosyo at tulungan kayong maghanda ng request.');
        }

        // 2. Human / complaint / judgment → handoff.
        if (preg_match('/\b(human|real person|tao|staff|agent|manager|owner|may-ari|kausap|makausap|complain|complaint|reklamo|refund|ibalik ang pera|angry|galit|disappointed|dismayado|sira|damaged|wrong item|mali ang)\b/', $t)) {
            $tools->execute('request_human', ['reason' => 'Customer asked for a person or raised an issue', 'summary' => 'Customer wrote: '.mb_substr($message, 0, 300)]);

            return $this->say('I’ve passed this to the team. A staff member will reply here during '.$business->setting('response_hours').'.',
                'Ipinasa ko na po ito sa staff. Sasagot po sila dito sa oras na '.$business->setting('response_hours').'.');
        }

        // 3. Discounts / custom pricing / exceptions need owner judgment.
        if (preg_match('/\b(discount|tawad|less|mas mura|pababa|promo code|custom price|special price|bulk price|libre|for free|make it free|it free|free of charge)\b/', $t)) {
            $tools->execute('request_human', ['reason' => 'Discount or custom price request', 'summary' => mb_substr($message, 0, 300)]);

            return $this->say('Discounts and special prices are decided by the owner, so I’ve passed your request to the team. They’ll reply here.',
                'Ang discount o special price ay desisyon po ng may-ari, kaya ipinasa ko na sa team. Sasagot po sila dito.');
        }

        // 4. Payment claims cannot be verified by the AI.
        if (preg_match('/\b(paid|bayad na|nagbayad|sent (the )?(payment|gcash|maya)|gcash|maya|screenshot|resibo|receipt|proof of payment)\b/', $t)) {
            $tools->execute('request_human', ['reason' => 'Payment question', 'summary' => mb_substr($message, 0, 300)]);

            return $this->say('Thanks! I can’t verify payments myself — a staff member checks them and updates your order. I’ve let the team know.',
                'Salamat po! Hindi ko po kayang i-verify ang bayad — staff po ang nagche-check nito. Naipaalam ko na sa team.');
        }

        // 5. Changes to existing records need verified access.
        if (preg_match('/\b(cancel|i-cancel|ikansela|resched|reschedule|move my|ilipat|palitan ang oras|status of my|track|(order|booking|appointment|request) status|status ng|nasaan na|where is my|my reference|reference (is|number|no)|(ord|apt|qte|srq)-[a-z0-9]{4,})\b/', $t)) {
            return $this->say('To change or check an existing request, open “Track my request” on this page and verify with the email you used. For security I can’t look up records from chat.',
                'Para baguhin o i-check ang request, buksan po ang “Track my request” sa page na ito at i-verify gamit ang email ninyo. Para sa seguridad, hindi ko po ma-check ang records dito sa chat.');
        }

        $services = CatalogItem::forBusiness($business)->active()->get();
        $mentioned = $this->matchItems($services, $t);
        if (! $mentioned) {
            // Carry the item from earlier in the conversation (e.g. "magkano haircut?" → "pwede bukas 3pm?").
            foreach (array_reverse($history) as $turn) {
                if ($turn['role'] === 'customer' && $found = $this->matchItems($services, mb_strtolower($turn['text']))) {
                    $mentioned = $found;
                    break;
                }
            }
        }
        $date = DateParser::date($t, $business->timezone);
        $time = DateParser::time($t);

        // 6. Opening hours / location.
        $asksHours = preg_match('/\b(open|close|closing|hours|oras|sarado|anong oras|what time do you)\b/', $t) || (str_contains($t, 'bukas') && ! DateParser::bukasMeansTomorrow($t));
        if ($asksHours && ! preg_match('/\b(book|appointment|slot|schedule|pa-?book)\b/', $t)) {
            $info = $tools->execute('get_business_info', []);

            return $this->say("Our hours:\n".implode("\n", $info['opening_hours']), "Ang oras po namin:\n".implode("\n", $info['opening_hours']));
        }
        if (preg_match('/\b(where|saan|address|location|located|direksyon|directions)\b/', $t)) {
            $info = $tools->execute('get_business_info', []);
            if (! $info['address']) {
                return $this->say('The address isn’t published yet. I can pass your question to staff if you like.', 'Wala pa pong naka-publish na address. Pwede ko pong ipasa sa staff ang tanong ninyo.');
            }

            return $this->say('We’re at '.$info['address'].'.', 'Nandito po kami sa '.$info['address'].'.');
        }

        // 7. Booking.
        $wantsBooking = preg_match('/\b(book|booking|appointment|appt|schedule|sched|reserve|reservation|slots?|available|availability|pa-?book|pa-?schedule|bakante|vacant)\b/', $t) || ($date && $time)
            || ($date && preg_match('/\b(pwede|puwede|can i|could i|may i|okay ba|ok ba)\b/', $t));
        if ($wantsBooking && $business->moduleEnabled('appointments')) {
            $service = collect($mentioned)->first(fn ($i) => $i->type === 'service');
            if (! $service) {
                $list = $tools->execute('search_catalog', ['type' => 'service']);
                $names = implode(', ', array_map(fn ($i) => $i['name'], array_slice($list['items'], 0, 6)));

                return $this->say("Which service would you like to book? We offer: $names.", "Anong service po ang gusto ninyong i-book? Meron po kami: $names.");
            }
            if (! $date) {
                return $this->say("Sure — what date would you like for {$service->name}?", "Sige po — anong petsa ang gusto ninyo para sa {$service->name}?");
            }
            if ($time) {
                $r = $tools->execute('prepare_booking', ['service_id' => $service->id, 'date' => $date, 'time' => $time]);
                if (! empty($r['draft_ready'])) {
                    return $this->say("{$service->name} on {$r['exact_datetime']} is open. Tap “Review & confirm” to finish — it isn’t booked until you confirm.",
                        "Bakante po ang {$service->name} sa {$r['exact_datetime']}. I-tap ang “Review & confirm” para matapos — hindi pa po ito booked hangga’t hindi ninyo kinukumpirma.");
                }
            }
            $a = $tools->execute('check_availability', ['service_id' => $service->id, 'date' => $date]);
            if (! empty($a['error'])) {
                return $this->say('I couldn’t check availability right now. Please use the booking form, or I can pass you to staff.',
                    'Hindi ko po ma-check ang availability ngayon. Pakigamit po ang booking form, o ipapasa ko kayo sa staff.');
            }
            if (! $a['open_times']) {
                return $this->say("There are no open times for {$service->name} on {$a['date']}. Would another date work?",
                    "Wala na pong bakanteng oras para sa {$service->name} sa {$a['date']}. Pwede po ba ang ibang araw?");
            }
            $prefix = $time ? 'That exact time isn’t open. ' : '';
            $prefixTl = $time ? 'Hindi po bakante ang oras na iyon. ' : '';

            return $this->say($prefix."Open times for {$service->name} on {$a['date']}: ".implode(', ', $a['open_times']).'. Which one works?',
                $prefixTl."Bakanteng oras para sa {$service->name} sa {$a['date']}: ".implode(', ', $a['open_times']).'. Alin po ang gusto ninyo?');
        }

        // 8. Ordering.
        $wantsOrder = preg_match('/\b(order|buy|bili|bibili|pabili|bilhin|kukuha|add to cart|checkout|pa-?order)\b/', $t);
        if ($wantsOrder && $business->moduleEnabled('orders')) {
            $products = array_values(array_filter($mentioned, fn ($i) => $i->type === 'product'));
            if (! $products) {
                $list = $tools->execute('search_catalog', ['type' => 'product']);
                $names = implode(', ', array_map(fn ($i) => $i['name'].' ('.$i['price'].')', array_slice($list['items'], 0, 6)));

                return $this->say("Which product would you like? Available: $names.", "Anong produkto po ang gusto ninyo? Meron po: $names.");
            }
            $qty = preg_match('/\b(\d{1,2})\s*(pcs|pieces|piraso|x|bottles|bote|items)?\b/', $t, $m) ? max(1, (int) $m[1]) : 1;
            $method = preg_match('/(deliver|ipadala|padala|\bship\b)/', $t) ? 'delivery' : 'pickup';
            $r = $tools->execute('prepare_order', ['items' => array_map(fn ($p) => ['item_id' => $p->id, 'quantity' => $qty], $products), 'fulfillment' => $method]);
            if (! empty($r['error'])) {
                return $this->say($r['error'], $r['error']);
            }

            return $this->say("Here’s your order draft (subtotal {$r['subtotal']}). Tap “Review & confirm” to submit — nothing is reserved until you do.",
                "Narito po ang draft ng order ninyo (subtotal {$r['subtotal']}). I-tap ang “Review & confirm” para i-submit — wala pang naka-reserve hangga’t hindi ninyo sinusubmit.");
        }

        // 9. Quotations / service requests.
        $asksPrice = (bool) preg_match('/\b(price|prices|how much|magkano|presyo|rate|rates|cost)\b/', $t) && $mentioned;
        if (! $asksPrice && $business->moduleEnabled('quotations') && preg_match('/\b(quote|quotation|estimate|estimate|magkano aabutin|magkano kaya|presyo para sa event|for an event|bulk|custom)\b/', $t)) {
            $r = $tools->execute('prepare_quote_request', ['requirements' => $message]);
            if (! empty($r['draft_ready'])) {
                return $this->say('I’ve prepared a quotation request from your message. Tap “Review & confirm” to add details and submit; the team will send a quote.',
                    'Naghanda po ako ng quotation request mula sa mensahe ninyo. I-tap ang “Review & confirm” para dagdagan ng detalye at i-submit; magpapadala ang team ng quote.');
            }
        }
        if (! $asksPrice && $business->moduleEnabled('service_requests') && preg_match('/\b(clean|cleaning|linis|maglinis|ipalinis|repair|ayusin|ipaayos|aircon|service request|deep clean|general cleaning)\b/', $t)) {
            $r = $tools->execute('prepare_service_request', ['description' => $message, 'preferred_date' => $date]);
            if (! empty($r['draft_ready'])) {
                return $this->say('I’ve prepared a service request. Tap “Review & confirm” to add your location and submit — a preferred date isn’t a confirmed booking yet.',
                    'Naghanda po ako ng service request. I-tap ang “Review & confirm” para ilagay ang lokasyon at i-submit — hindi pa po kumpirmadong booking ang preferred date.');
            }
        }

        // 10. Prices / catalog.
        if ($mentioned || preg_match('/\b(price|prices|how much|magkano|presyo|rate|rates|cost|services|products|menu|catalog|ano.*(meron|offer)|what do you (offer|sell)|available ba|in stock|may stock)\b/', $t)) {
            $items = $mentioned ?: CatalogItem::forBusiness($business)->active()->limit(8)->get()->all();
            $list = $tools->execute('search_catalog', ['query' => implode(' ', array_map(fn ($i) => $i->name, $mentioned))]);
            $wanted = array_map(fn ($i) => $i->id, $items);
            $lines = array_map(function ($i) {
                $extra = isset($i['duration_minutes']) ? " · {$i['duration_minutes']} min" : (isset($i['availability']) ? " · {$i['availability']}" : '');

                return "• {$i['name']}: {$i['price']}{$extra}";
            }, array_values(array_filter($list['items'], fn ($i) => ! $mentioned || in_array($i['id'], $wanted, true))));

            return $this->say(implode("\n", $lines ?: ['No matching items found.']), implode("\n", $lines ?: ['Walang tugmang item.']));
        }

        // 11. Greetings / thanks.
        if (preg_match('/^(hi|hello|hey|good (morning|afternoon|evening)|magandang (umaga|hapon|gabi)|kumusta|musta|helo|uy)\b/', $t)) {
            return $this->say($business->greeting ?: "Hi! I’m {$business->name}’s AI assistant. I can answer questions, share prices, and help you prepare a request.",
                "Hello po! Ako ang AI assistant ng {$business->name}. Pwede ko pong sagutin ang tanong ninyo, ibigay ang presyo, at tulungan kayong maghanda ng request.");
        }
        if (preg_match('/\b(thank|thanks|salamat|ty)\b/', $t)) {
            return $this->say('You’re welcome! Anything else I can help with?', 'Walang anuman po! May iba pa po ba akong maitutulong?');
        }

        // 12. Ask a focused clarification.
        return $this->say('Sorry, I didn’t quite get that. I can share prices and hours, check open times, or help you request a booking'.($business->moduleEnabled('orders') ? ', order' : '').' — or connect you with staff. What would you like?',
            'Pasensya na po, hindi ko masyadong naintindihan. Pwede ko pong ibigay ang presyo at oras, i-check ang bakanteng oras, o tulungan kayong mag-request — o ipasa kayo sa staff. Ano po ang gusto ninyo?');
    }

    /** @return CatalogItem[] */
    private function matchItems($items, string $t): array
    {
        $found = [];
        foreach ($items as $i) {
            $name = mb_strtolower($i->name);
            $keywords = array_filter(preg_split('/[\s\-–&,\/()]+/', $name), fn ($w) => mb_strlen($w) >= 4 && ! in_array($w, ['with', 'and', 'for', 'per', 'hour', 'session'], true));
            if (str_contains($t, $name)) {
                $found[$i->id] = $i;
                continue;
            }
            foreach ($keywords as $k) {
                if (preg_match('/\b'.preg_quote(rtrim($k, 's'), '/').'/u', $t)) {
                    $found[$i->id] = $i;
                    break;
                }
            }
        }

        return array_values($found);
    }

    private function say(string $en, string $tl): ProviderResult
    {
        return new ProviderResult($this->tagalog ? $tl : $en, 0);
    }
}
