# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

MedEquip is a B2B/B2C medical equipment & pharmaceutical marketplace for the Philippine market: Laravel 12 (PHP 8.2) backend, Vue 3 + Inertia.js 2 frontend, Tailwind 3, Vite 7, MySQL. `PROJECT_CONTEXT.md` has a long-form architectural briefing (models, services, order lifecycle, known problems, implementation plan) — consult it for domain detail; it was last verified against source on 2026-09-25.

## Commands

```bash
composer dev                 # php artisan serve + queue:listen + pail (logs) + vite dev, concurrently
composer test                # config:clear then php artisan test
php artisan test --filter=OrderAndPaymentFlowTest        # single test class
php artisan test --filter=test_method_name               # single test method
php artisan test tests/Feature/ProfileTest.php           # single file
npm run build                # production Vite build → public/build (committed, see Deployment)
./vendor/bin/pint             # PHP code style (Laravel preset)
composer dev-account         # seed local test accounts (LocalTestAccountSeeder)
composer db-info             # show which DB connection is active (db:where-am-i)
php artisan route:list       # routes_list.txt in root is a stale dump of this
```

Local runs under XAMPP with **MySQL** (`DB_CONNECTION=mysql` in `.env`), not the SQLite default in `.env.example`. **We don't use SQLite anywhere in this project, including tests** — MySQL-only, deliberately, because SQLite masked real bugs before (see Testing safety).

## Testing safety

Tests run against **real MySQL** — a dedicated database, `medequip_testing`, never the real `medequip` one. `tests/bootstrap.php` deletes `bootstrap/cache/config.php` and force-sets `DB_CONNECTION=mysql` / `DB_DATABASE=medequip_testing` before autoload, because a cached config would otherwise make `RefreshDatabase` run `migrate:fresh` against whatever `DB_DATABASE` resolves to. **Never point `DB_DATABASE` at `medequip` in `tests/bootstrap.php` or `phpunit.xml`** — `RefreshDatabase` would wipe it. Don't remove that bootstrap or run `php artisan config:cache` and then test without it.

This project used to run tests on SQLite `:memory:`, which silently hid real bugs: enum/CHECK-constraint drift between what a migration claims and what actually ran, and driver-specific SQL (e.g. a stray MySQL-only `FIELD()` call in a DSS query) that SQLite either errored on differently or didn't catch at all. One migration had literally been edited *after* it already ran on the real dev database, so the file looked correct while the live column was still missing two enum values — undetectable on SQLite, only caught by actually running migrations against MySQL. Don't reintroduce SQLite for anything in this repo.

Running `php artisan migrate` (and `migrate:status`) against the local dev database is pre-approved — go ahead without asking each time. Still confirm before anything destructive (`migrate:fresh`, `migrate:rollback`, editing an already-applied migration's `up()` and expecting it to retroactively apply — it won't; write a new corrective migration instead, same as `fix_orders_status_enum_missing_completed_and_ready_for_pickup` did).

Test coverage is thin (13 feature tests as of 2026-09-25, up from 7) — DSS and full courier-lifecycle flows are still effectively untested, so don't treat "tests pass" as proof those paths work; verify them manually when you touch them.

## Architecture

**Request flow:** all pages are Inertia renders (no REST API). Controllers return `Inertia::render('Dir/Page', props)`; `resources/js/app.js` resolves `./Pages/{name}.vue` via `import.meta.glob`. Named routes reach JS through Ziggy (`route()`). JSON endpoints exist only for polling (chat 12s, notifications 45s, dashboard pulse 30s, cart count 10s) — there are no WebSockets.

**Shared props** (`app/Http/Middleware/HandleInertiaRequests.php`): `auth.user` (role, distributor status, `admin_permissions` for admin/super_admin), flash (`success|error|info|warning`), csrf token, unread notification/chat counts, admin badge counts, `needsTermsAcceptance`. Computed on every request — don't add expensive queries here without caching.

**Routes:** nearly everything is in one flat `routes/web.php` (~580 lines), grouped by role prefix (`admin.*`, `owner.*`, `courier.*`, `superadmin.*`, customer routes at root). Controllers mirror this: `app/Http/Controllers/{Admin,Owner,Courier,SuperAdmin,Auth,Static,Concerns}/` plus 17 shared root-level controllers (cart, checkout, orders, payments, chat). Pages mirror it too: `resources/js/Pages/{Admin,Owner,Courier,Customer,...}` (16 top-level dirs; `Owner/` is by far the largest) with one layout per role in `resources/js/Layouts/`.

