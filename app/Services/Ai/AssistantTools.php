<?php

namespace App\Services\Ai;

use App\Exceptions\RuleViolation;
use App\Models\Business;
use App\Models\CatalogItem;
use App\Models\Conversation;
use App\Services\AppointmentService;
use App\Services\ConversationService;
use App\Services\OrderService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Controlled functions available to the AI (P6). The AI is a restricted actor: these tools only read
 * approved, published, business-scoped data, prepare drafts the customer must review and confirm in a
 * regular form, or hand off to staff. No tool can change prices, stock, permissions, policies,
 * subscriptions, payments, or read other customers' records.
 */
class AssistantTools
{
    /** @var array<int, array{name: string, args: array}> */
    public array $calls = [];

    public ?array $draft = null;

    public bool $handedOff = false;

    public function __construct(
        public Business $business,
        public Conversation $conversation,
        private AppointmentService $appointments,
        private OrderService $orders,
        private ConversationService $conversations,
    ) {}

    public function declarations(): array
    {
        $b = $this->business;
        $d = [
            ['name' => 'get_business_info', 'description' => 'Business name, address, contact, opening hours, response hours, enabled services, and published FAQ/policies.'],
            ['name' => 'search_catalog', 'description' => 'Search the approved catalog of services and products with live prices and availability.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Keywords, e.g. "haircut" or "serum". Empty for all.'],
                    'type' => ['type' => 'string', 'enum' => ['service', 'product', 'any']],
                ]]],
            ['name' => 'request_human', 'description' => 'Hand the conversation to staff. Use for complaints, custom prices, discounts, exceptions, payment questions, repeated misunderstanding, or when the customer asks for a person.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'reason' => ['type' => 'string'],
                    'summary' => ['type' => 'string', 'description' => 'What the customer needs and what is still unresolved.'],
                ], 'required' => ['reason', 'summary']]],
        ];
        if ($b->moduleEnabled('appointments')) {
            $d[] = ['name' => 'check_availability', 'description' => 'List open appointment times for a service on a date (YYYY-MM-DD, business timezone).',
                'parameters' => ['type' => 'object', 'properties' => [
                    'service_id' => ['type' => 'integer'], 'date' => ['type' => 'string'],
                ], 'required' => ['service_id', 'date']]];
            $d[] = ['name' => 'prepare_booking', 'description' => 'Prepare a booking draft for the customer to review and confirm in the booking form. Does NOT book.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'service_id' => ['type' => 'integer'], 'date' => ['type' => 'string'], 'time' => ['type' => 'string', 'description' => 'HH:MM 24h'],
                    'staff_id' => ['type' => 'integer'],
                ], 'required' => ['service_id', 'date', 'time']]];
        }
        if ($b->moduleEnabled('orders')) {
            $d[] = ['name' => 'prepare_order', 'description' => 'Prepare an order draft for the customer to review and confirm in the order form. Does NOT place the order.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'items' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                        'item_id' => ['type' => 'integer'], 'quantity' => ['type' => 'integer'],
                    ], 'required' => ['item_id', 'quantity']]],
                    'fulfillment' => ['type' => 'string', 'enum' => ['pickup', 'delivery']],
                ], 'required' => ['items']]];
        }
        if ($b->moduleEnabled('quotations')) {
            $d[] = ['name' => 'prepare_quote_request', 'description' => 'Prepare a quotation request draft (customer reviews and submits it). Use for custom work needing a price estimate.',
                'parameters' => ['type' => 'object', 'properties' => ['requirements' => ['type' => 'string']], 'required' => ['requirements']]];
        }
        if ($b->moduleEnabled('service_requests')) {
            $d[] = ['name' => 'prepare_service_request', 'description' => 'Prepare a service request draft (customer reviews and submits it). A preferred date is not a confirmed booking.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'description' => ['type' => 'string'], 'location' => ['type' => 'string'], 'preferred_date' => ['type' => 'string'],
                ], 'required' => ['description']]];
        }

        return $d;
    }

    public function names(): array
    {
        return array_column($this->declarations(), 'name');
    }

    public function execute(string $name, array $args): array
    {
        $this->calls[] = ['name' => $name, 'args' => $args];
        if (! in_array($name, $this->names(), true)) {
            return ['error' => 'This action is not available.'];
        }
        try {
            return match ($name) {
                'get_business_info' => $this->businessInfo(),
                'search_catalog' => $this->searchCatalog((string) ($args['query'] ?? ''), (string) ($args['type'] ?? 'any')),
                'check_availability' => $this->checkAvailability((int) ($args['service_id'] ?? 0), (string) ($args['date'] ?? '')),
                'prepare_booking' => $this->prepareBooking($args),
                'prepare_order' => $this->prepareOrder($args),
                'prepare_quote_request' => $this->prepareSimple('quote', ['requirements' => (string) ($args['requirements'] ?? '')]),
                'prepare_service_request' => $this->prepareSimple('service_request', [
                    'description' => (string) ($args['description'] ?? ''), 'location' => $args['location'] ?? null, 'preferred_date' => $args['preferred_date'] ?? null,
                ]),
                'request_human' => $this->requestHuman((string) ($args['reason'] ?? 'Customer request'), (string) ($args['summary'] ?? '')),
            };
        } catch (RuleViolation $e) {
            return ['error' => $e->getMessage()];
        } catch (Throwable $e) {
            report($e);

            return ['error' => 'Checking failed. Do not claim availability; offer the form or staff help.'];
        }
    }

    public function businessInfo(): array
    {
        $b = $this->business;
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $hours = $b->hours()->orderBy('weekday')->get()->map(fn ($h) => $days[$h->weekday].': '.(
            $h->is_closed || ! $h->opens_at ? 'Closed' : substr($h->opens_at, 0, 5).'–'.substr($h->closes_at, 0, 5)
        ))->all();

        return [
            'name' => $b->name,
            'description' => $b->description,
            'address' => $b->address,
            'phone' => $b->phone,
            'email' => $b->email,
            'opening_hours' => $hours,
            'staff_response_hours' => $b->setting('response_hours'),
            'enabled_actions' => array_values($b->modules ?? []),
            'fulfillment_methods' => $b->moduleEnabled('orders') ? $b->setting('fulfillment_methods') : [],
            'cancellation_policy_hours' => $b->setting('cancellation_hours'),
            'published_knowledge' => $b->knowledgeEntries()->where('is_published', true)->get(['kind', 'title', 'body'])->toArray(),
        ];
    }

    public function searchCatalog(string $query, string $type = 'any'): array
    {
        $q = CatalogItem::forBusiness($this->business)->active();
        $allowed = [];
        if ($this->business->moduleEnabled('appointments') || $this->business->moduleEnabled('quotations') || $this->business->moduleEnabled('service_requests')) {
            $allowed[] = 'service';
        }
        if ($this->business->moduleEnabled('orders')) {
            $allowed[] = 'product';
        }
        $q->whereIn('type', in_array($type, ['service', 'product'], true) ? array_intersect([$type], $allowed) : $allowed);
        $items = $q->orderBy('name')->get();

        $words = array_filter(preg_split('/\s+/', mb_strtolower(trim($query))), fn ($w) => mb_strlen($w) > 2);
        if ($words) {
            $filtered = $items->filter(function ($i) use ($words) {
                $hay = mb_strtolower($i->name.' '.$i->category.' '.$i->description);
                foreach ($words as $w) {
                    if (str_contains($hay, rtrim($w, 's'))) {
                        return true;
                    }
                }

                return false;
            });
            $items = $filtered->isNotEmpty() ? $filtered : $items;
        }

        return ['items' => $items->take(15)->map(fn (CatalogItem $i) => array_filter([
            'id' => $i->id,
            'type' => $i->type,
            'name' => $i->name,
            'category' => $i->category,
            'price' => Money::format($i->price_cents),
            'duration_minutes' => $i->duration_minutes,
            'availability' => $i->type === 'product'
                ? ($i->stock_on_hand === null ? 'available' : ($i->availableStock() <= 0 ? 'out of stock' : ($i->availableStock() <= 3 ? 'low stock' : 'in stock')))
                : null,
            'description' => $i->description,
        ], fn ($v) => $v !== null))->values()->all()];
    }

    public function checkAvailability(int $serviceId, string $date): array
    {
        $service = CatalogItem::forBusiness($this->business)->services()->active()->find($serviceId);
        if (! $service) {
            return ['error' => 'Unknown service. Use search_catalog first.'];
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['error' => 'Date must be YYYY-MM-DD.'];
        }
        $slots = $this->appointments->availableSlots($this->business, $service, $date);
        $day = CarbonImmutable::parse($date, $this->business->timezone);

        return [
            'service' => $service->name,
            'date' => $day->format('l, F j, Y'),
            'open_times' => collect($slots)->unique(fn ($s) => $s['start']->format('H:i'))->take(12)
                ->map(fn ($s) => $s['start']->format('g:i A').($s['staff_name'] ? ' (with '.$s['staff_name'].')' : ''))->values()->all(),
            'note' => $slots ? 'Times are not held until the customer confirms in the form.' : 'No open times on this date.',
        ];
    }

    public function prepareBooking(array $a): array
    {
        $service = CatalogItem::forBusiness($this->business)->services()->active()->find((int) ($a['service_id'] ?? 0));
        if (! $service) {
            return ['error' => 'Unknown service.'];
        }
        $date = (string) ($a['date'] ?? '');
        $time = (string) ($a['time'] ?? '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return ['error' => 'Need date YYYY-MM-DD and time HH:MM.'];
        }
        $start = CarbonImmutable::parse("$date $time", $this->business->timezone);
        $slot = collect($this->appointments->availableSlots($this->business, $service, $date, isset($a['staff_id']) ? (int) $a['staff_id'] : null))
            ->first(fn ($s) => $s['start']->eq($start));
        if (! $slot) {
            return ['error' => 'That time is not open. Offer other times from check_availability.'];
        }
        $this->draft = [
            'type' => 'booking',
            'title' => $service->name,
            'lines' => [
                'When: '.$start->format('l, F j, Y \a\t g:i A'),
                'Price: '.Money::format($service->price_cents),
            ] + ($slot['staff_name'] ? [2 => 'With: '.$slot['staff_name']] : []),
            'url' => route('public.book', [$this->business, 'service' => $service->id, 'date' => $date, 'time' => $time, 'staff' => $slot['staff_id']]),
        ];

        return ['draft_ready' => true, 'exact_datetime' => $start->format('l, F j, Y g:i A'), 'instruction' => 'Tell the customer to tap "Review & confirm". Nothing is booked yet.'];
    }

    public function prepareOrder(array $a): array
    {
        $items = [];
        foreach ((array) ($a['items'] ?? []) as $line) {
            $items[(int) ($line['item_id'] ?? 0)] = (int) ($line['quantity'] ?? 1);
        }
        $methods = (array) $this->business->setting('fulfillment_methods');
        $method = in_array($a['fulfillment'] ?? '', $methods, true) ? $a['fulfillment'] : $methods[0];
        $priced = $this->orders->price($this->business, $items, $method);
        $this->draft = [
            'type' => 'order',
            'title' => 'Order for '.$method,
            'lines' => array_merge(
                array_map(fn ($l) => $l['quantity'].' × '.$l['name'].' — '.Money::format($l['line_total_cents']), $priced['lines']),
                ['Subtotal: '.Money::format($priced['subtotal_cents'])],
                $method === 'delivery' ? ['Delivery fee: to be confirmed by staff'] : [],
            ),
            'url' => route('public.order', [$this->business, 'items' => $items, 'method' => $method]),
        ];

        return ['draft_ready' => true, 'subtotal' => Money::format($priced['subtotal_cents']), 'instruction' => 'Tell the customer to tap "Review & confirm". Stock is not reserved until they submit.'];
    }

    public function prepareSimple(string $type, array $fields): array
    {
        $first = (string) reset($fields);
        if (mb_strlen(trim($first)) < 5) {
            return ['error' => 'Ask the customer for more details first.'];
        }
        $this->draft = [
            'type' => $type,
            'title' => $type === 'quote' ? 'Quotation request' : 'Service request',
            'lines' => array_values(array_filter([
                \Illuminate\Support\Str::limit($first, 160),
                ! empty($fields['location']) ? 'Location: '.$fields['location'] : null,
                ! empty($fields['preferred_date']) ? 'Preferred date (not a booking): '.$fields['preferred_date'] : null,
            ])),
            'url' => route($type === 'quote' ? 'public.quote' : 'public.request', [$this->business] + array_filter($fields)),
        ];

        return ['draft_ready' => true, 'instruction' => 'Tell the customer to tap "Review & confirm" to submit. Staff will review it.'];
    }

    public function requestHuman(string $reason, string $summary): array
    {
        if (! $this->handedOff) {
            $this->conversations->handoff($this->conversation, trim($reason.'. '.$summary));
            $this->handedOff = true;
        }

        return ['handed_off' => true, 'staff_response_hours' => $this->business->setting('response_hours'),
            'instruction' => 'Tell the customer a staff member will reply; do not promise an exact time.'];
    }
}
