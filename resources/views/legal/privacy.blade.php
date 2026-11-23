@extends('legal.layout')
@section('title', 'Privacy notice')
@section('content')
@php $contact = config('vendra.contact_email'); @endphp
<span class="eyebrow">Privacy notice</span>
<h1 class="section-title mt-4">Vendra privacy notice</h1>
<p class="mt-3 text-sm text-ink-600">Under the Data Privacy Act of 2012 (Republic Act No. 10173). This is a student portfolio project; the public demo uses fictional data only. Not legal advice.</p>

<div class="mt-8 space-y-7 text-[15px] leading-relaxed text-ink-800">
    <section>
        <h2 class="h2">Two roles</h2>
        <p class="mt-2"><strong>For business accounts</strong> (owners, managers, staff), Vendra decides how account information is used: your name, email, password (stored only as a one-way hash), two-step sign-in secret (encrypted), memberships and an activity log.</p>
        <p class="mt-2"><strong>For a business’s customers</strong>, the business decides; Vendra only stores and processes the information for it. Each business page has its own privacy notice, linked in its footer.</p>
    </section>
    <section>
        <h2 class="h2">What Vendra does and doesn’t do</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>No outside AI service: the chat auto-assistant runs on Vendra’s own server, so messages aren’t sent to Google, OpenAI or others.</li>
            <li>No selling of data, no advertising trackers, no third-party fonts.@unless (\App\Services\HumanCheck::turnstileEnabled()) No third-party scripts.@endunless</li>
            @if (\App\Services\HumanCheck::turnstileEnabled())
                <li>Public forms use Cloudflare Turnstile to stop spam bots. Cloudflare sees your IP address and browser details for that check only.</li>
            @endif
            @if (config('vendra.media_disk') !== 'public' || config('vendra.files_disk') !== 'local')
                <li>Uploaded files are stored with Cloudflare R2 (encrypted at rest). Customer attachments are kept in a private bucket that isn’t reachable from the web.</li>
            @endif
            <li>Booking reminder emails are sent the day before a confirmed booking; each business can turn them off. Staff can turn on device alerts; an alert only says that something new arrived, and its details load from Vendra after sign-in.</li>
            <li>Each business’s data is kept separate. Platform admins can’t see a business’s records unless its owner grants time-limited, logged support access.</li>
            <li>Uploaded pictures (logos, cover and gallery photos, product photos, profile pictures) are re-encoded on upload, which removes hidden details such as GPS location and camera info. Business photos are public on that business’s page; profile pictures are shown only inside the dashboard.</li>
            <li>Passwords are hashed, sign-in attempts are rate-limited, owners use two-step sign-in, and important actions are logged.</li>
        </ul>
    </section>
    <section>
        <h2 class="h2">How long</h2>
        <p class="mt-2">Account information is kept while the account is active. When a business closes, its owner has 30 days to export data; after that, customer details are anonymized. Activity logs are kept for {{ config('vendra.retention.audit_logs_months') }} months.</p>
    </section>
    <section>
        <h2 class="h2">Your rights and contact</h2>
        <p class="mt-2">You can ask to access, correct, object to, or delete your information, or to get a copy. Update your name, email and password anytime under “Your account”.
            @if ($contact) Write to <a class="link" href="mailto:{{ $contact }}">{{ $contact }}</a>. @else Contact the developer through the <a class="link" href="https://github.com/ashweak/vendra" target="_blank" rel="noopener">GitHub repository</a>. @endif
            You can also file a complaint with the National Privacy Commission at <a class="link" href="https://privacy.gov.ph" target="_blank" rel="noopener">privacy.gov.ph</a>.</p>
    </section>
    <section>
        <h2 class="h2">Breaches</h2>
        <p class="mt-2">If a personal data breach that requires notification happens, affected people and the National Privacy Commission are notified within 72 hours of knowing about it, as the law requires.</p>
    </section>
</div>
@endsection
