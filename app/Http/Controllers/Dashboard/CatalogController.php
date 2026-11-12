<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\CatalogItem;
use App\Services\AuditLogger;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Http\Request;

class CatalogController extends DashboardController
{
    public function index()
    {
        $this->authorizeAbility('catalog.view');

        return view('dashboard.catalog.index', [
            'items' => $this->business()->catalogItems()->with('staff')->orderBy('type')->orderBy('category')->orderBy('name')->get(),
            'canManage' => $this->membership()->can('catalog.manage'),
        ]);
    }

    public function create()
    {
        $this->authorizeAbility('catalog.manage');

        return view('dashboard.catalog.form', ['item' => new CatalogItem(['type' => 'service', 'is_active' => true]), 'team' => $this->business()->activeStaff()->get()]);
    }

    public function store(Request $request)
    {
        $this->authorizeAbility('catalog.manage');
        $item = new CatalogItem($this->validated($request));
        $item->business_id = $this->business()->id;
        if ($item->type === 'product' && $request->filled('stock_on_hand')) {
            $item->stock_on_hand = (int) $request->input('stock_on_hand');
        }
        $item->save();
        $item->staff()->sync($this->staffIds($request));
        AuditLogger::log($item->business_id, 'catalog.created', $item, ['name' => $item->name, 'price_cents' => $item->price_cents]);

        return redirect()->route('dashboard.catalog.index')->with('status', 'Item added.');
    }

    public function edit(int $id)
    {
        $this->authorizeAbility('catalog.manage');

        return view('dashboard.catalog.form', ['item' => $this->business()->catalogItems()->findOrFail($id), 'team' => $this->business()->activeStaff()->get()]);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeAbility('catalog.manage');
        $item = $this->business()->catalogItems()->findOrFail($id);
        $before = $item->only(['name', 'price_cents', 'is_active']);
        $item->fill($this->validated($request, $item));
        if ($item->type === 'product' && $item->stock_on_hand === null && $request->filled('stock_on_hand')) {
            $item->stock_on_hand = (int) $request->input('stock_on_hand'); // start tracking stock
        }
        $item->save();
        $item->staff()->sync($this->staffIds($request));
        // Existing transactions keep their agreed snapshot prices (P6).
        AuditLogger::log($item->business_id, 'catalog.updated', $item, ['before' => $before, 'after' => $item->only(['name', 'price_cents', 'is_active'])]);

        return redirect()->route('dashboard.catalog.index')->with('status', 'Item updated. Existing bookings and orders keep their agreed prices.');
    }

    public function adjustStock(Request $request, int $id, OrderService $orders)
    {
        $this->authorizeAbility('stock.adjust');
        $data = $request->validate(['delta' => 'required|integer|not_in:0|between:-9999,9999', 'reason' => 'required|string|max:255']);
        $item = $this->business()->catalogItems()->products()->findOrFail($id);
        abort_if($item->stock_on_hand === null, 422, 'Stock is not tracked for this product.');
        $orders->adjustStock($item, (int) $data['delta'], $request->user(), $data['reason']);

        return back()->with('status', 'Stock updated.');
    }

    private function validated(Request $request, ?CatalogItem $existing = null): array
    {
        $data = $request->validate([
            'type' => ($existing ? 'prohibited' : 'required').'|in:service,product',
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:60',
            'price' => 'required|numeric|min:0|max:1000000',
            'duration_minutes' => 'nullable|integer|min:15|max:600',
            'requires_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'stock_on_hand' => 'nullable|integer|min:0|max:100000',
            'staff' => 'nullable|array',
        ]);
        $out = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'price_cents' => Money::toCents($data['price']),
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'requires_approval' => $request->boolean('requires_approval'),
            'is_active' => $request->boolean('is_active'),
        ];
        if (! $existing) {
            $out['type'] = $data['type'];
        }
        if (($existing?->type ?? $data['type']) === 'service' && empty($out['duration_minutes'])) {
            $out['duration_minutes'] = 60;
        }

        return $out;
    }

    private function staffIds(Request $request): array
    {
        $valid = $this->business()->memberships()->where('is_active', true)->pluck('user_id')->all();

        return array_values(array_intersect(array_map('intval', (array) $request->input('staff', [])), $valid));
    }
}
