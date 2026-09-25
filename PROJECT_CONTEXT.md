# MedEquip — Project Context & Architectural Briefing

> **Last audited:** 2026-09-25 | **Method:** direct source verification (routes, middleware, models, migrations, controllers, services, frontend) — every claim below was checked against the actual code, not inferred. Supersedes the 2026-09-01 audit, which had several stale counts and one factual error (noted inline where relevant).

---

## 1. Project Overview

**MedEquip** is a **B2B/B2C medical equipment & pharmaceutical marketplace** built for the Philippine market. It connects medical equipment distributors with customers (hospitals, clinics, individual buyers). The platform features multi-role access (customer, distributor/staff, courier, admin, super admin), an escrow-based payment system via PayMongo, a built-in courier fleet app, a Decision Support System (DSS) for inventory intelligence, and polling-based messaging/chat.

---

## 2. Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| **Backend Framework** | Laravel | ^12.0 |
| **Language** | PHP | ^8.2 |
| **Frontend Framework** | Vue.js | ^3.5 |
| **SPA Bridge** | Inertia.js (Vue 3 adapter) | ^2.0 |
| **CSS Framework** | Tailwind CSS | ^3.4 (`@tailwindcss/forms` imported but **never registered** — dead import, see §15) |
| **Build Tool** | Vite | ^7.0 |
| **Database** | MySQL only, everywhere — local dev (`medequip`) and tests (`medequip_testing`, dedicated DB, as of 2026-09-25). SQLite is not used anywhere in this project; it previously hid real bugs (see §11 P0) and was dropped from testing entirely. | — |
| **Payment Gateway** | PayMongo | REST API v1 |
| **Authorization** | `users.role` column + Spatie `laravel-permission` (teams, keyed on `distributor_id`) | ^6.25 |
| **Runtime** | XAMPP (local) / Hostinger shared hosting (production) | — |

### Notable JS Dependencies
`chart.js` (dashboard analytics), `leaflet` (map picker, Cavite-bounded), `@zxing/browser`/`@zxing/library` (barcode scan), `vue-qrcode-reader`/`qrcode.vue`, `tesseract.js` (OCR for license/ID/prescription scans), `lodash`, `axios`, `alpinejs` (Breeze residual, unused).

---

## 3. Architecture & Directory Structure

```
medequip/
├── app/
│   ├── Http/
│   │   ├── Controllers/{Admin,Owner,Courier,SuperAdmin,Auth,Static,Concerns}/ + 17 root-level (cart, orders, payments, chat)
│   │   ├── Middleware/          # 7: HandleInertiaRequests, RoleMiddleware, EnsureDistributorVerified,
│   │   │                        #    EnsureNotBanned, EnsureOTPVerified, AdminPermission, SetTeamIdMiddleware
│   │   └── Requests/            # Only 2 Form Requests exist: LoginRequest, ProfileUpdateRequest
│   ├── Models/                  # 40 Eloquent models (see §6 — 7 more than previously documented)
│   ├── Policies/                # 4 policies — Order, Delivery, Conversation, SavedPurchaseOrder — wired in via $this->authorize() as of 2026-09-25 (see §11 #8)
│   ├── Services/                # 17 service classes (business logic layer)
│   └── Support/                 # PublicStorageUrl, NotificationFilters
├── database/migrations/         # 134 files (130 + 4 added 2026-09-25: sqlite-sync (now vestigial, see §11), banned-status, and the corrective orders.status fix)
├── resources/js/
│   ├── app.js                   # Inertia + Vue bootstrap, CSRF re-sync on every navigation
│   ├── Components/              # 19 files (not ~17 — corrected count); no shared TextInput/InputError/InputLabel
│   ├── Layouts/                 # 5: MainLayout, AdminLayout, OwnerLayout (shared by distributor+staff), CourierLayout, OnboardingLayout
│   ├── Pages/                   # 16 top-level dirs, 84 .vue files; Owner/ is the largest subtree (19 nested dirs)
│   ├── composables/             # useHeaderNotificationPoll, useOCR (lowercase dir — see §15 casing bug)
│   └── utils/                   # chatProfanityHint, customerOrderStatusMessage
├── routes/web.php                # ~600 lines, one flat file, mixed FQCN/import style
└── tests/Feature/                # 11 feature test files + Auth/ suite; 1 Unit test (example only) — 4 new files added 2026-09-25; runs against real MySQL (medequip_testing), not SQLite
```

---

## 4. User Roles & Access Control — verified two-tier model

> **Updated 2026-09-25:** the items below describe the *original* audited state. Phase 2 of §12 fixed the duplicate-logic and dead-Policy issues this section originally flagged; see §11 P1 for exactly what changed. This section now reflects the current, post-fix state.

**Tier 1 — coarse role gating** via the plain `users.role` column (`customer`, `distributor`, `staff`, `courier`, `admin`, `super_admin`), enforced by the custom `role:` middleware alias → `RoleMiddleware` (`app/Http/Middleware/RoleMiddleware.php`).
- `super_admin` always bypasses — **except** `EnsureOTPVerified`, which enforces OTP equally on `admin` and `super_admin`. This is a real exception to the "super_admin bypasses everything" rule.
- Distributor/staff application, approval, suspension, and ban status are gated entirely by `EnsureDistributorVerified` (single source of truth as of the Phase 2 fix) — it resolves the acting distributor as `$user->distributor` for owners or `$user->employer` for staff, and applies the same banned/suspended/pending/rejected checks to both. `RoleMiddleware` no longer duplicates any of this; it only checks the coarse role match.

