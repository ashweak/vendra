{{-- Business photo gallery. Params: $variant (grid|row|dark), $title, $sectionClass --}}
@if ($gallery->isNotEmpty())
    @php $variant = $variant ?? 'grid'; @endphp
    <section class="{{ $sectionClass ?? 'mx-auto max-w-6xl px-5 py-16 sm:px-8' }}" aria-labelledby="gallery-title">
        <h2 id="gallery-title" class="{{ $variant === 'dark' ? 'font-display text-3xl font-semibold text-white' : 'section-title' }}">{{ $title ?? __('Photos') }}</h2>
        @if ($variant === 'row')
            <div class="-mx-5 mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-3 sm:-mx-8 sm:px-8" tabindex="0" aria-label="{{ __('Photo gallery') }}">
                @foreach ($gallery as $photo)
                    <figure class="w-72 shrink-0 snap-start sm:w-80">
                        <img src="{{ $photo->url() }}" @srcset($photo->path, '320px') decoding="async" alt="{{ $photo->caption ?: __('Photo :n of :business', ['n' => $loop->iteration, 'business' => $business->name]) }}" loading="lazy" class="aspect-[4/3] w-full rounded-3xl object-cover">
                        @if ($photo->caption)<figcaption class="mt-2 px-1 text-sm text-ink-600">{{ $photo->caption }}</figcaption>@endif
                    </figure>
                @endforeach
            </div>
        @else
            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($gallery as $photo)
                    <figure @class(['group relative overflow-hidden rounded-2xl', 'col-span-2 row-span-2' => $loop->first && $gallery->count() >= 5])>
                        <img src="{{ $photo->url() }}" @srcset($photo->path, $loop->first && $gallery->count() >= 5 ? '(min-width: 1024px) 50vw, 100vw' : '(min-width: 1024px) 25vw, 50vw') decoding="async" alt="{{ $photo->caption ?: __('Photo :n of :business', ['n' => $loop->iteration, 'business' => $business->name]) }}" loading="lazy" class="h-full w-full object-cover {{ $loop->first && $gallery->count() >= 5 ? 'aspect-square' : 'aspect-[4/3]' }}">
                        @if ($photo->caption)<figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 pt-8 pb-2.5 text-xs font-medium text-white">{{ $photo->caption }}</figcaption>@endif
                    </figure>
                @endforeach
            </div>
        @endif
    </section>
@endif
