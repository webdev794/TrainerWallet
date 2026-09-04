# CoachPay

Invoicing and payment collection for personal trainers — log a session, send a branded
invoice, get paid by UPI or card, and let automation chase the rest.

**Stack:** Laravel 13 · Inertia v3 · React 19 · TypeScript · Tailwind v4 · MySQL · Pest

---

## How to run the project

### Requirements

- **PHP 8.4+** — on this machine it is `D:\xampp8-2-12\php84\php.exe` (the `php` on `PATH`
  is 8.2 and too old). Put it first on `PATH` or call it by full path.
- **Node 20+** and npm
- **MySQL** running locally with a database named `trainerwallet`

### First-time setup

```bash
cd D:\laravel\TrainerWallet

composer install
npm install

cp .env.example .env
# edit .env → DB_DATABASE=trainerwallet, DB_USERNAME, DB_PASSWORD
# (optional) fill STRIPE_* / PAYPAL_* to exercise real gateways
php artisan key:generate

# build schema + a ready-to-use demo trainer
php artisan migrate:fresh --seed
```

### Run (development)

Two terminals:

```bash
npm run dev          # Vite dev server (hot reload)
php artisan serve    # http://localhost:8000
```

Or everything in one process:

```bash
composer run dev     # server + queue + vite + logs, concurrently
```

### Log in

| Who | Credentials | Lands on |
| --- | --- | --- |
| **Demo trainer** — seeded, Pro plan, 4 clients, sessions, paid + open invoices, a recurring schedule | `trainer@coachpay.test` / `password` | `/dashboard` |
| **Demo client** — seeded, linked to "Arjun Mehta"; 1 paid + 1 open invoice (both built from sessions), 8 completed sessions (6 invoiced / 2 not yet), plus scheduled / postponed / cancelled sessions | `client@coachpay.test` / `password` | `/portal` |
| New trainer | `/register` → choose **"I'm a trainer"** (Free plan) | `/onboarding` |
| New client | `/register` → choose **"I'm a client"** (use the email the trainer has on file) | `/portal` |
| Invited client | trainer clicks **invite** on the Clients screen → client sets a password via emailed link | `/portal` |

Both demo accounts have `password` as the password and are created by
`php artisan migrate:fresh --seed` (see `database/seeders/DemoSeeder.php`).

The `/register` form has a trainer/client toggle. A client account is matched to a
trainer's client record by email: whichever happens first — the client self-registers, or
the trainer adds/imports/invites them — the two records are linked automatically. Until
linked, the portal shows a "ask your trainer to add you" notice.

Emails use `MAIL_MAILER=log` in dev — the invoice / receipt / reminder / invite messages
land in `storage/logs/laravel.log`. Point `MAIL_*` at Mailtrap to preview them rendered.

### Background workers

Reminders, dunning, overdue flagging and recurring invoices run on the scheduler; queued
mail needs a worker:

```bash
php artisan queue:work
php artisan schedule:work          # or cron: * * * * * php artisan schedule:run
```

| Command | Cadence | Purpose |
| --- | --- | --- |
| `invoices:mark-overdue` | daily 06:00 | flag past-due open invoices |
| `reminders:dispatch` | hourly | pre-due reminders + overdue dunning |
| `invoices:generate-recurring` | daily 05:00 | create invoices from active schedules |

### Quality gates

```bash
php artisan test --compact                                            # 84 Pest tests
vendor/bin/pint                                                       # PHP code style
php -d memory_limit=1G vendor/bin/phpstan analyse --memory-limit=1G   # static analysis, level 7
npm run check                                                        # ESLint + Prettier
npm run build
```

---

## Pages

### Public / marketing

| Route | Page | What it does |
| --- | --- | --- |
| `/` | `welcome` | Landing page — hero, feature grid, 3-step "how it works", Free vs Pro pricing cards, FAQ, CTA band |
| `/i/{token}` | `invoice/public` | Tokenised public invoice — line items, amount due, and pay buttons (card / PayPal / UPI QR + UTR form). No login. Marks the invoice "viewed". Rate-limited. |

### Authentication (hand-rolled Inertia)

| Route | Page | Notes |
| --- | --- | --- |
| `/register` | `auth/register` | Sign-up with a **trainer / client** toggle. Trainer → creates `User` + `TrainerProfile`, goes to onboarding. Client → creates a client-role `User`, auto-links to any matching client record by email, goes to the portal. |
| `/login` | `auth/login` | Email + password, "remember me", 5-attempt throttle |
| `/forgot-password` | `auth/forgot-password` | Request a reset link |
| `/reset-password/{token}` | `auth/reset-password` | Set a new password (also the client-invite "set password" flow) |
| `/verify-email` | `auth/verify-email` | Prompt + resend; app routes are gated on a verified email |
| `/onboarding` | `onboarding` | One-step wizard: business name, currency, UPI ID, invoice prefix, logo. App routes redirect here until completed. |

