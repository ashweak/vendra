@if ($business->phone)
    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="btn {{ $style ?? 'btn-primary' }} btn-sm h-10 min-h-0 w-10 shrink-0 px-0 sm:w-auto sm:px-4" aria-label="{{ __('Call :phone', ['phone' => $business->phone]) }}"><x-icon name="phone" class="h-4 w-4" /><span class="hidden sm:inline">{{ $business->phone }}</span></a>
@endif
