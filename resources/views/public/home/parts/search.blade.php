{{-- Catalog search. Params: $class (form), $inputClass, $buttonClass, $placeholder --}}
<form method="GET" action="#catalog" class="{{ $class ?? 'flex gap-2' }}" role="search">
    @if (request()->query('theme'))<input type="hidden" name="theme" value="{{ \App\Support\Themes::active($business) }}">@endif
    <label for="q-{{ $searchId ?? 'main' }}" class="sr-only">Search</label>
    <input id="q-{{ $searchId ?? 'main' }}" name="q" value="{{ $q }}" class="input {{ $inputClass ?? '' }}" placeholder="{{ $placeholder ?? 'Search the menu…' }}">
    <button class="{{ $buttonClass ?? 'btn btn-secondary px-3.5' }}" aria-label="Search"><x-icon name="search" class="h-4 w-4" /></button>
</form>
