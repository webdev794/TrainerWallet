
composer run dev

http://localhost:8000/

------

## Resume the CoachPay build (paste this to Claude Code next session)

Continue the CoachPay build. Read the memory files first:
`C:\Users\Intel\.claude\projects\d--laravel\memory\coachpay-build.md` and
`coachpay-dev-environment.md`, plus the plan at
`C:\Users\Intel\.claude\plans\piped-launching-graham.md`.

Status: Phases 1-2 done (46 Pest tests green). Phase 3 (invoicing + payments) has all
backend written but NOT verified and NO React pages yet, so `/invoices`, `/settings`,
`/payments` currently 500.

Do this, in order:
1. Use `D:\xampp8-2-12\php84\php.exe` (the `php` on PATH is 8.2, too old). `C:` is full -
   route temp to `D:\laravel\tmp` (`export TMPDIR=/d/laravel/tmp TMP=... TEMP=...`).
2. `php artisan wayfinder:generate`
3. `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G` and
   fix anything.
4. Build the missing Phase 3 React pages: `invoices/index`, `invoices/create`,
   `invoices/show`, `invoice/public`, `settings/index`, `payments/index`, and update
   `portal/index` to render the new `invoices` prop.
5. Write Phase 3 Pest tests (invoice lifecycle, PDF renders, each gateway webhook incl.
   duplicate-delivery no-op, manual UPI confirm, refund, public-page access control).
6. `php artisan test --compact`, `vendor/bin/pint app config routes database tests --format agent`,
   `npx vp check --fix resources/js`, `npm run build` - all green.
7. Then Phase 4 (recurring invoices, reminders, reports, Free/Pro billing), then Phase 5
   (hardening + DemoSeeder).

Work autonomously through Phases 3-5 without asking for approval; keep approving bash
commands yourself as long as they run inside `D:\laravel`.
