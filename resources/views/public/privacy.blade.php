@extends('layouts.public')
@section('title', 'Privacy notice · '.$business->name)
@section('content')
@php
    $contact = $business->privacy_contact ?: $business->email;
    $who = $business->legal_name ?: $business->name;
@endphp
<article class="mx-auto max-w-3xl">
    <x-page-head eyebrow="Privacy notice" :title="'How '.$business->name.' handles your information'" />
    <p class="mt-3 text-sm text-ink-600">Under the Data Privacy Act of 2012 (Republic Act No. 10173). Last updated {{ now()->format('F j, Y') }}.</p>

    <div class="mt-8 space-y-7 text-[15px] leading-relaxed text-ink-800">
        <section>
            <h2 class="h2">Who is responsible</h2>
            <p class="mt-2">{{ $who }}@if ($business->legal_name && $business->legal_name !== $business->name) (trading as {{ $business->name }})@endif decides how your information is used.
                @if ($business->address) Address: {{ $business->address }}. @endif
                @if ($contact) Privacy contact: <a class="link" href="mailto:{{ $contact }}">{{ $contact }}</a>. @endif
            </p>
            <p class="mt-2">This page runs on Vendra, which stores the information for {{ $business->name }} and doesn’t use it for anything else. Vendra doesn’t send your messages to any outside AI service: the chat’s auto-assistant runs on Vendra’s own server.</p>
        </section>

        <section>
            <h2 class="h2">What we collect</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Your name, email and, when needed, mobile number.</li>
                <li>What you request: bookings, orders (including a delivery address if you choose delivery), quotation requests, service requests and any photo or PDF you attach.</li>
                <li>Chat messages you send, and the replies.</li>
                <li>Your choice about promotional messages.</li>
                <li>Basic technical records (such as your IP address on actions) to keep the service secure.</li>
            </ul>
            <p class="mt-2">Please don’t send passwords, PINs, card numbers or health details. We never ask for them.</p>
        </section>

        <section>
            <h2 class="h2">Why we use it</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>To handle what you asked for: confirm, schedule, prepare, deliver and follow up.</li>
                <li>To reply to your messages and send you secure tracking links.</li>
                <li>To keep records the business needs, and to prevent abuse.</li>
                <li>To send promotions — only if you ticked the box, and you can withdraw that anytime.</li>
            </ul>
            <p class="mt-2">We don’t sell your information. We don’t make decisions about you by automated means alone: the auto-assistant only answers questions and prepares drafts that you confirm and staff approve.</p>
        </section>

        <section>
            <h2 class="h2">How long we keep it</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Chat conversations: deleted after {{ $retention['conversations_months'] }} months without activity.</li>
                <li>Attached photos and PDFs: deleted after {{ $retention['attachments_days'] }} days unless still needed for an open request.</li>
                <li>Tracking links: expire after 30 minutes and are removed after {{ $retention['access_tokens_days'] }} days.</li>
                <li>Bookings, orders and quotations: kept as long as the business needs them for its records and as the law requires (for example, tax records).</li>
            </ul>
        </section>

        <section>
            <h2 class="h2">Your rights</h2>
            <p class="mt-2">You have the right to be informed, to access your information, to object, to have it corrected, to have it blocked or deleted, to get a copy you can take elsewhere, and to claim damages. You can:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li><a class="link" href="{{ route('public.track', $business) }}">Open “Track my request”</a> with your reference and email, then download your data or ask for a correction or deletion.</li>
                @if ($contact)<li>Email <a class="link" href="mailto:{{ $contact }}">{{ $contact }}</a>.</li>@endif
                <li>If you’re not satisfied, file a complaint with the National Privacy Commission at <a class="link" href="https://privacy.gov.ph" target="_blank" rel="noopener">privacy.gov.ph</a>.</li>
            </ul>
        </section>

        <section>
            <h2 class="h2">Security</h2>
            <p class="mt-2">Access is limited to the business’s own team, by role. Owners use two-step sign-in, activity is logged, and connections are encrypted. If a breach affects you, we’ll tell you and the National Privacy Commission as the law requires.</p>
        </section>
    </div>
</article>
@endsection
