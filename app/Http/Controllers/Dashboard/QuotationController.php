<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Quotation;
use App\Services\CustomerService;
use App\Services\Notifier;
use App\Services\QuotationService;
use App\Support\Money;
use Illuminate\Http\Request;

class QuotationController extends DashboardController
{
    public function __construct(private QuotationService $service, private CustomerService $customers) {}

    public function index(Request $request)
    {
        $this->requireModule('quotations');
        $status = $request->query('status');
        $quotes = $this->scopeVisible($this->business()->quotations()->with('customer', 'currentVersion', 'assignee'))
            ->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(25)->withQueryString();

        return view('dashboard.quotations.index', compact('quotes', 'status'));
    }

    public function store(Request $request)
    {
        $this->requireModule('quotations');
        $this->authorizeAbility('transactions.create');
        $data = $request->validate(['requirements' => 'required|string|max:3000', 'name' => 'required|string|max:120',
            'email' => 'nullable|email', 'phone' => 'nullable|string|max:40', 'idempotency_key' => 'required|string|max:64']);
        $customer = $this->customers->resolve($this->business(), $data, 'staff_entry');
        $q = $this->service->submit($this->business(), $customer, $data['requirements'], $data['idempotency_key'], $request->user());

        return redirect()->route('dashboard.quotations.show', $q->id);
    }

    public function show(int $id)
    {
        $q = $this->find($id, 'quotes.draft');

        return view('dashboard.quotations.show', [
            'quote' => $q->load('versions.drafter', 'customer', 'attachments', 'assignee'),
            'canApprove' => $this->membership()->can('quotes.approve'),
            'canAssign' => $this->membership()->can('conversations.assign'),
            'team' => $this->business()->activeStaff()->get(),
            'defaultValid' => now()->addDays((int) $this->business()->setting('quote_valid_days'))->toDateString(),
        ]);
    }

    public function draft(Request $request, int $id)
    {
        $q = $this->find($id, 'quotes.draft');
        $data = $request->validate([
            'lines' => 'required|array|min:1', 'lines.*.description' => 'nullable|string|max:255',
            'lines.*.quantity' => 'nullable|integer|min:1|max:9999', 'lines.*.unit_price' => 'nullable|numeric|min:0|max:10000000',
            'notes' => 'nullable|string|max:2000', 'valid_until' => 'required|date|after_or_equal:today',
        ]);
        $lines = array_map(fn ($l) => ['description' => $l['description'] ?? '', 'quantity' => $l['quantity'] ?? 1, 'unit_price_cents' => Money::toCents($l['unit_price'] ?? 0)], $data['lines']);
        $v = $this->service->draftVersion($q, $lines, $data['notes'] ?? null, $data['valid_until'], $request->user());
        if (! $this->membership()->isOwner()) {
            Notifier::staff($this->business(), 'Quotation '.$q->reference.' v'.$v->version.' needs your approval', 'Total '.Money::format($v->total_cents), route('dashboard.quotations.show', $q->id));
        }

        return back()->with('status', 'Version '.$v->version.' drafted and waiting for owner approval.');
    }

    public function approve(Request $request, int $id)
    {
        $q = $this->find($id, 'quotes.approve');
        $this->service->approveAndSend($q, $request->user());

        return back()->with('status', 'Approved and sent to the customer. They can respond from “Track my request”.');
    }

    public function status(Request $request, int $id)
    {
        $data = $request->validate(['status' => 'required|in:under_review,declined,withdrawn,expired', 'reason' => 'nullable|string|max:255']);
        $q = $this->find($id, $data['status'] === 'under_review' ? 'quotes.draft' : 'transactions.approve');
        $this->service->setStatus($q, $data['status'], $request->user(), $data['reason'] ?? null);

        return back()->with('status', 'Quotation updated.');
    }

    public function assign(Request $request, int $id)
    {
        $this->authorizeAbility('conversations.assign');
        $q = $this->business()->quotations()->findOrFail($id);
        $uid = $request->integer('user_id') ?: null;
        abort_if($uid && ! $this->business()->memberships()->where('user_id', $uid)->where('is_active', true)->exists(), 422);
        $q->update(['assigned_to' => $uid]);
        \App\Services\AuditLogger::log($q->business_id, 'quotation.assigned', $q, ['assigned_to' => $uid]);

        return back()->with('status', 'Assignment updated.');
    }

    public function convert(Request $request, int $id)
    {
        $this->requireModule('service_requests');
        $q = $this->find($id, 'transactions.approve');
        $sr = $this->service->convertToServiceRequest($q, $request->user());

        return redirect()->route('dashboard.service-requests.show', $sr->id)->with('status', 'Converted to service request '.$sr->reference.'.');
    }

    public function attachment(int $id, int $attachmentId)
    {
        $q = $this->find($id, 'quotes.draft');
        $a = $q->attachments()->findOrFail($attachmentId);

        return response()->file(storage_path('app/private/'.$a->path), ['Content-Disposition' => 'inline; filename="'.addslashes($a->original_name).'"']);
    }

    private function find(int $id, string $ability): Quotation
    {
        $this->requireModule('quotations');
        $q = $this->business()->quotations()->findOrFail($id);
        $this->authorizeRecord($q, $ability);

        return $q;
    }
}
