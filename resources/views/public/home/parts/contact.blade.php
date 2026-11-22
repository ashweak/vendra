{{-- Contact details. Params: $class, $gap, $iconClass --}}
<ul class="{{ $class ?? '' }} {{ $gap ?? 'space-y-4' }} text-sm">
    @if ($business->phone)<li class="flex gap-3"><x-icon name="phone" class="h-5 w-5 shrink-0 {{ $iconClass ?? '' }}" /><a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="hover:underline">{{ $business->phone }}</a></li>@endif
    @if ($business->email)<li class="flex gap-3"><x-icon name="chat" class="h-5 w-5 shrink-0 {{ $iconClass ?? '' }}" /><a href="mailto:{{ $business->email }}" class="break-all hover:underline">{{ $business->email }}</a></li>@endif
    @if ($business->address)<li class="flex gap-3"><x-icon name="pin" class="h-5 w-5 shrink-0 {{ $iconClass ?? '' }}" /><span>{{ $business->address }}</span></li>@endif
    <li class="flex gap-3"><x-icon name="clock" class="h-5 w-5 shrink-0 {{ $iconClass ?? '' }}" /><span>Staff reply in chat: {{ $business->setting('response_hours') }}</span></li>
</ul>
