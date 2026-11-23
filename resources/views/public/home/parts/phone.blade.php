{{-- Chat preview phone, using the first catalog item as the example question. --}}
@if ($featured)
    <x-phone-chat :name="$business->name" :ask="'Magkano po ang '.mb_strtolower($featured->name).'?'"
        :answer="'Ang '.$featured->name.' po ay '.peso($featured->price_cents).'. Gusto n’yo po bang mag-'.($featured->type === 'service' ? 'book' : 'order').'?'"
        :item="$featured->name" :price="peso($featured->price_cents)"
        :meta="$featured->type === 'service' ? __(':minutes min', ['minutes' => $featured->duration_minutes]) : __('Pickup or delivery')" />
@else
    <x-phone-chat :name="$business->name" />
@endif
