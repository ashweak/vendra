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

"Written" means the code exists and tests are included. They were **not executed in the build environment**
(PHP packages could not be downloaded there). The first GitHub Actions run, or `php artisan test` on your
machine, is the first real PASS/FAIL evidence.
