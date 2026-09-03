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
| **Demo client** — seeded, linked to the trainer's "Arjun Mehta" record, 1 paid + 1 open invoice | `client@coachpay.test` / `password` | `/portal` |
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
| `/sessions` | `sessions/index` | Month calendar **and** list tabs; log/edit a session; click a day to add one; status (scheduled / completed / cancelled / no-show) |
| `/invoices` | `invoices/index` | All invoices, status filter, row → detail |
| `/invoices/create`, `/invoices/{id}/edit` | `invoices/create` | Invoice builder — client, line items (manual, from a package, or from unbilled completed sessions), discount, tax rate, due date, allowed payment methods, notes; live totals |
| `/invoices/{id}` | `invoices/show` | Invoice detail — send / resend, download PDF, copy public link, record a manual (cash / UPI) payment, confirm a pending payment, refund, void, delete draft; payment history |
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
| `/portal/sessions` | `portal/sessions` | The client's past and upcoming sessions |

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
