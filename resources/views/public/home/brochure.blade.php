{{-- Brochure: centered brochure hero, three quick-action cards, service cards, assistant band, visit info, FAQ. --}}
<div class="bg-[linear-gradient(180deg,var(--color-sun-soft),var(--color-paper)_78%)]">
    <section class="mx-auto max-w-4xl px-5 pt-12 pb-14 text-center sm:px-8 sm:pt-20">
        <x-flash />
        <span class="eyebrow justify-center">{{ $categoryLabel }}</span>
        <h1 class="display mt-6">{{ $business->name }}</h1>
        <p class="lead mx-auto mt-6 max-w-2xl">{{ $intro }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ $primary[1] }}" class="btn btn-sun px-6">{{ $primary[0] }}</a>
            <button type="button" class="btn btn-secondary px-6" data-open-chat>{{ __('Ask our assistant') }}</button>
        </div>
        <p class="mt-6 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-ink-600">
            <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full {{ $openToday ? 'bg-leaf' : 'bg-ink-300' }}"></span>{{ $todayLabel }}</span>
            @if ($business->address)<span class="hidden text-ink-300 sm:inline" aria-hidden="true">|</span><span>{{ $business->address }}</span>@endif
        </p>
    </section>

    @if ($cover)
        <div class="mx-auto max-w-6xl px-5 pb-6 sm:px-8"><img src="{{ $cover }}" @srcset($business->cover_path, '(min-width: 1152px) 1152px, 100vw') alt="{{ $business->name }}" class="aspect-[16/9] w-full rounded-[28px] object-cover sm:aspect-[16/6]"></div>
    @endif
    <section class="mx-auto grid max-w-6xl gap-4 px-5 pb-16 sm:grid-cols-3 sm:px-8" aria-label="{{ __('Quick actions') }}">
        <a href="{{ $primary[1] }}" class="card group flex flex-col p-6 transition hover:-translate-y-0.5 hover:shadow-[0_18px_40px_-24px_rgba(0,0,0,.35)]">
            <span class="icon-chip bg-sun text-ink-900"><x-icon :name="$business->moduleEnabled('appointments') ? 'calendar' : 'bag'" class="h-5 w-5" /></span>
            <span class="mt-5 font-display text-xl font-semibold">{{ $primary[0] }}</span>
            <span class="mt-1 text-sm text-ink-600">{{ __('Pick what you need, review every detail, then confirm.') }}</span>
            <span class="mt-5 text-sm font-semibold underline decoration-sun decoration-2 underline-offset-4">{{ __('Start now') }}</span>
        </a>
        <button type="button" data-open-chat class="card group flex flex-col p-6 text-left transition hover:-translate-y-0.5 hover:shadow-[0_18px_40px_-24px_rgba(0,0,0,.35)]">
            <span class="icon-chip"><x-icon name="chat" class="h-5 w-5" /></span>
            <span class="mt-5 font-display text-xl font-semibold">{{ __('Ask in Taglish') }}</span>
            <span class="mt-1 text-sm text-ink-600">{{ __('Our auto-assistant answers prices and hours. Staff can join anytime.') }}</span>
            <span class="mt-5 text-sm font-semibold underline decoration-sun decoration-2 underline-offset-4">{{ __('Open chat') }}</span>
        </button>
        <a href="{{ route('public.track', $business) }}" class="card group flex flex-col p-6 transition hover:-translate-y-0.5 hover:shadow-[0_18px_40px_-24px_rgba(0,0,0,.35)]">
            <span class="icon-chip"><x-icon name="search" class="h-5 w-5" /></span>
            <span class="mt-5 font-display text-xl font-semibold">{{ __('Track a request') }}</span>
            <span class="mt-1 text-sm text-ink-600">{{ __('Enter your reference and email to get a secure link.') }}</span>
            <span class="mt-5 text-sm font-semibold underline decoration-sun decoration-2 underline-offset-4">{{ __('Track it') }}</span>
        </a>
    </section>
</div>

