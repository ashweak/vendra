<aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
    <div class="rounded-[22px] bg-sun p-6">
        <p class="font-display text-lg font-bold">What happens next</p>
        <ol class="mt-4 space-y-3 text-sm">
            @foreach (['You review every detail before anything is sent.', $business->name.' confirms or follows up with you.', 'Track it anytime with a secure link sent to your email.'] as $i => $step)
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink-900 text-xs font-bold text-sun">{{ $i + 1 }}</span>{{ $step }}</li>
            @endforeach
        </ol>
    </div>
    <div class="dots rounded-[22px] bg-tile p-5">
        <div class="rounded-2xl bg-white p-4">
            <p class="text-xs text-ink-500">Prefer to ask first?</p>
            <button type="button" class="mt-1 inline-flex items-center gap-2 text-sm font-semibold" data-open-chat><span class="icon-chip h-8 w-8"><x-icon name="chat" class="h-4 w-4" /></span> Chat with our assistant</button>
        </div>
        @if ($business->phone)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business->phone) }}" class="mt-3 flex items-center gap-3 rounded-2xl bg-white p-4 text-sm font-semibold"><span class="icon-chip h-8 w-8"><x-icon name="phone" class="h-4 w-4" /></span>{{ $business->phone }}</a>
        @endif
    </div>
</aside>
