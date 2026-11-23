{{-- Footer: dark, with columns (Brochure, Spotlight, Café, Shop). --}}
<footer class="bg-ink-900 text-white">
    <div class="mx-auto grid max-w-6xl gap-8 px-5 pt-12 pb-8 sm:grid-cols-2 sm:px-8 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <p class="font-display text-xl font-bold">{{ $business->name }}</p>
            @if ($business->description)<p class="mt-2 max-w-sm text-sm text-white/70">{{ \Illuminate\Support\Str::limit($business->description, 160) }}</p>@endif
            <button type="button" class="btn btn-sun btn-sm mt-5" data-open-chat>{{ __('Message us') }}</button>
        </div>
        <div class="text-sm">
            <p class="font-semibold">{{ __('Visit') }}</p>
            <p class="mt-2 text-white/70">{{ $statusLine }}</p>
            @if ($business->address)<p class="mt-1 text-white/70">{{ $business->address }}</p>@endif
        </div>
        <div class="text-sm">
            <p class="font-semibold">{{ __('Contact') }}</p>
            <ul class="mt-2 space-y-1 text-white/70">
                @if ($business->phone)<li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="hover:text-white">{{ $business->phone }}</a></li>@endif
                @if ($business->email)<li><a href="mailto:{{ $business->email }}" class="break-all hover:text-white">{{ $business->email }}</a></li>@endif
                <li><a href="{{ route('public.track', $business) }}" class="hover:text-white">{{ __('Track my request') }}</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 py-5 pr-24 pl-5 text-xs text-white/60 sm:px-8">
            <span>© {{ now()->year }} {{ $business->legal_name ?: $business->name }}@if ($business->registration_no) · {{ __('Reg. no. :no', ['no' => $business->registration_no]) }}@endif · <a href="{{ route('public.privacy', $business) }}" class="underline underline-offset-2 hover:text-white">{{ __('Privacy notice') }}</a></span>
            <span class="flex items-center gap-4">@include('public.partials.lang-switch', ['dark' => true])<a href="{{ route('landing') }}" class="hover:text-white">{{ __('Powered by Vendra') }}</a></span>
        </div>
    </div>
</footer>
