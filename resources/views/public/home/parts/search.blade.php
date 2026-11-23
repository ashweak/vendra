{{-- Catalog search. Params: $class (form), $inputClass, $buttonClass, $placeholder --}}
<form method="GET" action="#catalog" class="{{ $class ?? 'flex gap-2' }}" role="search">
    <label for="q-{{ $searchId ?? 'main' }}" class="sr-only">{{ __('Search') }}</label>
    <input id="q-{{ $searchId ?? 'main' }}" name="q" value="{{ $q }}" class="input {{ $inputClass ?? '' }}" placeholder="{{ $placeholder ?? __('Search the menu…') }}">
    <button class="{{ $buttonClass ?? 'btn btn-secondary px-3.5' }}" aria-label="{{ __('Search') }}"><x-icon name="search" class="h-4 w-4" /></button>
</form>
