@extends('layouts.public')
@section('title', __('Privacy notice').' · '.$business->name)
@section('content')
@php
    $contact = $business->privacy_contact ?: $business->email;
    $who = $business->legal_name ?: $business->name;
@endphp
<article class="mx-auto max-w-3xl">
    <x-page-head :eyebrow="__('Privacy notice')" :title="__('How :business handles your information', ['business' => $business->name])" />
    <p class="mt-3 text-sm text-ink-600">{{ __('Under the Data Privacy Act of 2012 (Republic Act No. 10173). Last updated :date.', ['date' => local_time(now(), $business->timezone, 'F j, Y')]) }}</p>

    <div class="mt-8 space-y-7 text-[15px] leading-relaxed text-ink-800">
        <section>
            <h2 class="h2">{{ __('Who is responsible') }}</h2>
            <p class="mt-2">{{ $business->legal_name && $business->legal_name !== $business->name
                    ? __(':who (trading as :business) decides how your information is used.', ['who' => $who, 'business' => $business->name])
                    : __(':who decides how your information is used.', ['who' => $who]) }}
                @if ($business->address) {{ __('Address: :address.', ['address' => $business->address]) }} @endif
                @if ($contact) {{ __('Privacy contact:') }} <a class="link" href="mailto:{{ $contact }}">{{ $contact }}</a>. @endif
            </p>
            <p class="mt-2">{{ __('This page runs on Vendra, which stores the information for :business and doesn’t use it for anything else. Vendra doesn’t send your messages to any outside AI service: the chat’s auto-assistant runs on Vendra’s own server.', ['business' => $business->name]) }}</p>
        </section>

        <section>
            <h2 class="h2">{{ __('What we collect') }}</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>{{ __('Your name, email and, when needed, mobile number.') }}</li>
                <li>{{ __('What you request: bookings, orders (including a delivery address if you choose delivery), quotation requests, service requests and any photo or PDF you attach.') }}</li>
                <li>{{ __('Chat messages you send, and the replies.') }}</li>
                <li>{{ __('Your choice about promotional messages.') }}</li>
                <li>{{ __('Basic technical records (such as your IP address on actions) to keep the service secure.') }}</li>
            </ul>
            <p class="mt-2">{{ __('Please don’t send passwords, PINs, card numbers or health details. We never ask for them.') }}</p>
            @if ($business->messengerConnected())
                <p class="mt-2">{{ __('If you message us on Facebook Messenger, Meta (Facebook) also processes those messages under its own privacy policy. We receive them in the same inbox as this website’s chat.') }}</p>
            @endif
            @if (\App\Services\HumanCheck::turnstileEnabled())
                <p class="mt-2">{{ __('Our forms use Cloudflare Turnstile to stop spam bots. Cloudflare sees your IP address and browser details for that check only.') }}</p>
            @endif
        </section>

        <section>
            <h2 class="h2">{{ __('Why we use it') }}</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>{{ __('To handle what you asked for: confirm, schedule, prepare, deliver and follow up.') }}</li>
                <li>{{ __('To reply to your messages and send you secure tracking links.') }}</li>
                <li>{{ __('To email you a reminder the day before a confirmed booking, with a calendar file (the business can turn this off).') }}</li>
                <li>{{ __('To keep records the business needs, and to prevent abuse.') }}</li>
                <li>{{ __('To send promotions — only if you ticked the box, and you can withdraw that anytime.') }}</li>
            </ul>
            <p class="mt-2">{{ __('We don’t sell your information. We don’t make decisions about you by automated means alone: the auto-assistant only answers questions and prepares drafts that you confirm and staff approve.') }}</p>
        </section>

        <section>
            <h2 class="h2">{{ __('How long we keep it') }}</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>{{ __('Chat conversations: deleted after :months months without activity.', ['months' => $retention['conversations_months']]) }}</li>
                <li>{{ __('Attached photos and PDFs: deleted after :days days unless still needed for an open request.', ['days' => $retention['attachments_days']]) }}</li>
                <li>{{ __('Tracking links: expire after 30 minutes and are removed after :days days.', ['days' => $retention['access_tokens_days']]) }}</li>
                <li>{{ __('Bookings, orders and quotations: kept as long as the business needs them for its records and as the law requires (for example, tax records).') }}</li>
            </ul>
        </section>

        <section>
            <h2 class="h2">{{ __('Your rights') }}</h2>
            <p class="mt-2">{{ __('You have the right to be informed, to access your information, to object, to have it corrected, to have it blocked or deleted, to get a copy you can take elsewhere, and to claim damages. You can:') }}</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li><a class="link" href="{{ route('public.track', $business) }}">{{ __('Open “Track my request”') }}</a> {{ __('with your reference and email, then download your data or ask for a correction or deletion.') }}</li>
                @if ($contact)<li>{{ __('Email') }} <a class="link" href="mailto:{{ $contact }}">{{ $contact }}</a>.</li>@endif
                <li>{{ __('If you’re not satisfied, file a complaint with the National Privacy Commission at') }} <a class="link" href="https://privacy.gov.ph" target="_blank" rel="noopener">privacy.gov.ph</a>.</li>
            </ul>
        </section>

        <section>
            <h2 class="h2">{{ __('Security') }}</h2>
            <p class="mt-2">{{ __('Access is limited to the business’s own team, by role. Owners use two-step sign-in, activity is logged, and connections are encrypted. If a breach affects you, we’ll tell you and the National Privacy Commission as the law requires.') }}</p>
        </section>
    </div>
</article>
@endsection
