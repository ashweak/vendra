# Vendra — From inquiry to opportunity

Vendra is a configurable AI business assistant for small businesses in the Philippines. It answers customer
questions in English, Tagalog and Taglish, then turns them into **bookings, orders, quotations and service
requests** that staff can trust.

> **Status:** local build for demonstrations with **fictional data only**. It is not production-ready; see
> [docs/SCOPE_CHANGES.md](docs/SCOPE_CHANGES.md) for the milestone status and the decisions recorded against the
> approved plan.

## What it does

**Customers** (no account needed)
- Branded business page with a searchable catalog, hours, policies and FAQ.
- Booking, ordering, quotation and service-request forms. Every submission goes **form → review → confirm**.
  Double-clicks and refreshes never create duplicates.
- AI chat that answers from the business's live data and prepares a **draft** the customer reviews in the
  form. Typing in chat never submits anything.
- "Track my request": a single-use, expiring email link. A reference number alone never shows private data.

**Business dashboard** (owner / manager / staff, enforced server-side)
- Inbox with AI → *Waiting for staff* → *Staff active* → *Closed* states, handoff summaries, internal notes,
  assignment, and explicit "Resume AI".
- Appointments: staff schedules, slot availability, locked double-booking checks, approval deadlines with
  expiry, walk-in/phone entries in the same calendar, safe rescheduling, no-shows.
- Orders: stock is reserved on submission, deducted once at handover, and released on cancel or expiry. Delivery
  fees are reviewed by staff. Payment verification is separate and done only by staff.
- Quotations: immutable versions, owner approval before sending, acceptance tied to a specific version,
  one-time conversion to a service request.
- Service requests, customers with consent history, reports (submitted, confirmed and completed shown
  separately), activity log, CSV exports, team and schedules, onboarding checklist, closure with a 30-day
  export window.

**Platform admin**
- Accounts, plans and usage records, verified Vendra fee payments, suspension, and an admin log.
- **No access to business content** unless the owner grants a time-limited, logged, revocable support grant.

**AI safety rules** (P6)
- The AI uses only controlled tools: business info, catalog search, availability, draft preparation and
  handoff.
- It cannot book, order, change prices or stock, give discounts, verify payments, or read other customers'
  records.
- Monthly limits per business, rate and concurrency limits, and an owner warning at 80%.
- Forms and staff replies keep working when the AI is off, at its limit, or failing.
- The AI checks the conversation state before replying, so it never replies over a staff member who took over.

## Tech

Laravel 13 (PHP ≥ 8.3) · Blade · Tailwind CSS 4 (precompiled) · MySQL or SQLite · Laravel scheduler ·
server-side Gemini adapter with function calling · offline rules engine.

## Run it locally (WSL Ubuntu)

```bash
git clone https://github.com/ashweak/vendra.git ~/projects/vendra
cd ~/projects/vendra
composer install
cp .env.example .env
php artisan key:generate
```

Choose a database in `.env`:

- **MySQL (plan default).** Create the database and user first:
  ```bash
  sudo mysql -e "CREATE DATABASE vendra; CREATE USER 'vendra'@'localhost' IDENTIFIED BY 'change-me'; GRANT ALL ON vendra.* TO 'vendra'@'localhost';"
  ```
  Then set `DB_PASSWORD=change-me`.
- **SQLite (quickest).** Set `DB_CONNECTION=sqlite`, remove the `DB_DATABASE` line, and run
  `touch database/database.sqlite`.

Then:

```bash
php artisan migrate --seed           # fictional demo data
php artisan serve                    # http://localhost:8000
php artisan schedule:work            # second terminal: expiry + retention jobs
```

Emails (tracking links, staff alerts) are written to `storage/logs/laravel.log`. No real email is sent.

### Demo accounts (password: `password`)

| Business | Owner | Others |
|---|---|---|
| Lumina Hair & Nail Studio (`/b/lumina-studio`) — appointments, quotations | owner@lumina.test | manager@lumina.test, joy@lumina.test, ken@lumina.test (staff) |
| Ganda Beauty Hub (`/b/ganda-beauty`) — orders | owner@ganda.test | |
| Kislap Home Cleaning (`/b/kislap-cleaning`) — service requests, quotations, appointments | owner@kislap.test | |
| Platform admin | admin@vendra.test | |

Owners and the admin must set up an authenticator app (TOTP) on first sign-in. For a quick local demo only, you
can set `VENDRA_ENFORCE_MFA=false`.

### Tests and AI evaluation

```bash
php artisan test                                   # feature + unit tests
php artisan migrate:fresh --seed
php artisan vendra:ai-eval --provider=rules        # 60 fictional EN/TL/Taglish cases
php artisan vendra:ai-eval --provider=gemini --sleep=5 --show   # with a key; mind free-tier limits
```

Pass criteria (P11): ≥95% of ordinary cases and 100% of the critical privacy, permission and integrity cases.
The results describe these cases only and do not guarantee future behavior.

### Gemini (optional)

Set `VENDRA_AI_PROVIDER=gemini` and `GEMINI_API_KEY`. Before using it, check the current model name, free-tier
limits and data terms:

- https://ai.google.dev/gemini-api/terms
- https://ai.google.dev/gemini-api/docs/pricing
- https://ai.google.dev/gemini-api/docs/rate-limits

Use fictional or non-sensitive data only on the free tier. Using it with real customers needs suitable provider
terms and the production review (M13). The key stays on the server. When a quota is reached, the app falls back
to forms and staff replies.

## Cost rule

This project uses free tools and local infrastructure. Nothing here buys services or activates live customer
use. That needs a committed customer, a budget review and the production gate in the plan.

## Project map

```
app/Services/            Appointment, Order, Quotation, ServiceRequest, Conversation, CustomerAccess, AuditLogger
app/Services/Ai/         AssistantService, AssistantTools (controlled functions), GeminiProvider, RuleBasedProvider, DateParser
app/Http/Controllers/    PublicController (customer page), ChatController, Dashboard/*, Admin/*
app/Support/             PermissionMatrix (P4), Totp (MFA), CurrentBusiness (tenant), Money
app/Console/Commands/    vendra:expire, vendra:retention, vendra:ai-eval
tests/ai-eval/cases.json 60 fictional evaluation cases
docs/SCOPE_CHANGES.md    decisions and milestone status
```

---
Built by Lance Ashley Agua · BSIT, Our Lady of Fatima University