**Authorization is two systems layered together — know which one governs what you're touching:**
1. `users.role` column (`customer`, `distributor`, `staff`, `courier`, `admin`, `super_admin`) enforced by `RoleMiddleware`. `super_admin` bypasses most checks — but **not** `EnsureOTPVerified`, which requires OTP for `admin` and `super_admin` equally. Unapproved/suspended/banned distributors *and their staff* are gated by `EnsureDistributorVerified` (the single source of truth as of 2026-09-25 — `RoleMiddleware` used to duplicate this and has been simplified). It resolves the acting distributor as `$user->distributor` for owners or `$user->employer` for staff.
2. Spatie `laravel-permission` with **teams enabled, keyed on `distributor_id`** (not the package default `team_id` — see `config/permission.php`). `SetTeamIdMiddleware` (global) sets the team context per request. Admin routes use `admin.permission:admin.xxx` consistently across all admin route groups as of 2026-09-25 — `AdminPermission` middleware also now degrades an unseeded permission name to a 403 instead of crashing. **New admin permission strings must be added to `database/seeders/RolesAndPermissionsSeeder.php`, and that seeder re-run in every environment**, or `hasPermissionTo()` denies by default.
3. `app/Policies/{Order,Delivery,Conversation,SavedPurchaseOrder}Policy.php` are wired in via `$this->authorize()`/`can()` (the base `Controller` has `AuthorizesRequests`). Follow this pattern for new authorization on these models — don't add another manual `abort(403)` check. Note `OrderPolicy::view` intentionally allows shop members too, not just the order's customer.

Other global web middleware: `EnsureNotBanned`. CSRF is exempted only for `payments/webhook` (PayMongo).

**Two things a real-browser QA pass caught that the test suite couldn't (2026-09-25), both fixed except the second:**
- `resources/js/Pages/Owner/Orders/Show.vue` built 6 different owner order-action URLs (`updateStatus`, `confirmCodRemittance`, prescription/discount approve/reject) using `props.order.id` instead of `props.order.order_number`, 404ing every single click since `Order`'s route key is `order_number`. If you touch any Vue page that posts to an `/owner/orders/{order}/...` or `/orders/{order}/...` route, use `order.order_number`, never `order.id` — check `Owner/Orders/Index.vue` for the correct pattern.
- `PayMongoService`'s constructor throws a `TypeError` (not a clean error) if `PAYMONGO_SECRET_KEY` isn't configured, and `OrderController` injects it unconditionally — so **every** `OrderController` route 500s on an environment without that key, even routes that never call PayMongo (like `confirmReceived`). Not fixed — flagged in `PROJECT_CONTEXT.md` §11 P0 #7b as a real design fragility, not a quick patch.

**Business logic** lives in `app/Services/` (17 classes — PayMongo, cart, DSS, dashboard analytics, invoicing, order chat automation, payouts, moderation, etc.). Several controllers are still very large (`OrderController` ~970 lines, `Owner/InventoryController`, `Admin/ReportHubController`) — prefer extracting new logic into a service over growing these further.