### Trainer app (sidebar)

| Route | Page | What it does |
| --- | --- | --- |
| `/dashboard` | `dashboard` | KPI cards (collected this month, outstanding, overdue count, sessions this week) + recent invoices table |
| `/clients` | `clients/index` | Directory table with search + status filter + pagination; add/edit drawer; CSV import; "invite to portal"; delete |
| `/packages` | `packages/index` | Reusable billing packages (single / pack / monthly) — list + add/edit dialog |
| `/sessions` | `sessions/index` | Month calendar **and** list tabs; log/edit a session; click a day to add one; status dropdown: scheduled / completed / postponed / cancelled / no-show |
| `/invoices` | `invoices/index` | All invoices, status filter, row → detail |
| `/invoices/create`, `/invoices/{id}/edit` | `invoices/create` | Invoice builder — client, line items (manual, from a package, or from unbilled completed sessions), discount, tax rate, due date, allowed payment methods, notes; live totals |
| `/invoices/{id}` | `invoices/show` | Invoice detail — send / resend, download PDF, copy public link, record a manual (cash / UPI) payment, confirm a pending payment, download a **per-payment PDF receipt**, refund, void, delete draft; payment history |
| `/recurring` | `recurring/index` | Recurring schedules — create (client, interval, first run, due days, line items, auto-send), pause/resume, generate-now, delete |
| `/payments` | `payments/index` | Every payment received, with gross / net / fee, method and status; row → invoice |
| `/reports` | `reports/index` | YTD collected + net tiles, a 6-month revenue area chart (Recharts), outstanding-by-client table, and a **payments CSV export** |
| `/billing` | `billing/index` | Free vs Pro comparison, this-month invoice usage, upgrade via Stripe Checkout, manage-subscription portal link |
| `/settings` | `settings/index` | Business profile + logo; payout details (UPI ID, PayPal merchant ID); Stripe Connect onboarding + status |

### Client portal

| Route | Page | What it does |
| --- | --- | --- |
| `/portal` | `portal/index` | The client's invoices — status, balance, "Pay" / "View" |
| `/portal/invoices/{id}` | `invoice/public` | Same pay page as the public link, inside the portal shell |
| `/portal/sessions` | `portal/sessions` | The client's past and upcoming sessions, each with its status and whether a completed session has been invoiced yet |
| `/portal/receipts` | `portal/receipts` | One row per successful payment; each downloads a PDF receipt (`/portal/receipts/{payment}/download`) |

### Layouts

- `marketing-layout` — nav + footer for the landing page
- `auth-layout` — split screen with a testimonial panel
- `app-layout` — dark collapsible sidebar, topbar (plan badge, avatar menu, verify-email
  banner), flash → toast
- `client-portal-layout` — slim top-nav shell for clients

### PDFs & email (Blade)

- `pdf/invoice`, `pdf/receipt` — branded documents; the invoice embeds a UPI QR
- `mail/invoice-sent`, `mail/payment-receipt`, `mail/invoice-reminder` — Markdown mailables;
  plus the `ClientInvitationNotification` and `InvoicePaidNotification`

---

## Payments architecture

All methods sit behind `App\Services\Payments\PaymentGateway`, resolved by
`PaymentGatewayManager`:

- **Stripe** — hosted Checkout Session; with Connect it adds `application_fee_amount` +
  `transfer_data.destination`. Env: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`.
- **PayPal** — Orders v2 with `payee` + `payment_instruction.platform_fees`, called
  directly over REST (`Http` facade). Env: `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`;
  set a `paypal_merchant_id` on the profile.
- **UPI** — `upi://pay` QR against the trainer's own VPA; the payer submits a UTR and the
  trainer confirms receipt (`payments.gateway = upi_manual`).

Webhooks: `POST /webhooks/stripe`, `POST /webhooks/paypal` — CSRF-exempt,
signature-verified, idempotent via the `webhook_events` table, rate-limited. The Stripe
endpoint also drives CoachPay's own Free→Pro subscription billing.

Everything runs on **test / sandbox credentials**; going live is an `.env` swap.

---

## Going live — checklist

- [ ] Live Stripe account: platform keys + **Stripe Connect** enabled; register the
      `/webhooks/stripe` endpoint, set `STRIPE_WEBHOOK_SECRET`
