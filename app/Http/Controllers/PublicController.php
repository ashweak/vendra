<?php

namespace App\Http\Controllers;

use App\Exceptions\RuleViolation;
use App\Models\Appointment;
use App\Models\Attachment;
use App\Models\Business;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Services\AppointmentService;
use App\Services\CustomerAccess;
use App\Services\CustomerService;
use App\Services\Notifier;
use App\Services\OrderService;
use App\Services\QuotationService;
use App\Services\ServiceRequestService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Customer-facing business page. Customers browse and inquire without an account; every submission
 * goes form → review → confirm. Records are visible only after verification (CustomerAccess).
 */
class PublicController extends Controller
{
    public function __construct(
        private CustomerService $customers,
        private CustomerAccess $access,
        private AppointmentService $appointments,
        private OrderService $orders,
        private QuotationService $quotes,
        private ServiceRequestService $requests,
    ) {}

    public function home(Request $request, Business $business)
    {
        abort_if($business->status === 'suspended', 404);
        $q = trim((string) $request->query('q'));
        $items = $business->catalogItems()->active()
            ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('description', 'like', "%$q%")->orWhere('category', 'like', "%$q%")))
            ->orderBy('type')->orderBy('category')->orderBy('name')->get()
            ->filter(fn ($i) => $i->type === 'product' ? $business->moduleEnabled('orders') : true);

        return view('public.home', [
            'business' => $business,
            'items' => $items,
            'q' => $q,
            'hours' => $business->hours()->orderBy('weekday')->get(),
            'knowledge' => $business->knowledgeEntries()->where('is_published', true)->get(),
        ]);
    }

    // ---------------------------------------------------------------- Appointments

    public function book(Request $request, Business $business)
    {
        $this->guard($business, 'appointments');
        $services = $business->catalogItems()->services()->active()->orderBy('name')->get();

        return view('public.book', [
            'business' => $business,
            'services' => $services,
            'prefill' => $request->only(['service', 'date', 'time', 'staff']),
            'today' => CarbonImmutable::now($business->timezone)->toDateString(),
            'maxDate' => CarbonImmutable::now($business->timezone)->addDays((int) $business->setting('booking_horizon_days'))->toDateString(),
        ]);
    }

    public function slots(Request $request, Business $business)
    {
        $this->guard($business, 'appointments');
        $data = $request->validate(['service' => 'required|integer', 'date' => 'required|date_format:Y-m-d']);
        $service = $business->catalogItems()->services()->active()->findOrFail($data['service']);

        return response()->json(['slots' => collect($this->appointments->availableSlots($business, $service, $data['date']))
            ->map(fn ($s) => [
                'time' => $s['start']->format('H:i'),
                'label' => $s['start']->format('g:i A'),
                'staff_id' => $s['staff_id'],
                'staff' => $s['staff_name'],
            ])->values()]);
    }

    public function reviewBooking(Request $request, Business $business)
    {
        $this->guard($business, 'appointments');
        $data = $this->validateBooking($request);
        $service = $business->catalogItems()->services()->active()->findOrFail($data['catalog_item_id']);
        $start = CarbonImmutable::parse($data['date'].' '.$data['time'], $business->timezone);
        $staff = ($data['staff_id'] ?? null) ? $business->activeStaff()->where('users.id', $data['staff_id'])->first() : null;
        $approval = $business->setting('booking_approval') === 'manual' || $service->requires_approval;

        return $this->review($business, 'book', $data, [
            'Service' => $service->name,
            'When' => $start->format('l, F j, Y \a\t g:i A'),
            'Duration' => ($service->duration_minutes ?? 60).' minutes',
            'With' => $staff?->name ?? 'Any available staff',
            'Price' => Money::format($service->price_cents),
            'Name' => $data['name'], 'Email' => $data['email'], 'Phone' => $data['phone'] ?? '—',
            'Notes' => $data['notes'] ?? '—',
        ], $approval ? 'This booking needs approval. You will see the decision deadline after submitting.' : null);
    }

    public function storeBooking(Request $request, Business $business)
    {
        $this->guard($business, 'appointments');
        $data = $this->validateBooking($request, true);
        $customer = $this->customers->resolve($business, $data);
        try {
            $appointment = $this->appointments->book($business, $customer, [
                'catalog_item_id' => $data['catalog_item_id'],
                'start' => $data['date'].' '.$data['time'],
                'staff_id' => $data['staff_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'idempotency_key' => $data['idempotency_key'],
            ]);
        } catch (RuleViolation $e) {
            return redirect()->route('public.book', [$business, 'service' => $data['catalog_item_id'], 'date' => $data['date']])
                ->withInput($request->except('time', 'idempotency_key'))->withErrors(['rule' => $e->getMessage()]);
        }
        $this->access->grant($business, $customer);
        if ($appointment->wasRecentlyCreated) {
            Notifier::staff($business, 'New booking '.$appointment->reference, "{$appointment->service_name} for {$customer->name} – {$appointment->statusLabel()}",
                route('dashboard.appointments.show', $appointment->id), $appointment->assigned_to);
        }

        return redirect()->route('public.result', [$business, $appointment->reference]);
    }

    private function validateBooking(Request $request, bool $confirm = false): array
    {
        return $request->validate([
            'catalog_item_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
            'staff_id' => 'nullable|integer',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:1000',
            'marketing_consent' => 'nullable|boolean',
        ] + $this->confirmRules($confirm));
    }

    // ---------------------------------------------------------------- Orders

    public function order(Request $request, Business $business)
    {
        $this->guard($business, 'orders');

        return view('public.order', [
            'business' => $business,
            'products' => $business->catalogItems()->products()->active()->orderBy('category')->orderBy('name')->get(),
            'prefill' => ['items' => (array) $request->query('items', []), 'method' => $request->query('method')],
        ]);
    }

    public function reviewOrder(Request $request, Business $business, ?string $error = null)
    {
        $this->guard($business, 'orders');
        $data = $this->validateOrder($request);
        $priced = $this->orders->price($business, $data['items'], $data['fulfillment_method']);
        $summary = [];
        foreach ($priced['lines'] as $l) {
            $summary[$l['quantity'].' × '.$l['name']] = Money::format($l['line_total_cents']);
        }
        $summary += [
            'Subtotal' => Money::format($priced['subtotal_cents']),
            'Fulfillment' => ucfirst($data['fulfillment_method']).($data['fulfillment_method'] === 'delivery' ? ' to '.$data['delivery_address'] : ''),
            'Delivery fee' => $data['fulfillment_method'] === 'delivery' ? 'To be confirmed by staff before your order is confirmed' : 'None',
            'Payment' => 'Not collected online. The business will tell you how to pay; staff verify payment.',
            'Name' => $data['name'], 'Email' => $data['email'], 'Phone' => $data['phone'] ?? '—',
        ];

        return $this->review($business, 'order', $data + ['reviewed_subtotal' => $priced['subtotal_cents']], $summary,
            'Stock is reserved only after you submit, and reservations expire after '.$business->setting('order_reservation_hours').' hours if not completed.', $error);
    }

    public function storeOrder(Request $request, Business $business)
    {
        $this->guard($business, 'orders');
        $data = $this->validateOrder($request, true);
        $customer = $this->customers->resolve($business, $data);
        try {
            $order = $this->orders->place($business, $customer, $data['items'], $data['fulfillment_method'], $data['delivery_address'] ?? null,
                (int) $request->input('reviewed_subtotal'), $data['idempotency_key'], null, $data['notes'] ?? null);
        } catch (RuleViolation $e) {
            if ($e->code_ === 'price_changed') {
                $request->merge(['idempotency_key' => null]);

                return $this->reviewOrder($request, $business, $e->getMessage());
            }

            return redirect()->route('public.order', $business)->withInput()->withErrors(['rule' => $e->getMessage()]);
        }
        $this->access->grant($business, $customer);
        if ($order->wasRecentlyCreated) {
            Notifier::staff($business, 'New order '.$order->reference, "{$customer->name} – ".Money::format($order->total_cents).' – '.$order->statusLabel(),
                route('dashboard.orders.show', $order->id));
        }

        return redirect()->route('public.result', [$business, $order->reference]);
    }

    private function validateOrder(Request $request, bool $confirm = false): array
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*' => 'nullable|integer|min:0|max:99',
            'fulfillment_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:fulfillment_method,delivery|nullable|string|max:500',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
            'notes' => 'nullable|string|max:1000',
            'marketing_consent' => 'nullable|boolean',
        ] + $this->confirmRules($confirm));
        $data['items'] = array_filter(array_map('intval', $data['items']), fn ($q) => $q > 0);
        if (! $data['items']) {
            throw new RuleViolation('Please choose at least one product.');
        }

        return $data;
    }

    // ---------------------------------------------------------------- Quotations & service requests

    public function quote(Request $request, Business $business)
    {
        $this->guard($business, 'quotations');

        return view('public.quote', ['business' => $business, 'prefill' => $request->only('requirements')]);
    }

    public function reviewQuote(Request $request, Business $business)
    {
        $this->guard($business, 'quotations');
        $data = $this->validateQuote($request);
        if ($request->hasFile('attachment')) {
            $data['attachment_token'] = $this->stashUpload($request, $business);
        }

        return $this->review($business, 'quote', $data, [
            'What you need' => $data['requirements'],
            'Attachment' => $request->hasFile('attachment') ? $request->file('attachment')->getClientOriginalName() : 'None',
            'Name' => $data['name'], 'Email' => $data['email'], 'Phone' => $data['phone'] ?? '—',
        ], 'A quotation request is not an order or booking. The business will review it and send you a quote to accept or decline.');
    }

    public function storeQuote(Request $request, Business $business)
    {
        $this->guard($business, 'quotations');
        $data = $this->validateQuote($request, true);
        $customer = $this->customers->resolve($business, $data);
        $quote = $this->quotes->submit($business, $customer, $data['requirements'], $data['idempotency_key']);
        if ($quote->wasRecentlyCreated && $token = $request->input('attachment_token')) {
            $this->attachStashed($business, $quote, $token);
        }
        $this->access->grant($business, $customer);
        if ($quote->wasRecentlyCreated) {
            Notifier::staff($business, 'New quotation request '.$quote->reference, $customer->name.': '.Str::limit($quote->requirements, 120),
                route('dashboard.quotations.show', $quote->id));
        }

        return redirect()->route('public.result', [$business, $quote->reference]);
    }

    private function validateQuote(Request $request, bool $confirm = false): array
    {
        return $request->validate([
            'requirements' => 'required|string|min:10|max:3000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'attachment_token' => 'nullable|string|size:40',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:40',
            'marketing_consent' => 'nullable|boolean',
        ] + $this->confirmRules($confirm));
    }

    public function serviceRequest(Request $request, Business $business)
    {
        $this->guard($business, 'service_requests');

        return view('public.request', ['business' => $business, 'prefill' => $request->only('description', 'location', 'preferred_date')]);
    }

    public function reviewServiceRequest(Request $request, Business $business)
    {
        $this->guard($business, 'service_requests');
        $data = $this->validateServiceRequest($request);

        return $this->review($business, 'request', $data, [
            'Description' => $data['description'],
            'Location' => $data['location'] ?? '—',
            'Preferred date' => ($data['preferred_date'] ?? null) ? CarbonImmutable::parse($data['preferred_date'])->format('l, F j, Y').' (preference only)' : '—',
            'Name' => $data['name'], 'Email' => $data['email'], 'Phone' => $data['phone'] ?? '—',
        ], 'A preferred date is not a confirmed booking. The business will review your request and contact you.');
    }

    public function storeServiceRequest(Request $request, Business $business)
    {
        $this->guard($business, 'service_requests');
        $data = $this->validateServiceRequest($request, true);
        $customer = $this->customers->resolve($business, $data);
        $sr = $this->requests->submit($business, $customer, $data);
        $this->access->grant($business, $customer);
        if ($sr->wasRecentlyCreated) {
            Notifier::staff($business, 'New service request '.$sr->reference, $customer->name.': '.Str::limit($sr->description, 120),
                route('dashboard.service-requests.show', $sr->id));
        }

        return redirect()->route('public.result', [$business, $sr->reference]);
    }

    private function validateServiceRequest(Request $request, bool $confirm = false): array
    {
        return $request->validate([
            'description' => 'required|string|min:10|max:3000',
            'location' => 'nullable|string|max:255',
            'preferred_date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
            'marketing_consent' => 'nullable|boolean',
        ] + $this->confirmRules($confirm));
    }

    // ---------------------------------------------------------------- Results & verified access

    public function result(Business $business, string $reference)
    {
        $customer = $this->access->customer($business);
        $record = $customer ? $this->findOwned($business, $customer, $reference) : null;
        if (! $record) {
            // Same response whether or not the reference exists.
            return redirect()->route('public.track', $business)->with('status', 'Please verify with your email to view this request.');
        }

        return view('public.result', ['business' => $business, 'record' => $record, 'type' => $this->typeOf($record)]);
    }

    public function track(Business $business)
    {
        return view('public.track', ['business' => $business]);
    }

    public function sendTrackLink(Request $request, Business $business)
    {
        $data = $request->validate(['reference' => 'required|string|max:20', 'email' => 'required|email']);
        $key = 'track:'.$business->id.':'.$request->ip();
        abort_if(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5), 429, 'Too many requests. Please wait a few minutes.');
        \Illuminate\Support\Facades\RateLimiter::hit($key, 300);
        $this->access->sendLink($business, $data['reference'], $data['email']);

        return back()->with('status', 'If the reference and email match our records, we sent a secure link to that email. It expires in 30 minutes.');
    }

    public function access(Business $business, string $token)
    {
        if (! $this->access->consume($business, $token)) {
            return redirect()->route('public.track', $business)->withErrors(['rule' => 'This link is invalid or has expired. Request a new one.']);
        }

        return redirect()->route('public.my', $business);
    }

    public function my(Business $business)
    {
        $customer = $this->access->customer($business);
        if (! $customer) {
            return redirect()->route('public.track', $business);
        }

        return view('public.my', [
            'business' => $business,
            'customer' => $customer,
            'appointments' => $customer->appointments()->latest('starts_at')->get(),
            'orders' => $customer->orders()->latest()->get(),
            'quotations' => $customer->quotations()->latest()->get(),
            'requests' => $customer->serviceRequests()->latest()->get(),
        ]);
    }

    public function cancelAppointment(Business $business, string $reference)
    {
        $customer = $this->access->customer($business) ?? abort(403);
        $a = $customer->appointments()->where('reference', $reference)->firstOrFail();
        $this->appointments->customerCancel($a, $customer);
        Notifier::staff($business, 'Booking cancelled '.$a->reference, $customer->name.' cancelled their booking.', route('dashboard.appointments.show', $a->id), $a->assigned_to);

        return back()->with('status', 'Your booking was cancelled.');
    }

    public function cancelOrder(Business $business, string $reference)
    {
        $customer = $this->access->customer($business) ?? abort(403);
        $o = $customer->orders()->where('reference', $reference)->firstOrFail();
        if (! in_array($o->status, ['pending_approval', 'confirmed'], true)) {
            throw new RuleViolation('This order is already being prepared. Please message the business to change it.');
        }
        $this->orders->transition($o, 'cancelled', null, 'Cancelled by customer');
        Notifier::staff($business, 'Order cancelled '.$o->reference, $customer->name.' cancelled their order.', route('dashboard.orders.show', $o->id));

        return back()->with('status', 'Your order was cancelled.');
    }

    public function respondQuote(Request $request, Business $business, string $reference)
    {
        $customer = $this->access->customer($business) ?? abort(403);
        $data = $request->validate(['version_id' => 'required|integer', 'action' => 'required|in:accept,decline,revise', 'note' => 'nullable|string|max:1000']);
        $q = $customer->quotations()->where('reference', $reference)->firstOrFail();
        $this->quotes->respond($q, (int) $data['version_id'], $data['action'], $customer, $data['note'] ?? null);
        Notifier::staff($business, 'Quotation '.$q->reference.' – customer chose '.$data['action'], $data['note'] ?? '', route('dashboard.quotations.show', $q->id), $q->assigned_to);

        return back()->with('status', match ($data['action']) {
            'accept' => 'Thank you! You accepted the quotation. The business will contact you about next steps.',
            'decline' => 'You declined the quotation.',
            'revise' => 'Your revision request was sent to the business.',
        });
    }

    public function attachment(Business $business, Attachment $attachment)
    {
        $customer = $this->access->customer($business);
        $owner = $attachment->attachable;
        abort_unless($customer && (int) $attachment->business_id === (int) $business->id && $owner && (int) $owner->customer_id === (int) $customer->id, 404);

        return response()->file(storage_path('app/private/'.$attachment->path), ['Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"']);
    }

    // ---------------------------------------------------------------- Helpers

    private function guard(Business $business, string $module): void
    {
        abort_unless($business->moduleEnabled($module), 404);
        abort_if($business->status === 'suspended', 404);
        if (! $business->isAcceptingSubmissions() && request()->isMethod('post')) {
            throw new RuleViolation('This business is not accepting online requests yet.');
        }
    }

    private function confirmRules(bool $confirm): array
    {
        return $confirm ? ['idempotency_key' => 'required|string|size:36', 'confirmed' => 'accepted'] : [];
    }

    private function review(Business $business, string $type, array $data, array $summary, ?string $notice = null, ?string $error = null)
    {
        unset($data['attachment'], $data['confirmed']);
        $data['idempotency_key'] = (string) Str::uuid();

        return response()->view('public.review', compact('business', 'type', 'data', 'summary', 'notice', 'error'));
    }

    private function findOwned(Business $business, $customer, string $reference)
    {
        foreach ([Appointment::class, Order::class, Quotation::class, ServiceRequest::class] as $cls) {
            $r = $cls::forBusiness($business)->where('reference', $reference)->where('customer_id', $customer->id)->first();
            if ($r) {
                return $r;
            }
        }

        return null;
    }

    private function typeOf($record): string
    {
        return match (true) {
            $record instanceof Appointment => 'appointment',
            $record instanceof Order => 'order',
            $record instanceof Quotation => 'quotation',
            default => 'service_request',
        };
    }

    /** Uploaded files wait in private storage between review and confirm; never executed or public. */
    private function stashUpload(Request $request, Business $business): string
    {
        $token = Str::random(40);
        $file = $request->file('attachment');
        $file->storeAs("stash/{$business->id}", $token, 'local');
        session(["stash.$token" => ['name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(), 'size' => $file->getSize()]]);

        return $token;
    }

    private function attachStashed(Business $business, Quotation $quote, string $token): void
    {
        $meta = session("stash.$token");
        $from = "stash/{$business->id}/$token";
        if (! $meta || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($from)) {
            return;
        }
        $to = "attachments/{$business->id}/".Str::random(40);
        \Illuminate\Support\Facades\Storage::disk('local')->move($from, $to);
        $a = new Attachment(['path' => $to, 'original_name' => Str::limit($meta['name'], 180), 'mime' => $meta['mime'], 'size' => $meta['size'],
            'expires_at' => now()->addDays(config('vendra.retention.attachments_days'))]);
        $a->business_id = $business->id;
        $quote->attachments()->save($a);
        session()->forget("stash.$token");
    }
}