**Order lifecycle** (see `PROJECT_CONTEXT.md` §7 for the full verified trace): `OrderController::placeOrder` (wrapped in `DB::transaction` + row locking) → `Owner\OrderController::updateStatus` (approve deducts inventory physically, packed creates the `Delivery` record; wrapped in `DB::transaction` + `lockForUpdate()` as of 2026-09-25) → `Courier\DeliveryController` (two parallel paths exist: a one-shot `processScan` endpoint and a granular `accept→startPickup→confirmScan→confirmPickup→confirmDelivery` flow — both are live, know which one a UI is calling before changing either) → `OrderController::confirmReceived` (online orders) or `Owner\OrderController::confirmCodRemittance` (COD orders, also transaction-wrapped now) for completion. Escrow state (`held→released→refunded`) lives on the `Payment` model itself (`applyEscrowFees`/`releaseEscrow`/`refundEscrow`), not in `PayMongoService`. COD is currently disabled at checkout validation (`'cod'` excluded from `placeOrder`'s allowed `payment_method` values) even though it's fully implemented downstream — don't "fix" this without confirming with the user first, it may be intentional (this is the one P0 item from `PROJECT_CONTEXT.md` §11 still open — see Phase 3).

**Inventory:** table name is singular `inventory` (`Inventory::$table = 'inventory'`), not `inventories`. `reserve()`, `deduct()`, and `releaseReservation()` are all atomic conditional `UPDATE`s now (as of 2026-09-25) — always go through these model methods rather than writing raw `quantity`/`reserved_quantity` math.

**Payouts are simulated by default:** `AutomatedPayoutService::disburse()` silently returns `false` (no exception) unless payout bank details exist and `SIMULATE_PAYOUTS` is enabled. This still doesn't block order completion / escrow release — that's a deliberate choice, not a bug — but callers now log a `[Controller] ... payout did not complete automatically` warning with order/delivery/payment context when it happens, so it's at least visible. Don't assume a "completed" order or "released" escrow means money actually moved; check the logs or `seller_payout_cleared_at`/`courier_payout_status`.

**Scheduling** is defined in `bootstrap/app.php` (`withSchedule`); `accounts:purge-deactivated` is deliberately restricted to `production`.

## Deployment (Hostinger)

- GitHub `juzwaista/medequip`, branch `main`, deployed to Hostinger shared hosting.
- **`public/build` is committed** (it's commented out in `.gitignore`) because the server doesn't build assets. After any change under `resources/js` or `resources/css`, run `npm run build` and commit the updated `public/build`.
- The root `.htaccess` rewrites all requests into `public/` so the repo root can serve as the web root.
- `vendor/` is not committed; `storage:link` symlinks can break on Hostinger — `PublicStorageUrl` helper, `LinkPublicStorage` command, and an admin "Repair Storage" utility exist for that.

## Frontend conventions

- Forms overwhelmingly use Inertia's `useForm()` with `form.errors.field` rendered as an inline `<p>` directly under each input (see `Pages/Auth/Login.vue`, `Pages/Owner/Products/Create.vue`, `Pages/Checkout/Index.vue`). One page (`Customer/Addresses/Index.vue`) instead uses a plain `reactive()` object + `router.post/put/delete` + `page.props.errors` — match `useForm()` for new work, don't propagate the second pattern.
- **There is no shared `TextInput`/`InputError`/`InputLabel` component** — every page hand-rolls its own input markup and Tailwind classes. Follow the surrounding page's existing markup style rather than inventing a new pattern, unless the task is specifically to build shared form components.
- Backend validation is almost entirely inline `$request->validate([...])` in controllers (~86 occurrences); only `LoginRequest` and `ProfileUpdateRequest` are Form Request classes. Match whichever convention the controller you're editing already uses.
- The `composables/` directory is lowercase on disk; some existing imports use `@/Composables/useOCR` (capital C), which only works because of a case-insensitive filesystem. Always import as lowercase `@/composables/...` in new code.

## Development workflow

- **Investigate before changing.** Read the actual controller/model/service/component involved (and its callers) before editing — this codebase has several places where behavior doesn't match its own naming (e.g. `updateStatus`'s state table lists statuses it never sets; `EnsureDistributorVerified` implies staff coverage it doesn't provide). Don't trust a method or file name as a full description of what it does.
- **Reuse existing services, models, and components** rather than adding new ones that duplicate them (e.g. use `Inventory::reserve()`/`deduct()`, not new stock-math; use `CartService` helpers, not new cart logic; check `app/Services/` before writing business logic directly in a controller).
- Don't touch unrelated files, don't refactor code you weren't asked to change, and don't "fix" open items (like the disabled-COD path) as a side effect of an unrelated task — flag them and let the user decide, per `PROJECT_CONTEXT.md` §11–12. That said, if a bug is actively blocking you from verifying the task you *were* asked to do (e.g. a migration that silently doesn't match its own file, a driver-specific SQL call breaking tests), fix it and say so clearly — that's what happened repeatedly during the Phase 1/2 work in §11.
- For anything touching orders, payments, inventory, or payouts, run `composer test` after the change, and manually trace the affected flow against `PROJECT_CONTEXT.md` §7 since automated coverage there is thin. State clearly what you verified vs. what you couldn't (e.g. no way to hit the real PayMongo API locally).
- Never write client-supplied IDs, roles, prices, or stock quantities straight into a DB write — recompute/re-validate them server-side (see how `placeOrder` re-locks and re-checks inventory under `lockForUpdate()` rather than trusting the cart's cached stock numbers).
- Before finishing a task, check `git status`/`git diff` for unintended changes, and don't run destructive git or DB commands without asking first.

## Conventions

- Commit messages use conventional-commit style with scope, e.g. `feat(b2b): ...`, `fix(ui): ...`.
- Log lines use `[ServiceName] Action description`.
- Loose root-level scripts (`test_*.php`, `seed_settings.php`, `run_migration.php`, `fix_*.php`, `patch_backend.php`, `reset_admin.php`, `build_*.txt`, `status.txt`) are ad-hoc debugging leftovers, not part of the app — don't build on top of them.
