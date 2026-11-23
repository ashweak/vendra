# Vendra — Recorded scope changes and decisions

Baseline: *Vendra Final Approved Plan v1.0* (12 Nov 2026). As the plan requires, changes are recorded here
rather than silently replacing approved requirements.

| # | Date | Area | Change / decision | Reason |
|---|------|------|-------------------|--------|
| 1 | 2026-11-12 | P12 working procedure | Development environment is **WSL Ubuntu + bash**, not Windows PowerShell. | The existing Laravel setup (PHP, Composer, MySQL) already runs in WSL. |
| 2 | 2026-11-12 | P4 open item | **Staff-created record assignment policy:** a record created by staff is assigned to that staff member. For appointments, it goes to the performing staff member when one is set. Owners and managers can reassign. Staff only see records assigned to them. | Required before the affected workflows (open decision in P4). |
| 3 | 2026-11-12 | P9 stack | **Blade + small vanilla JS instead of Livewire.** Tailwind is precompiled into `public/css/vendra.css`. | Fewer dependencies, and Node is not needed to run the app. Livewire can be added later without schema changes. |
| 4 | 2026-11-12 | P9 / P11 | **Offline rules-based AI provider** added next to the Gemini adapter, behind the same controlled tools. It is the default when no Gemini key is set. | Zero-cost rule; the app keeps working without AI; gives a deterministic baseline for the 60-case evaluation. |
| 5 | 2026-11-12 | P5 open item | Default time-window rules: approval deadline 12 h (never after the start time), 30-min slot interval, 60-min booking lead time, 30-day booking horizon, customer self-cancel up to 6 h before, order reservation 24 h, quote validity 14 days. All are configurable per business. | Required before M7 (open decision). |
| 6 | 2026-11-12 | P5 quotations | Accepted quotations convert to a linked **service request**. Converting directly to an appointment or order is deferred; staff create a linked booking manually. | Keeps conversion simple and prevents duplicates. |
| 7 | 2026-11-12 | P5 appointments | Customer self-service **rescheduling is deferred**. Customers can cancel within policy and rebook, or ask staff, who can reschedule (the old booking is kept unless the new slot is secured). | Reduces risk in the first release. |
| 8 | 2026-11-12 | P4 team | Team members are added with a **temporary password shown once**, instead of email invitations. | No real email in local development (P9). |
| 9 | 2026-11-12 | Portfolio | The portfolio shows a **static demo with fictional data**. The full app runs locally only. | GitHub Pages cannot run Laravel; no paid hosting (P9). |
| 10 | 2026-11-19 | P9 hosting | Added a **Docker image and Render free-plan Blueprint** for an optional public demo. Public demo mode reseeds fictional data on every start and turns owner MFA off so visitors can sign in. The static portfolio demo stays. | Lets reviewers try the real app at zero cost; data is fictional and disposable. |
| 11 | 2026-11-21 | P6 AI provider | **Removed the Gemini provider.** The offline rules engine is now the only auto-assistant; customer-facing copy says “auto-assistant”, not “AI”. | Gemini's unpaid tier lets Google use submitted content and have people review it, which isn't acceptable for real customer messages. Zero cost is kept. |
| 12 | 2026-11-22 | Business page | Added **website themes**: six presets, each with its own homepage layout, header and footer plus colors, headline font and corner style. Picked at setup, changeable in Settings → Appearance, previewable with `?theme=`. Not demo-locked. | Owners asked to choose their page's look. Tokens keep layout, accessibility (contrast checked) and behaviour identical across themes; fonts are self-hosted (Fraunces, OFL). |
| 13 | 2026-11-22 | Business page | **Layout and colors are separate choices**: six layouts × eight palettes (Berry and Lavender added). A migration maps each old theme to the same layout and palette, so existing pages look the same. Preview with `?layout=` and `?palette=`. | Owners wanted to pair any structure with any color mood. Palettes only set color tokens; layouts set structure, fonts and shape. |
| 14 | 2026-11-22 | Business page | **Photos and profile pictures**: logo, cover, gallery (max 8, captions) and per-item photos on the customer page in every layout; profile pictures for accounts. Previews of a layout/palette now persist across pages (session) until exited or saved, and every customer page shares the layout's title style. | Owners wanted to show their real shop and products. Uploads are re-encoded with GD (metadata stripped), size-capped, owner/manager only, demo-locked, and cleared on demo reset. Seeded pictures are generated illustrations, not real photos. |
| 15 | 2026-11-23 | Platform | **Cloud storage, responsive images, spam protection, reminders, installable dashboard, Filipino pages.** Uploads can live on Cloudflare R2 (public bucket for pictures, private bucket for attachments); pictures get 480/960 px copies with `srcset`; public forms have a honeypot plus optional Cloudflare Turnstile; day-before reminder emails with `.ics` and an *Add to calendar* button; the dashboard is a PWA with data-less Web Push alerts (VAPID, no library); customer pages, chat, validation and customer emails in English or Filipino. | All free (R2, Turnstile and Web Push have no cost at this scale) and all optional: without keys the app behaves as before. Push carries no data, so customer details never pass through browser vendors; the push endpoint is limited to known push services (no SSRF). Payments (GCash/Maya) were deferred by the owner. |

## Milestone status (honest)

| ID | Status |
|----|--------|
| M1 | Project scaffolded (Laravel 13 skeleton). Run `composer install` locally. |
| M2 | Schema and migrations written (tenant-scoped tables, unique idempotency keys, indexes). |
| M3 | Auth, memberships, roles matrix, owner/admin TOTP MFA, server-side checks written; tests included. |
| M4 | Profile, modules, catalog, policies/FAQ, onboarding checklist and activation written. |
| M5 | Customer page, forms with review → confirm, verified customer access written. |
| M6 | Inbox, assignment, internal notes, handoff, AI pause/resume written. |
| M7 | Appointment lifecycle with locking, expiry and reschedule written; concurrency tests included. |
| M8 | Gemini adapter (function calling), rules provider, usage limits and fallbacks written. **The Gemini model and its accuracy must still be measured with a real key.** |
| M9 | Reports, activity log, retention command, CSV exports written. **Backups and a tested restore are still to do.** |
| M10 | Quotations (versions, owner approval) and service requests written. |
| M11 | Orders with stock reservation/deduction written; integrity tests included. |
| M12 | Subscription records and platform admin written. Onboarding/support material: README only. |
| M13–M14 | Not started, as the plan requires. They need a committed customer and a budget decision. |

**Verification (GitHub Actions, PHP 8.3 and 8.4):** 48 feature/unit tests pass (185 assertions). The seeder
runs. The AI evaluation passes 42/42 ordinary and 18/18 critical cases with the offline rules engine. A
Playwright job boots the app and captures 22 screens (pushed to the `screenshots` branch) with no JavaScript or
server errors. The rules engine was tuned against these cases, so this is a baseline. Gemini must be measured
separately with `vendra:ai-eval --provider=gemini` (M8).
