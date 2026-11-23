{{-- English / Filipino switch for the customer pages. Params: $class, $dark --}}
<div class="{{ $class ?? 'flex' }} shrink-0 items-center rounded-full border p-0.5 text-xs font-semibold {{ ($dark ?? false) ? 'border-white/25' : 'border-line bg-white' }}" role="group" aria-label="{{ __('Language') }}">
    @foreach (['en' => 'EN', 'fil' => 'FIL'] as $code => $short)
        @php $on = app()->getLocale() === $code; @endphp
        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" hreflang="{{ $code }}" lang="{{ $code }}" title="{{ \App\Http\Middleware\PageLanguage::LANGUAGES[$code] }}" @if ($on) aria-current="true" @endif
           @class(['rounded-full px-2.5 py-1', 'bg-ink-900 text-white' => $on && ! ($dark ?? false), 'bg-white text-ink-900' => $on && ($dark ?? false), 'opacity-70 hover:opacity-100' => ! $on])>{{ $short }}</a>
    @endforeach
</div>
