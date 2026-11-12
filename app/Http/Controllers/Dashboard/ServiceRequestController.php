<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\ServiceRequest;
use App\Services\AuditLogger;
use App\Services\CustomerService;
use App\Services\ServiceRequestService;
use Illuminate\Http\Request;

class ServiceRequestController extends DashboardController
{
    public function __construct(private ServiceRequestService $service, private CustomerService $customers) {}

    public function index(Request $request)
    {
        $this->requireModule('service_requests');
        $status = $request->query('status');
        $requests = $this->scopeVisible($this->business()->serviceRequests()->with('customer', 'assignee'))
            ->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(25)->withQueryString();

        return view('dashboard.service-requests.index', compact('requests', 'status'));
    }

    public function store(Request $request)
    {
        $this->requireModule('service_requests');
        $this->authorizeAbility('transactions.create');
        $data = $request->validate(['description' => 'required|string|max:3000', 'location' => 'nullable|string|max:255',
            'preferred_date' => 'nullable|date', 'name' => 'required|string|max:120', 'email' => 'nullable|email',
            'phone' => 'nullable|string|max:40', 'idempotency_key' => 'required|string|max:64']);
        $customer = $this->customers->resolve($this->business(), $data, 'staff_entry');
        $sr = $this->service->submit($this->business(), $customer, $data, $request->user());

        return redirect()->route('dashboard.service-requests.show', $sr->id);
    }

    public function show(int $id)
    {
        $sr = $this->find($id, 'work.complete');

        return view('dashboard.service-requests.show', [
            'sr' => $sr->load('customer', 'assignee', 'quotation', 'appointment'),
            'canApprove' => $this->membership()->can('transactions.approve'),
            'canAssign' => $this->membership()->can('conversations.assign'),
            'team' => $this->business()->activeStaff()->get(),
            'transitions' => ServiceRequestService::TRANSITIONS[$sr->status] ?? [],
        ]);
    }

    public function action(Request $request, int $id)
    {
        $data = $request->validate(['action' => 'required|string', 'reason' => 'nullable|string|max:255']);
        $approvalSteps = ['accepted', 'declined', 'under_review', 'awaiting_customer', 'cancelled'];
        $sr = $this->find($id, in_array($data['action'], $approvalSteps, true) ? 'transactions.approve' : 'work.complete');
        $this->service->transition($sr, $data['action'], $request->user(), $data['reason'] ?? null);

        return back()->with('status', 'Request updated.');
    }

    public function assign(Request $request, int $id)
    {
        $this->authorizeAbility('conversations.assign');
        $sr = $this->business()->serviceRequests()->findOrFail($id);
        $uid = $request->integer('user_id') ?: null;
        abort_if($uid && ! $this->business()->memberships()->where('user_id', $uid)->where('is_active', true)->exists(), 422);
        $sr->update(['assigned_to' => $uid]);
        AuditLogger::log($sr->business_id, 'service_request.assigned', $sr, ['assigned_to' => $uid]);

        return back()->with('status', 'Assignment updated.');
    }

    private function find(int $id, string $ability): ServiceRequest
    {
        $this->requireModule('service_requests');
        $sr = $this->business()->serviceRequests()->findOrFail($id);
        $this->authorizeRecord($sr, $ability);

        return $sr;
    }
}