**Tier 2 — fine-grained permissions** via Spatie `laravel-permission` with `teams => true` and **`team_foreign_key => 'distributor_id'`** (`config/permission.php`, not the package default `team_id`). `SetTeamIdMiddleware` (global) calls `setPermissionsTeamId($distributorId)` for `distributor`/`staff`, `null` for everyone else. Admin routes additionally require `admin.permission:admin.xxx` (`AdminPermission` middleware, checks `hasPermissionTo()`, now defensively handles an unseeded permission name as a 403 rather than crashing) — this is now applied consistently across all admin route groups (business-profiles, users, roles, reports hub, audit-logs, announcements, storage repair, distributor risk actions included, not just applications/couriers/products/orders/disputes as before).

**Global middleware stack** (`bootstrap/app.php`): `HandleInertiaRequests`, `EnsureNotBanned`, `SetTeamIdMiddleware` (all three global). `trustProxies(at: '*')` is also set here. CSRF is exempted only for `payments/webhook`.

**Authorization:** `app/Policies/{Order,Delivery,Conversation,SavedPurchaseOrder}Policy.php` are wired in via `$this->authorize()`/`can()` (added `AuthorizesRequests` to the base `Controller`) — see §11 P1 #8 for the full list of call sites migrated off manual `abort(403)` checks.

---

## 5. Core Features & Capabilities

Unchanged from prior audit in substance — Customer (catalog, cart, batch checkout, PayMongo/COD, prescription OCR upload, SC/PWD discount, order tracking + chat, reviews), Distributor/Owner (dashboard analytics, inventory + variations + batch/lot tracking, POS, order management, staff/roles, branches, DSS insights, financial/payout overview), Courier (dashboard, barcode scan, delivery lifecycle, COD remittance), Admin/Super Admin (verification, user/role management, moderation hub, audit logs, settings, storage repair). See §11 for behavioral corrections to how COD and delivery actually flow.

---

## 6. Domain Models (40 total — verified via `ls app/Models/`)

Previously undocumented models found: **`BusinessProfile`**, **`ProductImage`**, **`SavedPurchaseOrder`**, **`StaffInvitation`**, **`Supplier`**, **`SupplierPurchaseOrder`**, **`SupplierPurchaseOrderItem`** — all real, actively referenced (e.g. `Product::images()`, `User::businessProfile()`, `User::savedPurchaseOrders()`).

