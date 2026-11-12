<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\CustomerService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AppointmentController extends DashboardController
{
    use Concerns\AssignsRecords;

    protected function assignableQuery()
    {
        return $this->business()->appointments();
    }

    public function __construct(private AppointmentService $service, private CustomerService $customers) {}

    /** Agenda view by day (mobile-friendly), filterable by status. */
    public function index(Request $request)
    {
        $this->requireModule('appointments');
        $b = $this->business();
        $date = CarbonImmutable::parse($request->query('date', CarbonImmutable::now($b->timezone)->toDateString()), $b->timezone)->startOfDay();
        $status = $request->query('status');

        $q = $this->scopeVisible($b->appointments()->with('customer', 'staff'));
        if ($status) {
            $q->where('status', $status)->orderBy('starts_at');
        } else {
            $q->whereBetween('starts_at', [$date->utc(), $date->addDay()->utc()])->orderBy('starts_at');
        }

        return view('dashboard.appointments.index', [
            'appointments' => $q->limit(200)->get(),
            'date' => $date,
            'status' => $status,
            'pendingCount' => $this->scopeVisible($b->appointments()->where('status', 'pending_approval'))->count(),
        ]);
    }

    public function create()
    {
        $this->requireModule('appointments');
        $this->authorizeAbility('transactions.create');
        $b = $this->business();

        return view('dashboard.appointments.create', [
            'services' => $b->catalogItems()->services()->active()->orderBy('name')->get(),
            'team' => $b->activeStaff()->get(),
            'today' => CarbonImmutable::now($b->timezone)->toDateString(),
        ]);
    }

    /** Walk-in and phone appointments go into the same calendar (P5). */
    public function store(Request $request)
    {
        $this->requireModule('appointments');
        $this->authorizeAbility('transactions.create');
        $data = $request->validate([
            'catalog_item_id' => 'required|integer', 'date' => 'required|date_format:Y-m-d', 'time' => 'required|date_format:H:i',
            'staff_id' => 'nullable|integer', 'source' => 'required|in:walk_in,phone',
            'name' => 'required|string|max:120', 'email' => 'nullable|email', 'phone' => 'nullable|string|max:40', 'notes' => 'nullable|string|max:1000',
            'idempotency_key' => 'required|string|max:64',
        ]);
        $b = $this->business();
        $staffId = $data['staff_id'] ?? null;
        if ($staffId) {
            abort_unless($b->memberships()->where('user_id', $staffId)->where('is_active', true)->exists(), 422);
        }
        // Staff create bookings for themselves unless they are owner/manager.
        if (! $this->membership()->isManagerOrOwner()) {
            $staffId = auth()->id();
        }
        $customer = $this->customers->resolve($b, $data, 'staff_entry');
        $a = $this->service->book($b, $customer, [
            'catalog_item_id' => $data['catalog_item_id'], 'start' => $data['date'].' '.$data['time'], 'staff_id' => $staffId,
            'notes' => $data['notes'] ?? null, 'source' => $data['source'], 'idempotency_key' => $data['idempotency_key'],
        ], $request->user());

        return redirect()->route('dashboard.appointments.show', $a->id)->with('status', 'Booking '.$a->reference.' saved.');
    }

    public function show(int $id)
    {
        $a = $this->find($id, 'work.complete');

        return view('dashboard.appointments.show', [
            'appointment' => $a->load('customer', 'staff', 'creator'),
            'canApprove' => $this->membership()->can('transactions.approve'),
            'logs' => $this->business()->auditLogs()->where('subject_type', 'Appointment')->where('subject_id', $a->id)->latest()->get(),
        ]);
    }

    public function action(Request $request, int $id)
    {
        $data = $request->validate(['action' => 'required|in:confirmed,declined,cancelled,completed,no_show', 'reason' => 'nullable|string|max:255']);
        $ability = in_array($data['action'], ['confirmed', 'declined'], true) ? 'transactions.approve' : 'work.complete';
        $a = $this->find($id, $ability);
        if (in_array($data['action'], ['declined', 'cancelled'], true) && ! $data['reason']) {
            return back()->withErrors(['reason' => 'Please give a reason.']);
        }
        $this->service->transition($a, $data['action'], $request->user(), $data['reason'] ?? null);

        return back()->with('status', 'Booking updated.');
    }

    public function reschedule(Request $request, int $id)
    {
        $a = $this->find($id, 'transactions.approve');
        $data = $request->validate(['date' => 'required|date_format:Y-m-d', 'time' => 'required|date_format:H:i', 'reason' => 'nullable|string|max:255']);
        $new = $this->service->reschedule($a, $data['date'].' '.$data['time'], null, $request->user(), $data['reason'] ?? null);

        return redirect()->route('dashboard.appointments.show', $new->id)->with('status', 'Rescheduled. New reference '.$new->reference.'.');
    }

    private function find(int $id, string $ability): Appointment
    {
        $this->requireModule('appointments');
        $a = $this->business()->appointments()->findOrFail($id);
        $this->authorizeRecord($a, $ability);

        return $a;
    }
}
