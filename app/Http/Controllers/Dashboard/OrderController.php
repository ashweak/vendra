<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Order;
use App\Services\CustomerService;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Http\Request;

class OrderController extends DashboardController
{
    use Concerns\AssignsRecords;

    protected function assignableQuery()
    {
        return $this->business()->orders();
    }

    public function __construct(private OrderService $service, private CustomerService $customers) {}

    public function index(Request $request)
    {
        $this->requireModule('orders');
        $status = $request->query('status');
        $orders = $this->scopeVisible($this->business()->orders()->with('customer'))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()->paginate(25)->withQueryString();

        return view('dashboard.orders.index', compact('orders', 'status'));
    }

    public function create()
    {
        $this->requireModule('orders');
        $this->authorizeAbility('transactions.create');

        return view('dashboard.orders.create', ['products' => $this->business()->catalogItems()->products()->active()->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->requireModule('orders');
        $this->authorizeAbility('transactions.create');
        $data = $request->validate([
            'items' => 'required|array', 'items.*' => 'nullable|integer|min:0|max:999',
            'fulfillment_method' => 'required|in:pickup,delivery', 'delivery_address' => 'nullable|string|max:500',
            'name' => 'required|string|max:120', 'email' => 'nullable|email', 'phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:1000', 'idempotency_key' => 'required|string|max:64',
        ]);
        $customer = $this->customers->resolve($this->business(), $data, 'staff_entry');
        $o = $this->service->place($this->business(), $customer, array_map('intval', $data['items']), $data['fulfillment_method'],
            $data['delivery_address'] ?? null, null, $data['idempotency_key'], $request->user(), $data['notes'] ?? null);

        return redirect()->route('dashboard.orders.show', $o->id)->with('status', 'Order '.$o->reference.' saved.');
    }

    public function show(int $id)
    {
        $o = $this->find($id, 'work.complete');

        return view('dashboard.orders.show', [
            'order' => $o->load('items', 'customer'),
            'canApprove' => $this->membership()->can('transactions.approve'),
            'canVerify' => $this->membership()->can('payments.verify'),
            'logs' => $this->business()->auditLogs()->where('subject_type', 'Order')->where('subject_id', $o->id)->latest()->get(),
        ]);
    }

    public function action(Request $request, int $id)
    {
        $data = $request->validate(['action' => 'required|in:confirmed,declined,preparing,ready_for_pickup,dispatched,completed,cancelled', 'reason' => 'nullable|string|max:255']);
        $ability = in_array($data['action'], ['confirmed', 'declined'], true) ? 'transactions.approve' : 'work.complete';
        $o = $this->find($id, $ability);
        $this->service->transition($o, $data['action'], $request->user(), $data['reason'] ?? null);

        return back()->with('status', 'Order updated.');
    }

    public function deliveryFee(Request $request, int $id)
    {
        $o = $this->find($id, 'transactions.approve');
        $data = $request->validate(['fee' => 'required|numeric|min:0|max:100000']);
        $this->service->setDeliveryFee($o, Money::toCents($data['fee']), $request->user());

        return back()->with('status', 'Delivery fee set. The customer sees the updated total.');
    }

    public function verifyPayment(Request $request, int $id)
    {
        $o = $this->find($id, 'payments.verify');
        $data = $request->validate(['reason' => 'required|string|max:255']);
        $this->service->verifyPayment($o, $request->user(), $data['reason']);

        return back()->with('status', 'Payment marked as verified.');
    }

    private function find(int $id, string $ability): Order
    {
        $this->requireModule('orders');
        $o = $this->business()->orders()->findOrFail($id);
        $this->authorizeRecord($o, $ability);

        return $o;
    }
}
