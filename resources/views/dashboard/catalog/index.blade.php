@extends('layouts.dashboard')
@section('title', 'Catalog')
@section('heading', 'Catalog')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-gray-600">Only active items appear to customers and the AI. Price changes never alter existing bookings or orders.</p>
    @if ($canManage)<a href="{{ route('dashboard.catalog.create') }}" class="btn btn-primary">+ Add item</a>@endif
</div>
<div class="card mt-4 overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>Item</th><th>Type</th><th>Price</th><th>Details</th><th>Status</th>@if($canManage)<th></th>@endif</tr></thead>
        <tbody>
        @forelse ($items as $i)
            <tr>
                <td><span class="font-medium">{{ $i->name }}</span><span class="block text-xs text-gray-500">{{ $i->category }}</span></td>
                <td>{{ ucfirst($i->type) }}</td>
                <td class="font-medium">{{ peso($i->price_cents) }}</td>
                <td class="text-xs text-gray-600">
                    @if ($i->type === 'service') {{ $i->duration_minutes }} min @if($i->staff->isNotEmpty()) · {{ $i->staff->pluck('name')->implode(', ') }} @endif @if($i->requires_approval) · needs approval @endif
                    @elseif ($i->stock_on_hand !== null) {{ $i->stock_on_hand }} on hand · {{ $i->stock_reserved }} reserved · <strong>{{ $i->availableStock() }} available</strong>
                    @else stock not tracked @endif
                </td>
                <td><x-status :value="$i->is_active ? 'active' : 'inactive'" :label="$i->is_active ? 'Active' : 'Hidden'" /></td>
                @if ($canManage)
                <td class="text-right">
                    <a href="{{ route('dashboard.catalog.edit', $i->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                    @if ($i->type === 'product' && $i->stock_on_hand !== null && $currentMembership->can('stock.adjust'))
                        <details class="mt-2 text-left"><summary class="cursor-pointer text-xs text-indigo-700">Adjust stock</summary>
                            <form method="POST" action="{{ route('dashboard.catalog.stock', $i->id) }}" class="mt-2 space-y-2">@csrf
                                <input type="number" name="delta" class="input" placeholder="+10 or -2" required>
                                <input name="reason" class="input" placeholder="Reason (e.g. delivery received, walk-in sale)" required>
                                <button class="btn btn-secondary btn-sm">Save</button>
                            </form>
                        </details>
                    @endif
                </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="6" class="py-8 text-center text-gray-500">No items yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
