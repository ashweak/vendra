{{-- Spam protection for public forms: a hidden honeypot field, plus Cloudflare Turnstile when it's configured. --}}
<div class="hp-field" aria-hidden="true">
    <label for="{{ \App\Services\HumanCheck::HONEYPOT }}-{{ $id = \Illuminate\Support\Str::random(6) }}">{{ __('Leave this empty') }}</label>
    <input type="text" id="{{ \App\Services\HumanCheck::HONEYPOT }}-{{ $id }}" name="{{ \App\Services\HumanCheck::HONEYPOT }}" value="" tabindex="-1" autocomplete="off">
</div>
@if (\App\Services\HumanCheck::needsChallenge())
    <div class="cf-turnstile my-3" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="light" data-language="{{ app()->getLocale() === 'fil' ? 'tl' : 'auto' }}"></div>
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