<section id="catalog" class="mx-auto max-w-6xl px-5 py-16 sm:px-8">
    <div class="mx-auto max-w-2xl text-center">
        <span class="eyebrow justify-center">{{ __('Services & prices') }}</span>
        <h2 class="section-title mt-4">{{ __('Everything we') }} <span class="mark">{{ __('offer') }}</span></h2>
        @include('public.home.parts.search', ['class' => 'mx-auto mt-6 flex max-w-md gap-2', 'inputClass' => 'rounded-full'])
    </div>
    @if ($items->isEmpty())
        <div class="mt-10"><x-empty title="{{ __('Nothing found') }}">{{ __('Try another search, or ask us in the chat.') }}</x-empty></div>
    @else
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                <article class="card flex flex-col p-6">
                    @if ($item->image_path)<img src="{{ $item->imageUrl() }}" @srcset($item->image_path, '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw') alt="{{ $item->name }}" loading="lazy" decoding="async" class="-mx-2 -mt-2 mb-5 aspect-[4/3] w-[calc(100%+1rem)] rounded-2xl object-cover">@endif
                    <div class="flex items-center gap-3">
                        <span class="icon-chip h-9 w-9"><x-icon :name="$item->type === 'service' ? 'calendar' : 'bag'" class="h-4 w-4" /></span>
                        <span class="text-xs font-semibold tracking-wider text-ink-500 uppercase">{{ $item->category ?: ($item->type === 'service' ? __('Service') : __('Product')) }}</span>
                    </div>
                    <h3 class="mt-4 font-display text-xl font-semibold">{{ $item->name }}</h3>
                    <p class="mt-1 mb-6 text-sm text-ink-600">@include('public.home.parts.meta')</p>
                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-5">
                        <span class="font-display text-2xl font-semibold whitespace-nowrap">{{ peso($item->price_cents) }}</span>
                        @include('public.home.parts.action', ['btn' => 'btn btn-sun btn-sm shrink-0', 'showUnavailable' => true])
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@include('public.home.parts.gallery', ['title' => __('A look inside'), 'sectionClass' => 'mx-auto max-w-6xl px-5 pb-16 sm:px-8'])

<section class="bg-tile">
    <div class="mx-auto grid max-w-6xl items-center gap-12 px-5 py-16 sm:px-8 lg:grid-cols-2">
        <div class="dots order-2 rounded-[28px] bg-paper px-6 py-10 lg:order-1">@include('public.home.parts.phone')</div>
        <div class="order-1 lg:order-2">
            <span class="eyebrow">{{ __('Ask anything') }}</span>
            <h2 class="section-title mt-4">{{ __('Questions? Just ask, in') }} <span class="mark">{{ __('Taglish') }}</span>.</h2>
            <ul class="mt-6 space-y-3 text-ink-700">
                @foreach ([__('Prices and open times come straight from our list.'), __('It prepares a draft — nothing is booked until you confirm.'), __('A real person takes over whenever you need one.')] as $line)
                    <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sun text-ink-900"><x-icon name="check" class="h-3.5 w-3.5" /></span>{{ $line }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn btn-primary mt-8" data-open-chat>{{ __('Start a chat') }}</button>
        </div>
    </div>
</section>

<section class="mx-auto grid max-w-6xl gap-6 px-5 py-16 sm:px-8 lg:grid-cols-2">
    <div class="card p-6 sm:p-8">
        <h2 class="font-display text-2xl font-semibold">{{ __('Opening hours') }}</h2>
        @include('public.home.parts.hours', ['class' => 'mt-5'])
    </div>
    <div class="card flex flex-col p-6 sm:p-8">
        <h2 class="font-display text-2xl font-semibold">{{ __('Contact & how it works') }}</h2>
        @include('public.home.parts.contact', ['class' => 'mt-5', 'iconClass' => 'text-sun-ink'])
        <div class="mt-6 border-t border-line pt-6">@include('public.home.parts.steps', ['numClass' => 'bg-sun text-ink-900'])</div>
    </div>
</section>

@if ($knowledge->isNotEmpty())
    <section class="mx-auto max-w-3xl px-5 pb-8 sm:px-8">
        <h2 class="section-title text-center">{{ __('Good to know') }}</h2>
        <p class="lead mt-3 text-center">{{ __('Policies and answers to common questions.') }}</p>
        @include('public.home.parts.faq', ['class' => 'mt-8 space-y-3'])
    </section>
@endif
