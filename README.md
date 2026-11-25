# Vendra — From inquiry to opportunity

Vendra is a business assistant for small businesses in the Philippines. Its offline auto-assistant answers customer
questions in English, Tagalog and Taglish, staff take over any chat, then turns them into **bookings, orders, quotations and service
requests** that staff can trust.

[![Tests](https://github.com/ashweak/vendra/actions/workflows/tests.yml/badge.svg)](https://github.com/ashweak/vendra/actions/workflows/tests.yml)

**Interactive demo (fictional data, runs in the browser):** https://ashweak.github.io/vendra/ ·
**Case study:** https://ashweak.github.io/vendra/case-study.html

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/ashweak/vendra)

![Walkthrough: a customer books through the chat and the owner approves](https://raw.githubusercontent.com/ashweak/vendra/recording/vendra-demo.gif)

[Watch the walkthrough as MP4](https://raw.githubusercontent.com/ashweak/vendra/recording/vendra-demo.mp4) — recorded
from the real app in GitHub Actions on every change to the script.

> **Status:** local build for demonstrations with **fictional data only**. It is not production-ready; see
> [docs/SCOPE_CHANGES.md](docs/SCOPE_CHANGES.md) for the milestone status and the decisions recorded against the
> approved plan.

## What it does

**Customers** (no account needed)
- Branded business page with a searchable catalog, hours, policies and FAQ.
- **Appearance in two parts**, picked during setup and changeable anytime in *Settings → Appearance*: one of six
  **layouts** (Classic, Brochure, Spotlight, Café, Panel, Shop — each with its own structure, header, footer and
  headline font) and one of eight **color palettes** (Sunny, Coral, Ocean, Espresso, Slate, Garden, Berry, Lavender),
  in any combination, with a live preview of the real page before saving. A preview follows the visitor to every
  page (book, order, track…) until they exit it or save it.
- **Business photos**: a logo, a cover photo, a gallery of up to 8 pictures with captions, and a photo per
  product or service, shown in every layout (*Settings → Photos*, *Catalog*). Staff accounts have **profile
  pictures**. Every upload is decoded and re-encoded (WebP), resized, and stripped of hidden metadata such as GPS
  location; anything that isn't a real JPG/PNG/WebP is refused. Large pictures also get 480 px and 960 px copies
  (`srcset`), so phones on mobile data download less. Uploads are off in the public demo.
- **English or Filipino**: every customer page, the chat widget, form errors and customer emails come in both.
  Visitors switch with the EN/FIL toggle; the owner sets the default (*Settings → Appearance*), and browsers set to
  Filipino get it automatically. The dashboard stays in English.
- **Found on Google**: `sitemap.xml`, `robots.txt`, and schema.org business data on every shop page (hours,
  address, phone, prices), with canonical and English/Filipino language links. Private pages are `noindex`.
- **Photos shrink before upload**: big phone photos are resized in the browser first (faster on mobile data,
  no 5 MB errors); the server still checks and re-encodes every file.
- **Spam protection** on every public form and sign-up: a hidden honeypot field, plus optional
  [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/) (free) when its keys are set.
- **Booking reminders**: an email the day before each confirmed booking, with a calendar (`.ics`) file attached.
  Customers can also tap "Add to calendar" after booking. Owners can turn reminders off (*Settings → Workflow*).
- Booking, ordering, quotation and service-request forms. Every submission goes **form → review → confirm**.
  Double-clicks and refreshes never create duplicates.
- Chat with an offline auto-assistant that answers from the business's live data and prepares a **draft** the customer reviews in the
  form. Typing in chat never submits anything.
- "Track my request": a single-use, expiring email link. A reference number alone never shows private data.

**Business dashboard** (owner / manager / staff, enforced server-side)
- **Installable app**: add the dashboard to a phone's home screen (offline page included) and turn on **device
  alerts** for new bookings, orders and chats (*Your account*). Alerts are data-less Web Push (VAPID): the push
  service never sees customer details; the phone fetches them from Vendra after sign-in.
- **Facebook Messenger**: connect a Page (*Settings → Messenger*) and its chats arrive in the same inbox, with the
  same auto-assistant, drafts (sent as a *Review & confirm* button), handoff and staff replies sent back to
  Messenger. Webhooks are verified with the app secret; Page tokens are stored encrypted.
- **Morning summary** for owners at 7:00 AM: today's bookings, approvals, orders to prepare, low stock and
  unanswered questions (skipped on quiet days; can be turned off).
- **Catalog import** from a CSV (Excel / Google Sheets): every row is checked first and nothing is imported until all
  rows are valid; existing items are skipped, never overwritten.
- **Returning customers**: bookings, orders, the inbox and the customer list show "Returning · N visits"
  (finished bookings and completed orders), with a *Returning only* filter on *Customers*.
- **Team sign-in security**: owners can require two-step sign-in for managers and staff, see who has it on, and
  reset it for someone who lost their phone. Anyone who turns two-step on is always asked for a code.
- **Encrypted backups**: `php artisan vendra:backup` (nightly at 2:15 AM Manila) saves the database and uploaded files
  in one encrypted file (XChaCha20-Poly1305, streamed; tampering is detected), keeping the newest 14.
  `php artisan vendra:restore` puts everything back. See *Backups* below.
- **Unanswered questions → FAQ**: questions the auto-assistant couldn't answer are listed by how often they're
  asked (contact details masked). Write the answer once (*Settings → Unanswered questions*) and it becomes a
  published FAQ that the assistant uses from then on. The assistant now answers from published FAQ/policies.
- **Suggested replies** in each conversation, built from the business's FAQ, live prices and hours, in the
  customer's language. Staff tap one, edit it, then send.
- Inbox with *Auto-assistant* → *Waiting for staff* → *Staff active* → *Closed* states, handoff summaries, internal notes,
  assignment, and explicit "Resume auto-replies".
- Appointments: staff schedules, slot availability, locked double-booking checks, approval deadlines with
  expiry, walk-in/phone entries in the same calendar, safe rescheduling, no-shows.
- Orders: stock is reserved on submission, deducted once at handover, and released on cancel or expiry. Delivery
  fees are reviewed by staff. Payment verification is separate and done only by staff.
- Quotations: immutable versions, owner approval before sending, acceptance tied to a specific version,
  one-time conversion to a service request.
- Service requests, customers with consent history and privacy requests, reports (submitted, confirmed and completed shown
  separately), activity log, CSV exports, team and schedules, onboarding checklist, closure with a 30-day
  export window.

**Platform admin**
- Accounts, plans and usage records, verified Vendra fee payments, suspension, and an admin log.
- **No access to business content** unless the owner grants a time-limited, logged, revocable support grant.

**Auto-assistant rules** (P6)
- No outside AI service: the rules engine runs on the server, so customer messages never leave Vendra.
- It uses only controlled tools: business info, catalog search, availability, draft preparation and
  handoff.
- It cannot book, order, change prices or stock, give discounts, verify payments, or read other customers'
  records.
- Monthly limits per business, rate and concurrency limits, and an owner warning at 80%.
- Forms and staff replies keep working when auto-replies are off, at their limit, or failing.
- It checks the conversation state before replying, so it never replies over a staff member who took over.

## Tech

Laravel 13 (PHP ≥ 8.3) · Blade · Tailwind CSS 4 (precompiled) · MySQL or SQLite · Laravel scheduler ·
offline rules engine (English, Tagalog, Taglish).

## Host the live demo for free (Render)

The repo includes a `Dockerfile` and a `render.yaml` Blueprint for Render's free web service plan. Check
Render's current free-plan terms before you start; nothing in this setup uses a paid service.

1. Click **Deploy to Render** above and sign in with GitHub.
2. Review the `vendra-demo` service (Docker, **Free** plan) and click **Deploy Blueprint**.
3. After the first build (about 5–8 minutes), open the `https://vendra-demo-xxxx.onrender.com` URL Render gives you.
   The sign-in page lists one-click demo accounts.

What to expect: public demo mode (`VENDRA_PUBLIC_DEMO=true`) reseeds **fictional data on every start**, the free
instance sleeps after 15 idle minutes and takes about a minute to wake, and the auto-assistant runs offline
(no API key). Owner MFA is off in demo mode so visitors can sign in. The **Quality** workflow checks every page
with axe-core (WCAG 2.1 AA; any serious problem fails) and Lighthouse (performance, accessibility, best
practices and SEO budgets). CI builds the same image and smoke-tests
pages, the chat and sign-in on every push.

Run the image yourself: `docker build -t vendra . && docker run -p 8080:10000 -e VENDRA_PUBLIC_DEMO=true -e VENDRA_ENFORCE_MFA=false vendra`

### Optional free add-ons (add the variables on Render → *Environment*)

| Feature | What to do | Variables |
|---|---|---|
| **Keep uploads across restarts** (Render's disk is wiped on every deploy/restart) | Create two [Cloudflare R2](https://developers.cloudflare.com/r2/) buckets (free tier: 10 GB): one with *Public access* on (pictures), one private (customer attachments). Create an R2 API token with read/write on both. | `VENDRA_MEDIA_DISK=r2`, `VENDRA_FILES_DISK=r2_private`, `R2_ENDPOINT` (`https://<account-id>.r2.cloudflarestorage.com`), `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_PRIVATE_BUCKET`, `R2_PUBLIC_URL` (the bucket's `https://pub-….r2.dev` or custom domain) |
| **Spam check on forms** | Add a Turnstile widget in Cloudflare for your Render domain. | `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` |
| **Facebook Messenger** | Create a Meta app (Messenger product). Set the webhook callback to `https://<your-site>/webhooks/messenger` with your verify token and subscribe to `messages`. Each business then pastes its Page ID and Page access token in *Settings → Messenger*. | `FB_APP_SECRET`, `FB_VERIFY_TOKEN` |
| **Device alerts** | Run `php artisan vendra:vapid` once and copy the three lines. | `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` |
| **Real emails** (reminders, tracking links) | Any SMTP service with a free tier. Without it, emails are only written to the log. | `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` |

A **Keep demo awake** GitHub Action opens the demo every 10 minutes so it doesn't sleep (set the repository
variable `DEMO_URL` if your Render address differs; delete the workflow to let it sleep).

### Backups

| Command | What it does |
|---|---|
| `php artisan vendra:backup` | Encrypted backup of every table and the files on this server (runs nightly; not in the public demo) |
| `php artisan vendra:backup --list` | Lists backups, newest first |
| `php artisan vendra:backup --key` | Prints a random `VENDRA_BACKUP_KEY` (otherwise a key derived from `APP_KEY` is used) |
| `php artisan vendra:restore [file]` | Checks the whole file first, then replaces all data and files (asks to confirm) |

Keep backups off the server with `VENDRA_BACKUP_DISK=r2_private`, and keep the key (or `APP_KEY`) in a password
manager: without it a backup can't be opened. `VENDRA_BACKUPS_KEEP` (default 14) sets how many are kept.

The database is SQLite inside the container, so a real (non-demo) deployment also needs a hosted MySQL database
(`DB_CONNECTION=mysql` and the `DB_*` variables).

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

### Tests and assistant evaluation

```bash
php artisan test                                   # 122 feature + unit tests
php artisan migrate:fresh --seed
php artisan vendra:ai-eval          # 60 fictional EN/TL/Taglish cases (add --show to print replies)
```

Pass criteria (P11): ≥95% of ordinary cases and 100% of the critical privacy, permission and integrity cases.
The results describe these cases only and do not guarantee future behavior.

### Why there is no Gemini or other cloud AI

An earlier version could call Gemini's free tier. It was removed because Google's terms for unpaid use allow
submitted content to be used to improve its products and reviewed by people, which isn't acceptable for real
customers' messages. The offline rules engine costs nothing and keeps every message on the server.

## Cost rule

This project uses free tools and local infrastructure. Nothing here buys services or activates live customer
use. That needs a committed customer, a budget review and the production gate in the plan.

## Project map

```
app/Services/            Appointment, Order, Quotation, ServiceRequest, Conversation, CustomerAccess, AuditLogger
app/Services/Ai/         AssistantService, AssistantTools (controlled functions), RuleBasedProvider, DateParser
app/Http/Controllers/    PublicController (customer page), ChatController, Dashboard/*, Admin/*
app/Support/             PermissionMatrix (P4), Totp (MFA), CurrentBusiness (tenant), Money
app/Console/Commands/    vendra:expire, vendra:retention, vendra:ai-eval
tests/ai-eval/cases.json 60 fictional evaluation cases
docs/SCOPE_CHANGES.md    decisions and milestone status
Dockerfile, docker/      production image (Apache + PHP 8.4 + SQLite) used by render.yaml
scripts/                 screenshots.mjs and record.mjs (Playwright, run in GitHub Actions)
```

---
Built by Lance Ashley Agua · BSIT, Our Lady of Fatima University
