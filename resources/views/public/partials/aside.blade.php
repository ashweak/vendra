<aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
    <div class="notch art-warm aspect-[4/3]" style="--nw: 46%; --nh: 46px; --nr: 18px">
        <span class="notch-slot"><span class="eyebrow">Need help?</span></span>
        <div class="absolute inset-x-4 bottom-4 z-[6] rounded-xl bg-white/90 p-4 backdrop-blur">
            <p class="text-xs text-ink-500">Prefer to ask first?</p>
            <button type="button" class="mt-1 inline-flex items-center gap-2 text-sm font-medium" data-open-chat><x-icon name="chat" class="h-4 w-4" /> Chat with our assistant</button>
        </div>
    </div>
    <div class="tile">
        <p class="text-sm font-semibold">What happens next</p>
        <ol class="mt-3 space-y-3 text-sm text-ink-600">
            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-semibold text-ink-900">1</span>You review every detail before anything is sent.</li>
            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-semibold text-ink-900">2</span>{{ $business->name }} confirms or follows up with you.</li>
            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-semibold text-ink-900">3</span>Track it anytime with a secure link sent to your email.</li>
        </ol>
    </div>
    @if ($business->phone)
    <div class="flex items-center gap-3 px-1">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-tile"><x-icon name="phone" class="h-4 w-4" /></span>
        <span class="leading-tight"><span class="block text-[11px] text-ink-500">Call any time</span><span class="block text-sm font-semibold">{{ $business->phone }}</span></span>
    </div>
    @endif
</aside>