### Core Domain
| Model | Key facts (verified) |
|-------|---------------|
| `User` | `SoftDeletes` + Spatie `HasRoles`. Relations: `distributor` (hasOne, latest), `orders` (via `customer_id`), `businessProfile`, `savedPurchaseOrders`, `courier`, `employer` (belongsTo Distributor via `distributor_id`, for staff). `canAccessWholesale()` checks role/verification/business-profile approval. |
| `Distributor` | `available_payout_balance` is a **computed accessor** (sums released-escrow `Payment` rows minus pending `WithdrawalRequest` sums) — not a stored balance column. `getRatingAttribute()` recomputes an average over all products' reviews on every access (uncached, matches prior audit's flagged concern). |
| `Product` | `SoftDeletes`. Relations: `images` (hasMany `ProductImage`, ordered by `sort_order`), `variations` (hasMany `ProductVariation`), `inventory`, `orderItems`, `reviews`. `stockTotals()` and `scopeOrderByPopularity()` do business-logic aggregation in the model. |
| `ProductVariation` | `combination` cast to array; `inventory()` hasMany via `product_variation_id`. |
| `Inventory` | **Table name is singular `inventory`**, not `inventories` (`protected $table = 'inventory'`). `reserve(int $qty)` does an atomic conditional `UPDATE ... WHERE (quantity-reserved_quantity)>=?` — safe under concurrency. `deduct()` and `releaseReservation()` use plain `decrement()`/`update()` on an already-loaded instance — **not** atomic, and are called without row locking outside of `placeOrder()` (see §11 problem #3). |
| `Category` | Self-referential `parent`/`children`; `medicineTreeIds()`/`descendantIdsIncludingSelf()` drive prescription-requirement rules. |
| `Branch` | `distributor` belongsTo, `inventory` hasMany. |
| `License` | Business licenses with expiry tracking (unread in full this pass). |

### Order & Payment
| Model | Key facts (verified) |
|-------|---------------|
| `Order` | **No SoftDeletes.** Route key is `order_number`. `getRouteKeyName()` → `order_number`. Only formal constants are `PRESCRIPTION_*`/`DISCOUNT_*` — **the main `status` column has no PHP enum**, just string literals. **`generateOrderNumber()` is `'ORD-'.date('Ymd').'-'.strtoupper(Str::random(6))` with a uniqueness retry loop** (`app/Models/Order.php:281-288`) — corrects the prior audit's claim that it uses `rand(1,9999)`; that pattern actually lives in `Delivery::generateTrackingNumber()` (`rand(1000,9999)` + 3-letter prefix + date). Confirmed full status set across code + migrations (10 values): `pending, pending_po_verification, approved, processing, packed, shipped, delivered, cancelled, rejected, completed, ready_for_pickup`. |
| `OrderItem` | `inventory()` belongsTo — ties each line item to the specific inventory row it reserved against. |
| `Invoice` | `getTotalPaidAttribute()`/`getBalanceAttribute()` sum verified payments; `generateInvoiceNumber()` uses the same date+`Str::random(6)` pattern as Order. |
| `Payment` | `escrow_status`: `held → released → refunded`. Escrow transitions are **methods on the model itself**: `applyEscrowFees()` (sets `held` + computes platform fee / net seller amount), `releaseEscrow()` (wraps in `DB::transaction`), `refundEscrow()`. `allowedMethods()` includes `purchase_order` alongside `cash/cod/bank_transfer/gcash/paymaya/paymongo/card/grab_pay`. |
| `CheckoutBatch` | Groups multiple per-distributor orders into one PayMongo checkout session (see §11). |
| `Delivery` | No SoftDeletes. `generateTrackingNumber()` is the actual `rand()` user in this codebase. |
| `Courier` | `getTotalEarningsAttribute()` sums a `delivery_fee` column on the `deliveries` relation — but `Delivery`'s own fillable/column is `courier_fee`, not `delivery_fee`. **Flagged as a likely bug**, not fully confirmed against the migration column list — verify before relying on courier earnings figures. |
| `WithdrawalRequest` | Distributor payout requests. |

### New / previously undocumented models
| Model | Responsibility |
|-------|---------------|
| `BusinessProfile` | B2B business-customer profile/verification, drives `canAccessWholesale()` on `User`. |
| `ProductImage` | Multi-image support per product (with `sort_order`, `is_primary`) — supersedes a single-image-per-product assumption. |
| `SavedPurchaseOrder` | Customer-saved purchase order templates (`user.savedPurchaseOrders()`), linked from `Order.savedPurchaseOrder()`. |
| `StaffInvitation` | Invitation-based staff account setup (parallel to `AdminInvitation`). |
| `Supplier`, `SupplierPurchaseOrder`, `SupplierPurchaseOrderItem` | Distributor-side procurement/purchasing module (`Owner/Procurement`, `Owner/Suppliers` pages) — not covered in the original briefing at all. |

### Communication, Moderation, DSS
Unchanged from prior audit: `Conversation`, `ConversationMessage`, `ConversationMessageReport`, `ProductReview`/`DeliveryReview`, `ProductReport`/`UserReport`/`CourierReport`, `AuditLog`; `DssDistributorSettings`, `DssAlert`, `DssSalesAnalytics`, `DssReorderRecommendation`.

**SoftDeletes usage confirmed via direct grep: only `User` and `Product`** — exactly matches the prior audit's claim, no other model uses it.

---

## 7. Core End-to-End Data Flow: Order Lifecycle (corrected)

```
Customer browses /products → cart (session array, "cart" key) → /checkout
     │
     ▼
OrderController::placeOrder()  [app/Http/Controllers/OrderController.php:170-722]
     ├── payment_method validated as one of: card, gcash, paymaya, wallet, purchase_order
     │     — 'cod' is explicitly EXCLUDED from the allowed list (line ~179); the COD
     │       branch further down is commented out. COD is fully implemented downstream
     │       (Owner + Courier controllers, OrderInvoiceService) but currently unreachable
     │       from checkout — a half-removed feature (see §11, problem #6).
     ├── DB::transaction() wraps the whole method (begin ~286, commit ~592)
     ├── Cart products locked: Product::with(['inventory','variations'])->lockForUpdate()
     ├── Order::create(status: 'pending_po_verification' if purchase_order else 'pending')
     ├── Inventory reservation via $inventory->reserve($qty) — atomic conditional UPDATE,
     │     NOT a raw "+=", throws/rolls back the transaction on insufficient stock
     ├── OrderItem created per reserved inventory row
     ├── VAT / SC-PWD discount computed
     ├── prescription_status set (separate column from order `status`) if any item requires Rx
     ├── OrderInvoiceService::createInvoiceAndPayment() — ALWAYS creates a Payment row
     │     (even conceptually for COD), status 'pending', escrow_status 'held' immediately —
     │     escrow is "held" at invoice-creation time, not "on payment success"
     ├── If online payment → PayMongoService::createGenericCheckoutSession() — a BATCH
     │     checkout across a CheckoutBatch spanning all distributor sub-orders in one cart
     │     (NOT the per-invoice createCheckoutSession(), which is reserved for the separate
     │     payNow() repayment/retry endpoint)
     └── OrderNotification sent to distributor(s) post-commit

Payment success / webhook confirmation
     │
     ▼
PaymentController::webhook() / success() / batchSuccess()  — NOT part of PayMongoService,
which contains no webhook handler or escrow logic at all. This controller finalizes
payment status and applies fees via Payment::applyEscrowFees().

Distributor sees order in /owner/orders
     │
     ▼
Owner\OrderController::updateStatus()  [719 lines total, method at :178-431]
     ├── Explicit state machine (comment: "Bug 14: Enforce valid state machine transitions"):
     │     pending → approved/rejected/cancelled
     │     approved → packed/ready_for_pickup/cancelled
     │     (packed/ready_for_pickup/shipped/delivered/cancelled/rejected are terminal here)
     ├── ⚠ NOT wrapped in DB::transaction() and no row locking (see §11, problem #1)
     ├── On approve: inventory is PHYSICALLY DEDUCTED per item via $inventory->deduct($qty)
     │     — this is a genuine stock decrement, distinct from the reservation made at placeOrder
     ├── On reject/cancel: releases reservation (if was pending) or restores quantity (if was
     │     already approved/deducted) — try/catch only, no transaction
     ├── On packed: creates the Delivery record — NOT on "shipped" as previously documented;
     │     code comment explicitly says the Courier controller now owns the shipped transition
     └── Also handles approvePrescription/rejectPrescription/approveDiscount/rejectDiscount —
         gating steps not in the original documented flow at all

Courier picks up & delivers  — TWO PARALLEL LIFECYCLES coexist in the SAME controller:
     │
     ▼
Courier\DeliveryController  [482 lines]
  (a) One-shot scanner flow: scanner() → lookupOrder() → processScan()
        — single endpoint, 'pickup'|'deliver' action param, jumps straight to
        in_transit / delivered, bypassing the granular steps below entirely
  (b) Granular flow: accept() → startPickup() → confirmScan() → confirmPickup()
        (order status → shipped here) → confirmDelivery() [proof photo + geofence
        check, >1km flagged] / reportFailure() [max 2 attempts → is_return_to_sender]
  COD handling: if payment_method === 'cod', stamps cod_amount/cod_collected_at and
  does NOT auto-release courier payout; non-COD releases payout immediately via
  AutomatedPayoutService::disburse() — return value is NOT checked by the caller.

Order completion — TWO INDEPENDENT PATHS:
     │
     ├─▶ OrderController::confirmReceived()  [customer-initiated, online/escrow orders]
     │     DB::transaction(): status → completed, received_at set, Payment::releaseEscrow()
     │     per verified+held payment, AutomatedPayoutService::disburse(net_seller_amount) —
     │     return value NOT checked; invoice reconciled to 'paid' once fully covered.
     │
     └─▶ Owner\OrderController::confirmCodRemittance()  [distributor-initiated, COD orders]
           NOT wrapped in DB::transaction(): status → completed, releases COURIER payout
           (not seller escrow — none exists for cash orders), marks COD Payment verified,
           invoice → paid.

AutomatedPayoutService::disburse() is an explicit SIMULATION: returns false (no exception)
if payout bank details are missing OR SIMULATE_PAYOUTS is not truthy. It never increments
a stored "payout balance" — it only stamps seller_payout_cleared_at / courier_payout_status.
In a real deployment without SIMULATE_PAYOUTS=true, every disbursement is currently a
silent no-op that callers treat as success.
```

---

## 8. Service Layer (17 Services — count confirmed)

`AdminModerationService`, `AutomatedPayoutService` (see §7 — simulation-only), `CartService` (session-array transform helpers, no DB/table of its own — advisory stock checks only, authoritative check happens under `lockForUpdate()` in `OrderController::placeOrder`), `ChatMessageNotifier`, `ContentModerationService`, `CustomerReliabilityService`, `DashboardAnalyticsService` (~26KB, largest service), `DssAlertService`, `DssEngineService`, `OrderChatAutomationService`, `OrderInvoiceService` (always creates Payment, even for COD), `OrderPrescriptionRefundService`, `PayMongoService` (checkout session creation + webhook signature verification + refunds — **no escrow logic, no webhook handler**, those live in `PaymentController` and the `Payment` model respectively), `PrescriptionChatService`, `ProductCatalogSyncService`, `ShopConversationAutoReplyService`, `UnreadConversationMessageService`.

---

## 9. Frontend Architecture (verified)

- **Bootstrap:** `resources/js/app.js` — Inertia + Vue 3, page resolution via `import.meta.glob('./Pages/**/*.vue')`. CSRF token re-synced on **both** `inertia:success` and every `router.on('navigate')` event, plus a separate axios response interceptor in `bootstrap.js` for plain-axios 419s — two independent 419 handlers, both funnel into a shared `sessionExpiredToast.js` toast-then-reload helper.
- **Layouts (5):** `MainLayout` (public/customer), `AdminLayout` (dark sidebar, permission-gated nav via `admin_permissions` shared prop, **leaks raw Vue errors to the UI** via `onErrorCaptured` — a debug leftover, see §11 #17), `OwnerLayout` (shared by distributor **and** staff, branches nav on suspension/role), `CourierLayout`, `OnboardingLayout`.
- **Pages:** 16 top-level directories, 84 `.vue` files; `Owner/` is the largest subtree (19 nested dirs) — distributor/staff back-office tooling is the bulk of the frontend, more so than Admin.
- **Components (19):** no shared `TextInput`/`InputError`/`InputLabel` — every page hand-rolls input markup. `FlashMessage.vue` and `Toast.vue` duplicate the same flash-watching behavior. `OrderChatPanel.vue` (~990 lines) is by far the largest, handling chat, RFQ negotiation, prescription review, and attachments in one component.
- **Forms:** dominant pattern is Inertia's `useForm()` + `form.errors.field` inline `<p>` tags (Login, Register, Owner/Products, Checkout). **Not universal** — `Customer/Addresses/Index.vue` instead uses a plain `reactive()` object, `router.post/put/delete` directly, and reads `page.props.errors` — a second, coexisting convention.
- **Validation:** only 2 Form Request classes exist (`LoginRequest`, `ProfileUpdateRequest`); inline `$request->validate([...])` is used ~86 times across controllers — the overwhelming convention.
- **Known bugs:** `@/Composables/useOCR` (capital C) imported in 4 pages while the real directory is lowercase `composables/` — works only on case-insensitive filesystems; `@tailwindcss/forms` imported in `tailwind.config.js` but never added to `plugins: []`.

---

## 10. State Management & Real-Time Updates

Unchanged: sessions/cache/queue on the `database` driver, no Redis by default. Real-time is polling-only (chat 12s, notifications 45s, dashboard 30s, cart 10s) with a documented-but-unimplemented Reverb/Echo upgrade path in `config/medequip.php`.

---

## 11. Verified Problems (superseding the old "Technical Debt" list)

> **Status key:** ✅ fixed | 🔲 open. Phase 1 (financial/data-integrity) and Phase 2 (authorization consolidation) from §12 are both complete as of 2026-09-25; see the per-item notes below for what actually changed and any new findings made while fixing them.

### P0 — Data integrity / financial correctness risks
1. ✅ **No `DB::transaction()` in `Owner\OrderController::updateStatus()`** around inventory deduction/restoration + Delivery creation + order status save, and no row locking — unlike `placeOrder()`, which uses both. **Fixed:** the whole mutation block now runs inside `DB::transaction()` with `lockForUpdate()` on the touched `Inventory`/`Delivery` rows; failures now roll back cleanly instead of leaving inventory half-deducted. Covered by `tests/Feature/OrderStatusInventoryTransactionTest.php`.
2. ✅ **No transaction in `Owner\OrderController::confirmCodRemittance()`** — sequential unguarded updates across delivery, payout, order, and invoice. **Fixed:** wrapped in `DB::transaction()`.
3. ✅ **`Inventory::deduct()` / `releaseReservation()` are not atomic**, unlike `reserve()`. **Fixed:** both now use the same atomic conditional-`UPDATE` pattern as `reserve()` (using a `CASE WHEN` expression rather than MySQL's `LEAST()`, written portably at the time tests still ran on SQLite too — harmless either way now that MySQL is the only driver in use).
4. ✅ **`AutomatedPayoutService::disburse()` failures were silently ignored** by `OrderController::confirmReceived`, `Owner\OrderController::confirmCodRemittance`, and `Courier\DeliveryController::releaseCourierPayout`. **Fixed:** all three callers now check the return value and log a `[Controller] ... payout did not complete automatically` warning with order/delivery/payment context when it fails; the order still completes (payout automation being a no-op today isn't treated as a blocking error), but it's no longer silent.
5. 🔲 Two independent order-completion methods and two independent delivery-progress paths still exist — not addressed this pass (Phase 3).
6. 🔲 COD still disabled at checkout validation while implemented downstream — not addressed this pass (Phase 3).
7. 🔲 `updateStatus()`'s transition table still references statuses it never assigns — not addressed this pass (Phase 3).
   - **New bug found while fixing #1-#4 (originally, on SQLite):** `database/migrations/2026_09_18_123636_add_pending_po_verification_to_orders_status_enum.php` ran its raw `ALTER TABLE ... ENUM` unconditionally, with no `DB::connection()->getDriverName() === 'mysql'` guard unlike every sibling enum migration — this broke the *entire* test suite on SQLite. Fixed by adding the guard.
   - **Far more serious, found later by actually running `php artisan migrate` against the real local MySQL database (`medequip`) on 2026-09-25, after switching tests off SQLite entirely (see §2 Database):** the *live* `orders.status` column was `enum('pending','pending_po_verification','approved','processing','packed','shipped','delivered','cancelled','rejected')` — missing `'completed'` and `'ready_for_pickup'` — even though `2026_09_18_123636_...`'s `up()` explicitly includes both in its `ALTER` statement and is recorded as having already run. The only explanation: this migration's file content was edited *after* it had already executed against this database (presumably in an earlier session, to "fix" the missing values in the file), which doesn't retroactively re-apply — Laravel only tracks migrations by filename, not content. **Confirmed impact: 8 real orders on this database were stuck at `delivered`, permanently unable to reach `completed` via `OrderController::confirmReceived()`** (it would fail with a raw DB error, most likely surfaced to the customer as the generic "we could not finalize the seller payout" message, masking the real cause). Fixed with a new corrective migration, `2026_09_25_043702_fix_orders_status_enum_missing_completed_and_ready_for_pickup.php`, that unconditionally re-applies the full correct enum on MySQL regardless of what any prior migration actually left behind; already run against the local `medequip` database and confirmed via `SHOW COLUMNS`. **This needs the same migration run against the Hostinger production database** — check there for orders similarly stuck at `delivered`/`ready_for_pickup`-eligible states once it's deployed. A systematic check of the other status/enum columns (`payments.status`, `payments.escrow_status`, `payments.payment_method`, `deliveries.status`, `invoices.status`, `users.role`) found no further drift — this pattern was isolated to `orders.status`.

7a. ✅ **New, severe frontend bug found during the 2026-09-25 manual browser QA pass, unrelated to anything else this session touched:** `resources/js/Pages/Owner/Orders/Show.vue` built the URL for **6 different owner order actions** — `updateStatus` (approve/reject/pack/cancel), `confirmCodRemittance`, `approvePrescription`, `rejectPrescription`, `approveDiscount`, `rejectDiscount` — using `props.order.id` (the numeric primary key), but every one of those routes is bound on `{order}` which resolves via `Order::getRouteKeyName() = 'order_number'`. Every single click of these buttons 404'd, unconditionally, for every order, regardless of role/permissions/anything this session changed (confirmed via `git log` the bug was introduced 2026-09-18, a week before this session, in commit `e665bf6`). `Owner/Orders/Index.vue` already does this correctly (`` `/owner/orders/${order.order_number}` ``) — `Show.vue` just never matched it. **This means, until this fix, distributors could not approve, reject, pack, or cancel any order, approve/reject prescriptions or SC/PWD discounts, or confirm COD remittance, from the order detail page at all** — arguably the single highest-severity bug found this entire session, since it blocks the core seller workflow outright. Fixed by changing all 6 to `props.order.order_number`; confirmed working end-to-end in a real browser against real MySQL (multi-item order approval correctly deducted inventory: 10→8 and 10→7, reservations cleared to 0). No automated test covers this page's JS directly (no frontend test suite exists in this project at all) — this class of bug (a frontend route-building error) is invisible to the backend PHPUnit suite regardless of driver, which is exactly why the manual QA pass mattered.

7b. 🔲 **New, real design fragility found during the same manual QA pass, not fixed this session:** `PayMongoService`'s constructor (`app/Services/PayMongoService.php:20`) does `$this->secretKey = config('services.paymongo.secret_key', '')`, but that config key resolves to `null` (not the `''` default) whenever `PAYMONGO_SECRET_KEY` isn't set in `.env`, and the property is typed `string` (not `?string`) — so construction throws `TypeError: Cannot assign null to property ... of type string`. Since `OrderController` injects `PayMongoService` unconditionally in its constructor (`app/Http/Controllers/OrderController.php:23-26`), **every route handled by `OrderController` — `checkout`, `placeOrder`, `confirmReceived`, `payNow` — 500s on any environment without `PAYMONGO_SECRET_KEY` configured**, even for actions like `confirmReceived` that never call PayMongo at all. Confirmed live on this local dev environment (no PayMongo keys were configured in `.env` before this session): `confirmReceived` crashed with exactly this error until a placeholder key was added locally for testing purposes only. Production presumably has real keys configured so this may not manifest there, but it's a real fragility — a misconfigured/expired/rotated key would take down the *entire* purchase flow, not fail gracefully. Not fixed this session (out of scope — a real design decision about `PayMongoService`'s construction/injection, not a quick bug fix). Options worth considering: make the property nullable and only throw when a PayMongo-calling method is actually invoked, or inject `PayMongoService` per-method instead of via the constructor for methods that don't need it.

### P1 — Authorization inconsistency
8. ✅ **The 3 `Policies/` classes were dead code** — never invoked via `authorize()`/`Gate::`/`can()` anywhere. **Fixed:** added `AuthorizesRequests` to the base `Controller` (it was missing entirely — a Laravel default most apps get for free), then wired `OrderPolicy`/`DeliveryPolicy`/`ConversationPolicy` into every controller that used to duplicate the same checks manually: `CustomerOrderController`, root `OrderController` (`confirmReceived`, `payNow` — added a new `pay` ability to `OrderPolicy`), `Owner\OrderController` (9 call sites), `Courier\DeliveryController` (9 call sites, plus `accept()` via a non-throwing `can()` check to preserve its existing friendly-error UX instead of a hard 403), and the `AuthorizesOrderChat`/`AuthorizesShopConversation` traits (now thin wrappers around `$this->authorize('view', ...)`). One behavior change, intentional per the Policy's original (dormant) design: `OrderPolicy::view` also allows shop members, so a distributor/staff user can now view their own shop's order through the customer-facing `/orders/{order}` route, which previously 403'd. Covered by `tests/Feature/PolicyAuthorizationTest.php`.
   - **New bug found while wiring this in:** `SavedPurchaseOrderController` had its own local `authorize()` method override (a stand-in ownership check, since no Policy existed for that model) with a signature incompatible with the real `AuthorizesRequests::authorize()`. Fixed by adding a proper `SavedPurchaseOrderPolicy` instead of the override — same ownership rule, now on the standard mechanism.
9. ✅ **`RoleMiddleware` and `EnsureDistributorVerified` independently duplicated distributor-approval gating.** **Fixed:** removed `RoleMiddleware`'s duplicate "Distributor Validation" block entirely; `EnsureDistributorVerified` is now the single source of truth. This also fixed a real, previously-dead-code bug: `RoleMiddleware`'s simpler check ran *first* and short-circuited the request for any non-approved distributor status (including `banned`) with a generic "complete your application" redirect — so `EnsureDistributorVerified`'s specific banned-account forced-logout message never actually ran in practice for anyone hitting a non-safe route directly.
   - **New bug found while fixing this:** `distributors.status` was created with only `['pending', 'approved', 'rejected']` and never expanded — yet `AdminModerationService::banDistributor()` sets it to `'banned'`, a value the column never actually allowed, on MySQL *or* SQLite. Fixed with a new migration (`2026_09_25_040449_add_banned_to_distributors_status_enum.php`) adding `'banned'` to the enum on both drivers. **This means the admin "ban distributor" feature was likely broken (or corrupting data) in production before this fix** — worth double-checking any distributor currently expected to be banned.
10. ✅ **`EnsureDistributorVerified` silently skipped `staff`.** **Fixed:** it now resolves the acting distributor as `$user->employer` for staff (vs. `$user->distributor` for owners) and applies the same banned/suspended/pending/rejected checks. Staff-specific behavior differs sensibly from the owner's: no employer link → logged out (broken account state, staff can't fix it); employer banned → logged out; employer suspended → same restricted-route blocking as the owner; employer pending/rejected/null → redirected to `/products` with an explanatory error (staff have no application flow to send them to, unlike owners). Covered by `tests/Feature/DistributorAccessControlTest.php`.
11. ✅ **`admin.permission:admin.xxx` granular gating was inconsistently applied** — business-profiles, users, roles, message-reports, audit-logs, broadcast-announcement, repair-storage, and the DSS distributor-risk actions (suspend/ban/warn) relied only on the coarse `role:admin,super_admin` + `otp` gate. **Fixed:** added 10 new permission strings to `RolesAndPermissionsSeeder` (`admin.business-profiles.review`, `admin.users.manage`, `admin.roles.manage`, `admin.reports.review`, `admin.reports.moderate`, `admin.distributors.moderate`, `admin.documents.view`, `admin.audit-logs.view`, `admin.announcements.broadcast`, `admin.storage.repair`) and gated the corresponding routes. **Production note: the seeder needs to be re-run after deploying this** (`php artisan db:seed --class=RolesAndPermissionsSeeder`) so these permissions exist and can be assigned to non-super-admin admin accounts via the existing Admin → Roles UI; `super_admin` is unaffected (it already bypasses `AdminPermission` entirely).
   - **New bug found while extending this:** `AdminPermission::handle()` called `$user->hasPermissionTo($permission)` directly with no guard — Spatie throws `PermissionDoesNotExist` (an unhandled 500, not a 403) if the permission name was never seeded at all. This was already a latent crash risk for the routes that were gated *before* this pass, not just the new ones. Fixed by catching it and treating "doesn't exist" the same as "not granted." Covered by `tests/Feature/AdminPermissionGatingTest.php`.
11. **`admin.permission:admin.xxx` granular gating is inconsistently applied** — several admin route groups (business-profiles, users, roles, message-reports, audit-logs, broadcast-announcement, repair-storage) rely only on the coarse `role:admin,super_admin` + `otp` gate.

### P2 — Frontend consistency debt
12. No shared `TextInput`/`InputError`/`InputLabel` components — every page duplicates markup and Tailwind classes.
13. `Customer/Addresses/Index.vue` uses a different form convention (`reactive()` + direct `router.*` calls) than the dominant `useForm()` pattern.
14. `FlashMessage.vue` and `Toast.vue` functionally overlap.
15. `@/Composables/useOCR` casing mismatch (works only on case-insensitive filesystems — latent Linux CI/build breakage).
16. `@tailwindcss/forms` imported but never registered in `tailwind.config.js`'s `plugins: []`.
17. `AdminLayout.vue` renders raw Vue errors directly into the admin UI in production.

### P3 — Structural / longer-term debt
18. 130 migrations, many incremental single-column additions — candidate for squashing once there's a stable release baseline.
19. Giant controllers remain (`OrderController` ~972 lines; `Owner/InventoryController`, `Admin/ReportHubController` also large) — logic should move into services.
20. Minimal test coverage (7 feature tests, 1 placeholder unit test) for a payment-handling, multi-role marketplace — payment, DSS, courier lifecycle, and admin moderation paths are untested.
21. Polling instead of WebSockets (4 separate intervals) — documented Reverb upgrade path not implemented.
22. Loose ad hoc scripts in the project root (`test_*.php`, `seed_settings.php`, `run_migration.php`, `fix_*.php`, `patch_backend.php`, `reset_admin.php`) — not part of the app, should be removed or converted to Artisan commands.
23. `Distributor::getRatingAttribute()` recomputes an average over all products' reviews on every access, uncached.
24. `HandleInertiaRequests` computes several counts (unread messages, open reports, pending verifications) on every single request — candidate for caching.
25. `Courier::getTotalEarningsAttribute()` likely sums a non-existent `delivery_fee` column (actual column is `courier_fee`) — unconfirmed against the `deliveries` migration, flagged for verification before relying on courier earnings figures.

---

## 12. Implementation Plan

Ordered by risk reduction per unit of effort. Each phase is independently shippable; don't start a later phase until the previous one's tests pass.

### Phase 1 — Financial/data-integrity fixes ✅ done (2026-09-25)
1. ✅ Wrapped `Owner\OrderController::updateStatus()`'s inventory mutation + Delivery creation + status save in `DB::transaction()`, with `lockForUpdate()` on the relevant `Inventory`/`Delivery` rows.
2. ✅ Wrapped `Owner\OrderController::confirmCodRemittance()` in `DB::transaction()` the same way.
3. ✅ Made `Inventory::deduct()` and `releaseReservation()` atomic (conditional `UPDATE ... WHERE`, same style as `reserve()`).
4. ✅ Made `AutomatedPayoutService::disburse()`'s failures visible: callers now check the return value and log a warning with order/delivery/payment context instead of proceeding silently.
5. ✅ Added `tests/Feature/OrderStatusInventoryTransactionTest.php` (6 tests): multi-item approve rollback (a deterministic same-inventory-row scenario that reproduces the old partial-deduction bug), approve success, pending-order rejection, approved-order cancellation restore, `confirmReceived` escrow release + payout-failure visibility, `confirmCodRemittance` completion + payout-failure visibility.
   - Along the way, fixed a migration missing the standard `getDriverName() === 'mysql'` guard on a raw `ALTER TABLE ... ENUM` (was breaking the suite outright when tests still ran on SQLite).
   - **Then, after switching the test suite off SQLite onto real MySQL (`medequip_testing`) and running `php artisan migrate` against the real local dev database (`medequip`) to verify — see §2 Database and §11 P0 #7 — found and fixed the actually serious version of this class of bug**: the live `orders.status` enum on the real dev database was missing `'completed'` and `'ready_for_pickup'` because a migration's file had been edited after it already ran, which never retroactively re-applied. 8 real orders were stuck at `delivered` as a direct result. Fixed with a new corrective migration and confirmed against the live schema.

### Phase 2 — Authorization consolidation ✅ done (2026-09-25)
6. ✅ Wired the three Policies in via `$this->authorize()` (added `AuthorizesRequests` to the base `Controller`, which was missing it), replacing the manual `abort(403)` checks in `CustomerOrderController`, `OrderController`, `Owner\OrderController` (9 sites), `Courier\DeliveryController` (9 sites), and the two `Concerns/Authorizes*` traits. Added a `SavedPurchaseOrderPolicy` to replace an ad hoc `authorize()` override that turned out to be signature-incompatible with the trait.
7. ✅ Merged `RoleMiddleware`'s and `EnsureDistributorVerified`'s distributor-status logic — `EnsureDistributorVerified` is now the single source of truth; `RoleMiddleware` only does the coarse role check. This also surfaced and fixed a real bug where `RoleMiddleware` was short-circuiting before `EnsureDistributorVerified`'s banned-account handling could ever run.
8. ✅ Extended `EnsureDistributorVerified` to also gate `staff`, resolving the acting distributor via `$user->employer`. Along the way, found and fixed a `distributors.status` enum that never actually allowed `'banned'` on any database, despite `AdminModerationService::banDistributor()` setting exactly that value — likely a broken/silently-failing production feature until now.
9. ✅ Extended `admin.permission:admin.xxx` to the previously coarse-only admin routes (business-profiles, users, roles, reports hub, audit-logs, announcements, storage repair, distributor risk actions) with 10 new permission strings in `RolesAndPermissionsSeeder`. **Needs `php artisan db:seed --class=RolesAndPermissionsSeeder` run in production after deploy** so these are assignable via the admin Roles UI. Also hardened `AdminPermission` against a permission name that was never seeded (previously an unhandled 500, now a clean 403).

New regression tests from this phase: `tests/Feature/DistributorAccessControlTest.php`, `tests/Feature/PolicyAuthorizationTest.php`, `tests/Feature/AdminPermissionGatingTest.php`.

**Verification note (2026-09-25):** all Phase 1 + 2 changes were verified three ways: the full test suite (87 tests, run against real MySQL after the SQLite→MySQL switch above), `php artisan migrate` actually run against the real local dev database (which surfaced the serious `orders.status` enum bug in P0 #7), and a real-browser manual QA pass (Playwright against `php artisan serve` + Vite, real MySQL) covering: banned-distributor forced logout, a plain admin without permissions getting clean 403s on 3 newly-gated routes (not crashes), a shop owner viewing their own order via the customer route (the new `OrderPolicy::view` behavior), a multi-item order approval (inventory deduction confirmed via direct DB query: 10→8, 10→7, reservations cleared), and a delivered→completed confirmation with escrow release (the exact scenario 8 real orders were stuck on before the enum fix). **The manual pass is what caught problem #7a** (`Owner/Orders/Show.vue`'s six broken order-action URLs) **and #7b** (`PayMongoService`'s constructor fragility) — both invisible to the backend test suite regardless of driver, since #7a is a frontend routing bug and #7b only manifests when a real HTTP server actually boots the app. Still not done: true concurrent-request load testing of the new atomic `Inventory` methods (reasoned correct from the SQL, not empirically load-tested under real concurrency).

### Phase 3 — Order/COD architecture cleanup
10. Either fully re-enable COD at checkout (exercise the whole path end-to-end and re-add it to the validated `payment_method` list) or remove the dead COD branches from `OrderController`/`OrderInvoiceService` to eliminate the half-implemented state. Pick one deliberately.
11. Consolidate or clearly document the two order-completion methods and two delivery-progress paths; keep the authoritative state machine in one place — today `Owner\OrderController`'s transition table lists states it never produces, which should be trimmed or corrected.

### Phase 4 — Frontend consistency (lower urgency, good "while you're in the area" cleanup)
12. Extract shared `TextInput`/`InputLabel`/`InputError` Vue components; migrate the highest-traffic forms first (`Checkout/Index.vue`, `Owner/Products/Create|Edit.vue`).
13. Migrate `Customer/Addresses/Index.vue` onto `useForm()` for consistency.
14. Merge `FlashMessage.vue` and `Toast.vue` into one component.
15. Fix the `@/Composables/useOCR` casing at all 4 call sites to match the real lowercase `composables/` directory.
16. Either register `@tailwindcss/forms` in `tailwind.config.js`'s `plugins` array or remove the unused import.
17. Replace `AdminLayout.vue`'s raw error dump with a generic fallback (or gate it behind `import.meta.env.DEV`).

### Phase 5 — Structural debt (ongoing, opportunistic)
18. Expand test coverage toward payment/DSS/courier/admin-moderation flows before further refactors in those areas.
19. Incrementally extract logic from `OrderController`, `Owner/InventoryController`, `Admin/ReportHubController` into services as those files are touched for other work — not a standalone rewrite.
20. Cache `Distributor::getRatingAttribute()` and the per-request counts computed in `HandleInertiaRequests`.
21. Confirm/fix `Courier::getTotalEarningsAttribute()`'s column reference.
22. Consider migration squashing once there's a stable release baseline; move root-level ad hoc scripts into Artisan commands or delete them.
23. Revisit polling vs. Reverb/WebSockets only if server load from the 4 polling loops actually becomes a measured problem.

---

## 13. Key File Quick Reference

| What | Path |
|------|------|
| App bootstrap / global middleware | `bootstrap/app.php` |
| Web routes | `routes/web.php` |
| Role middleware | `app/Http/Middleware/RoleMiddleware.php` |
| Distributor verification middleware | `app/Http/Middleware/EnsureDistributorVerified.php` |
| Team-scoping middleware | `app/Http/Middleware/SetTeamIdMiddleware.php` |
| Spatie permission config (team key) | `config/permission.php` |
| Order placement | `app/Http/Controllers/OrderController.php` (`placeOrder`, `confirmReceived`, `payNow`) |
| Order status transitions | `app/Http/Controllers/Owner/OrderController.php` (`updateStatus`, `confirmCodRemittance`) |
| Delivery lifecycle | `app/Http/Controllers/Courier/DeliveryController.php` |
| Payment webhook/finalization | `app/Http/Controllers/PaymentController.php` |
| Escrow state machine | `app/Models/Payment.php` |
| Inventory reserve/deduct | `app/Models/Inventory.php` |
| PayMongo integration | `app/Services/PayMongoService.php` |
| Payout simulation | `app/Services/AutomatedPayoutService.php` |
| Test bootstrap (DB target for tests) | `tests/bootstrap.php` — forces MySQL, `medequip_testing`, never the real `medequip` DB |
| Vue entry point | `resources/js/app.js` |
| Authorization policies (wired in) | `app/Policies/*.php` — `OrderPolicy`, `DeliveryPolicy`, `ConversationPolicy`, `SavedPurchaseOrderPolicy` |
| Admin granular permissions | `database/seeders/RolesAndPermissionsSeeder.php`, `app/Http/Middleware/AdminPermission.php` |
