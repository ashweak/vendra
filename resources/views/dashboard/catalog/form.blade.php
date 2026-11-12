@extends('layouts.dashboard')
@section('title', $item->exists ? 'Edit item' : 'Add item')
@section('heading', 'Catalog')
@section('content')
<a href="{{ route('dashboard.catalog.index') }}" class="link text-sm">← Catalog</a>
<h1 class="h1 mt-2">{{ $item->exists ? 'Edit '.$item->name : 'Add a service or product' }}</h1>
<form method="POST" action="{{ $item->exists ? route('dashboard.catalog.update', $item->id) : route('dashboard.catalog.store') }}" class="card mt-4 max-w-2xl space-y-4">
    @csrf @if($item->exists) @method('PUT') @endif
    @unless ($item->exists)
        <fieldset><legend class="label">Type</legend>
            <label class="mr-4 text-sm"><input type="radio" name="type" value="service" @checked(old('type', $item->type) === 'service')> Service (bookable)</label>
            <label class="text-sm"><input type="radio" name="type" value="product" @checked(old('type', $item->type) === 'product')> Product (orderable)</label>
        </fieldset>
    @endunless
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="name">Name</label><input id="name" name="name" class="input" required value="{{ old('name', $item->name) }}"><x-field-error name="name" /></div>
        <div><label class="label" for="category">Category</label><input id="category" name="category" class="input" value="{{ old('category', $item->category) }}"></div>
        <div><label class="label" for="price">Price (₱)</label><input id="price" name="price" type="number" step="0.01" min="0" class="input" required value="{{ old('price', $item->price_cents !== null ? $item->price_cents / 100 : '') }}"><x-field-error name="price" /></div>
        <div><label class="label" for="dur">Duration (minutes, services)</label><input id="dur" name="duration_minutes" type="number" min="15" step="5" class="input" value="{{ old('duration_minutes', $item->duration_minutes) }}"></div>
        @if (! $item->exists || ($item->type === 'product' && $item->stock_on_hand === null))
            <div><label class="label" for="stock">Starting stock (products, optional)</label><input id="stock" name="stock_on_hand" type="number" min="0" class="input" value="{{ old('stock_on_hand') }}"><p class="hint">Leave blank to not track stock.</p></div>
        @endif
    </div>
    <div><label class="label" for="desc">Description</label><textarea id="desc" name="description" rows="3" class="input">{{ old('description', $item->description) }}</textarea></div>
    @if ($team->isNotEmpty() && ($item->type === 'service' || ! $item->exists))
        <fieldset><legend class="label">Who can perform this service? (services)</legend>
            <p class="hint mb-2">Leave all unchecked to allow anyone with a schedule.</p>
            @foreach ($team as $t)<label class="mr-4 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="staff[]" value="{{ $t->id }}" @checked($item->exists && $item->staff->contains($t->id))> {{ $t->name }}</label>@endforeach
        </fieldset>
    @endif
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="requires_approval" value="1" @checked(old('requires_approval', $item->requires_approval))> Bookings for this service need approval</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active (visible to customers and the AI)</label>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