- [ ] Stripe Price for the Pro plan → `STRIPE_PRICE_ID`; enable the Billing Portal
- [ ] PayPal Commerce Platform partner account; register `/webhooks/paypal`, set
      `PAYPAL_WEBHOOK_ID`, `PAYPAL_MODE=live`
- [ ] `PLATFORM_FEE_PERCENT` = your real take rate
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, HTTPS enforced
- [ ] `php artisan config:cache route:cache event:cache` in the deploy step
- [ ] Supervisor running `queue:work`; cron running `schedule:run` every minute
- [ ] `MAIL_MAILER` → a real transactional provider
- [ ] Database backups for `trainerwallet`

---
## Client marketplace (Phase 6)

CoachPay is two-sided: trainers can publish a public profile and clients can browse,
**instant-book** a listed service, and leave **per-service reviews**. A client can be
connected to several trainers at once.

### Public (no login)

| Route | Page | What it does |
| --- | --- | --- |
| `/trainers` | `trainers/index` | Directory of trainers who opted in — search, rating, service count |
| `/t/{slug}` | `trainers/show` | A trainer's profile — bio, bookable services with prices, reviews, per-service ratings, "Book now" buttons |

### Client portal (new / changed)

| Route | Page | What it does |
| --- | --- | --- |
| `/portal` | `portal/index` | **Rebuilt as a progress dashboard** — completed-sessions bar chart (6 months), sessions-completed / this-month / week-streak / invoices-to-pay tiles, next session, "Book a session" + "Browse trainers" |
| `/portal/invoices` | `portal/invoices` | The invoice list (moved off the dashboard) — now also shows the trainer per invoice |
| `/portal/book` | `portal/book` | Instant booking — pick trainer → service → (if a single session) date/time → an invoice is generated and you're sent to the pay page. Deep-linkable: `/portal/book?trainer={slug}&package={id}` |
| `/portal/bookings` | `portal/bookings` | Booking history with invoice status; cancel a confirmed booking |
| `/portal/reviews` | `portal/reviews` | Rate a booked service (stars + "what went well" + "how to improve"); edit / delete your reviews |

### Trainer app (new)

| Route | Page | What it does |
| --- | --- | --- |
| `/bookings` | `bookings/index` | Bookings received from the public profile; mark done / cancel (syncs the linked session) |
| `/reviews` | `reviews/index` | Overall rating, per-service breakdown, and the full review feed with improvement notes |

### Trainer settings / packages

- **Settings** gains a *Marketplace profile* card — a public toggle (auto-generates a URL
  slug the first time it's switched on), headline, bio, city, and the public page link.
- **Packages** gains *Description*, *Session length*, and a **Bookable** toggle — only
  bookable + active packages appear on the public page and in the booking flow.

### How instant-book works

`App\Services\BookingService::book()` runs in one transaction: find-or-create the client
record for that trainer, create a scheduled `TrainingSession` (session-type services only),
create a **sent** `Invoice` with the service as a line item, materialise its reminder
schedule, and record the `Booking`. The invoice email goes out and the client lands on the
public pay page.

`App\Services\ReviewService` upserts one review per client per service and recomputes the
trainer's `rating_avg` / `rating_count` on every write.

### Demo marketplace data

`migrate:fresh --seed` now also: makes **Priya Sharma Strength** public
(`/t/priya-sharma-strength`) with 3 bookable packages; adds a **second public trainer**
`rahul@coachpay.test` / `password` (`/t/rahul-verma-performance`); and seeds a completed
booking + a 5-star review from the demo client.

### Data model additions

`trainer_profiles` +`is_public`, `slug`, `headline`, `bio`, `city`, `rating_avg`,
`rating_count`. `packages` +`description`, `is_bookable`, `duration_minutes`. New
`bookings` and `reviews` tables. `User::clientRecord()` (hasOne) became
`clientRecords()` (hasMany) so a client can hold a record per trainer.

**Tests:** 112 Pest tests green (19 for the marketplace). PHPStan L7, Pint, ESLint, tsc,
and the Vite build all clean.

*****
Continue the CoachPay build. Read `C:\Users\Intel\.claude\projects\d--laravel\memory\coachpay-build.md` + `coachpay-dev-environment.md` first. Use `D:\xampp8-2-12\php84\php.exe` (not the 8.2 on PATH); route temp to `D:\laravel\tmp`; start MySQL via `D:\xampp8-2-12\mysql_start.bat` if `migrate` can't connect. Phases 1-6 are done and green (112 Pest tests) — run the quality gates (`php artisan test --compact`, `vendor/bin/pint`, `phpstan`, `npm run check`, `npm run build`) to confirm, then tell me what's next. Work autonomously; keep approving your own bash commands inside `D:\laravel`.
*****
