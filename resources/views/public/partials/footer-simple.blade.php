{{-- Footer: simple light bar (Classic, Panel). --}}
    <footer class="border-t border-line bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 py-6 pr-24 pl-5 text-xs text-ink-500 sm:px-8">
            <span class="flex flex-col gap-1">
                <span>© {{ now()->year }} {{ $business->legal_name ?: $business->name }}@if ($business->registration_no) · {{ __('Reg. no. :no', ['no' => $business->registration_no]) }}@endif</span>
                @if ($business->address)<span>{{ $business->address }}</span>@endif
                <a href="{{ route('public.privacy', $business) }}" class="w-fit underline underline-offset-2 hover:text-ink-900">{{ __('Privacy notice') }}</a>
            </span>
            <span class="flex items-center gap-4">@include('public.partials.lang-switch')<a href="{{ route('landing') }}" class="inline-flex items-center gap-2 hover:text-ink-900">{{ __('Powered by') }} <x-logo /></a></span>
        </div>
    </footer>
