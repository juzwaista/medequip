# MedEquip — Project Context & Architectural Briefing

> **Last audited:** 2026-09-01 | **Auditor role:** Senior Software Architect

---

## 1. Project Overview

**MedEquip** is a **B2B/B2C medical equipment & pharmaceutical marketplace** built for the Philippine market. It connects medical equipment distributors with customers (hospitals, clinics, individual buyers). The platform features multi-role access (customer, distributor/staff, courier, admin, super admin), an escrow-based payment system via PayMongo, a built-in courier fleet app, a Decision Support System (DSS) for inventory intelligence, and a real-time messaging/chat system.

---

## 2. Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| **Backend Framework** | Laravel | ^12.0 |
| **Language** | PHP | ^8.2 |
| **Frontend Framework** | Vue.js | ^3.5 |
| **SPA Bridge** | Inertia.js (Vue 3 adapter) | ^2.0 |
| **CSS Framework** | Tailwind CSS | ^3.4 (with `@tailwindcss/forms`) |
| **Build Tool** | Vite | ^7.0 |
| **Package Manager (PHP)** | Composer | (lockfile present) |
| **Package Manager (JS)** | npm | (lockfile present) |
| **Database** | SQLite (default) | configurable to MySQL |
| **Payment Gateway** | PayMongo | REST API v1 |
| **Routing (client)** | Ziggy | ^2.6 (Laravel→JS named routes) |
| **Runtime** | XAMPP (local) / Hostinger (production) | — |

### Notable JS Dependencies

| Package | Purpose |
|---------|---------|
| `chart.js` | Dashboard analytics charts |
| `leaflet` | Map picker & display (geolocation) |
| `@zxing/browser` + `@zxing/library` | Barcode/QR scanning (courier app) |
| `vue-qrcode-reader` + `qrcode.vue` | QR code generation & reading |
| `tesseract.js` | OCR for prescription/ID scanning |
| `lodash` | Utility functions |
| `axios` | HTTP client (bootstrapped) |
| `alpinejs` | Lightweight interactivity (likely residual from Breeze) |

### PHP Dependencies

| Package | Purpose |
|---------|---------|
| `laravel/breeze` | Auth scaffolding (registration, login, email verification) |
| `inertiajs/inertia-laravel` | Server-side Inertia adapter |
| `tightenco/ziggy` | Named-route sharing with JS |
| `laravel/pail` | Real-time log tailing |
| `laravel/pint` | PHP code style fixer (PSR-12 / Laravel preset) |
| `phpunit/phpunit` | Testing (^11.5) |
| `fakerphp/faker` | Test data generation |

---

## 3. Architecture & Directory Structure

```
medequip/
├── app/
│   ├── Console/Commands/       # 6 Artisan commands (purge accounts, doc expiry alerts, etc.)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # 13 controllers — dashboard, user mgmt, reports, moderation
│   │   │   ├── Auth/           # Breeze auth + OTP controller + admin setup
│   │   │   ├── Courier/        # 2 controllers — dashboard, delivery lifecycle
│   │   │   ├── Owner/          # 16 controllers — inventory, orders, POS, DSS, profile, staff
│   │   │   ├── Static/         # Contact, About pages
│   │   │   ├── SuperAdmin/     # Admin management
│   │   │   ├── Concerns/       # Shared controller traits
│   │   │   └── [root-level]    # 17 shared controllers (cart, order, payment, products, chat)
│   │   ├── Middleware/         # 5 middlewares (Inertia, Role, Ban, OTP, DistributorVerified)
│   │   └── Requests/          # Form request validation (Auth/, ProfileUpdateRequest)
│   ├── Models/                 # 34 Eloquent models
│   ├── Notifications/          # 13 notification classes (mail + database channel)
│   ├── Policies/               # 3 authorization policies (Conversation, Delivery, Order)
│   ├── Providers/              # Service providers
│   ├── Rules/                  # 1 custom validation rule (SafeUpload)
│   ├── Services/               # 17 service classes (business logic layer)
│   ├── Support/                # 2 helpers (NotificationFilters, PublicStorageUrl)
│   └── View/                   # View composers/components
├── bootstrap/app.php           # Application bootstrap & middleware registration
├── config/                     # 14 config files (includes medequip.php, order_chat.php, profanity.php, cavite.php)
├── database/
│   ├── factories/              # Model factories
│   ├── migrations/             # 115 migration files (extensive schema evolution)
│   └── seeders/                # 8 seeders (categories, distributors, products, orders, etc.)
├── resources/
│   ├── css/                    # app.css (Tailwind entry point)
│   ├── js/
│   │   ├── app.js              # Inertia + Vue bootstrap
│   │   ├── bootstrap.js        # Axios defaults, CSRF setup
│   │   ├── Components/         # 17 reusable Vue components
│   │   ├── Layouts/            # 5 layout components (Main, Admin, Courier, Owner, Onboarding)
│   │   ├── Pages/              # 14 page directories (Admin, Auth, Cart, Checkout, Courier, Customer, etc.)
│   │   ├── composables/        # 2 composables (notification polling, OCR)
│   │   └── utils/              # 2 utility modules (profanity hints, order status messages)
│   └── views/                  # Blade templates (root Inertia template)
├── routes/
│   ├── web.php                 # ~503 lines — all web routes
│   ├── auth.php                # Breeze auth routes
│   └── console.php             # Console route definitions
├── tests/
│   ├── Feature/                # 7 feature tests + Auth/ test suite
│   └── Unit/                   # 1 unit test (example only)
├── public/                     # Web root (assets, storage symlink)
└── storage/                    # Logs, uploads, cache, sessions
```

---

## 4. Application Entry Points & Bootstrap Sequence

### Backend (PHP)
1. **`public/index.php`** → Laravel's standard HTTP kernel
2. **`bootstrap/app.php`** configures:
   - Web routing from `routes/web.php` + `routes/auth.php`
   - Console routing from `routes/console.php`
   - Health check at `/up`
   - **Global web middleware stack:** `HandleInertiaRequests`, `EnsureNotBanned`
   - **CSRF exemption:** `payments/webhook` (PayMongo)
   - **Middleware aliases:** `role`, `verified`, `otp`
   - **Scheduled tasks:**
     - `accounts:purge-deactivated` — daily, production only
     - `medequip:alert-doc-expiry` — daily, all environments

### Frontend (Vue 3)
1. **`resources/js/app.js`** → creates Inertia app with Vue 3
2. Page resolution: `./Pages/{name}.vue` via `import.meta.glob`
3. CSRF token synced on every Inertia navigation + 419 (session expired) auto-reload
4. **Layouts** (5): `MainLayout`, `AdminLayout`, `OwnerLayout`, `CourierLayout`, `OnboardingLayout`

---

## 5. User Roles & Access Control

| Role | Description | Middleware Guard |
|------|-------------|-----------------|
| `customer` | Default role — browses, buys, tracks orders | `auth`, `verified` |
| `distributor` | Shop owner — manages products, inventory, orders, staff, finances | `role:distributor`, `EnsureDistributorVerified` |
| `staff` | Employee of a distributor — shared shop operations | `role:distributor,staff`, `EnsureDistributorVerified` |
| `courier` | Delivery fleet member — scans, picks up, delivers | `role:courier` |
| `admin` | Platform moderator — user/distributor management, reports | `role:admin,super_admin`, `otp` |
| `super_admin` | Full platform control — settings, admin invitations, courier governance | `role:super_admin`, `otp` |

**Key middleware flow:** `auth` → `verified` (email) → `role:X` → `otp` (admin only) → `EnsureDistributorVerified` (distributor only) → `EnsureNotBanned` (global)

---

## 6. Domain Models (34 total)

### Core Domain
| Model | Responsibility |
|-------|---------------|
| `User` | Multi-role user, soft deletes, OTP, username auto-generation, terms acceptance |
| `Distributor` | Business profile, verification docs, suspension, DSS settings, payout info |
| `Product` | Catalog item with variations, prescriptions, VAT, barcode, popularity scoring |
| `ProductVariation` | SKU-level options (e.g., size, color) |
| `Inventory` | Stock quantities per product/variation per branch |
| `Category` | Hierarchical product taxonomy (parent/child/slug) |
| `Branch` | Physical location of a distributor |
| `License` | Business licenses with expiry tracking |

### Order & Payment
| Model | Responsibility |
|-------|---------------|
| `Order` | Full order lifecycle (pending→approved→packed→shipped→delivered→completed) |
| `OrderItem` | Line items with variation support |
| `Invoice` | Financial document per order |
| `Payment` | Escrow-based payments (held→released→refunded), PayMongo integration |
| `CheckoutBatch` | Multi-order checkout grouping |
| `Delivery` | Shipping lifecycle, COD remittance, proof of delivery, failure tracking |
| `Courier` | Delivery personnel profile |
| `WithdrawalRequest` | Distributor payout requests |

### Communication & Moderation
| Model | Responsibility |
|-------|---------------|
| `Conversation` | Shop-level buyer↔seller thread |
| `ConversationMessage` | Messages within conversations (also used for order chat) |
| `ConversationMessageReport` | Flagged messages for admin review |
| `ProductReview` / `DeliveryReview` | Star ratings with dispute system |
| `ProductReport` / `UserReport` / `CourierReport` | Moderation reports |
| `AuditLog` | Admin action audit trail |

### DSS (Decision Support System)
| Model | Responsibility |
|-------|---------------|
| `DssDistributorSettings` | Per-shop thresholds (low stock, expiry warning, dead stock) |
| `DssAlert` | Generated alerts (low stock, near-expiry, dead stock) |
| `DssSalesAnalytics` | Aggregated sales data per product |
| `DssReorderRecommendation` | Automated reorder suggestions |

### Other
| Model | Responsibility |
|-------|---------------|
| `AdminInvitation` | Token-based admin account setup |
| `CustomerAddress` | Saved delivery addresses |
| `CustomerDiscountId` | Senior/PWD discount card management |
| `SystemSetting` | Global platform configuration (e.g., fee rates) |

---

## 7. Core Features & Capabilities

### Customer-Facing
- **Product catalog** with search, category filtering, popularity sorting
- **Product detail** with images, variations, reviews, prescription requirements
- **Cart** (session-based, no auth required for browsing)
- **Multi-order checkout** (batch checkout across multiple distributors)
- **Payment** via PayMongo (GCash, PayMaya, Card) or COD
- **Prescription upload** with OCR scanning (`tesseract.js`) and seller review workflow
- **Senior/PWD discount** with ID verification workflow
- **Order tracking** with timeline, real-time chat with seller
- **Delivery tracking** with map display (Leaflet)
- **Product & delivery reviews** with star ratings
- **Notifications** (database + email channels, polling-based)
- **Shop messaging** (buyer↔seller conversations)
- **Seller profile pages** (public storefront with rating, products)

### Distributor/Owner-Facing
- **Dashboard** with analytics (revenue, orders, top products, charts via Chart.js)
- **Inventory management** (unified product + stock, variations, barcodes, batch/lot tracking)
- **Point of Sale (POS)** for in-store transactions
- **Order management** (approve, pack, ship, prescription review, discount review)
- **Staff management** (add/remove staff accounts)
- **Shop profile** (logo, cover photo, description, business hours, social links, geolocation)
- **Branch management**
- **Sales reports** with date range filtering
- **DSS insights** (low stock alerts, expiry warnings, reorder recommendations, dead stock detection)
- **Chat with customers** (auto-reply templates, order status templates)
- **Financial overview** (payments, escrow status, available payout balance)
- **Review disputes**

### Courier-Facing
- **Courier dashboard** (active/completed deliveries)
- **Barcode/QR scanner** for order verification
- **Delivery lifecycle** (accept → start pickup → confirm scan → confirm pickup → in transit → delivered)
- **COD remittance tracking**
- **Delivery failure reporting** with proof of attempt

### Admin/Super Admin
- **Distributor verification** (document review, approve/reject)
- **User management** (role changes, ban/unban)
- **Moderation report hub** (message reports, user reports, courier reports, low-rating deliveries, product reports)
- **Product moderation** (deactivate, soft-delete)
- **Order overview** (system-wide order monitoring)
- **Review dispute resolution**
- **Courier management** (create courier accounts)
- **Audit logs**
- **System-wide announcements**
- **Global settings** (platform fee rate, etc.)
- **Storage repair** utility (Hostinger symlink fixes)
- **OTP-gated access** for additional security

---

## 8. Critical End-to-End Data Flow: Order Lifecycle

```
Customer browses /products → adds to cart (session) → /checkout
     │
     ▼
OrderController@placeOrder
     ├── Validates cart, addresses, payment method
     ├── Creates Order (status: pending) + OrderItems
     ├── Reserves inventory (Inventory.reserved_quantity += qty)
     ├── Creates Invoice
     ├── If online payment → PayMongoService.createCheckoutSession()
     │     ├── Redirects to PayMongo hosted checkout
     │     └── On success/webhook → Payment created, escrow = 'held'
     ├── If COD → No payment record yet
     ├── If prescription required → status: awaiting_upload
     ├── Sends OrderNotification to distributor
     └── Returns confirmation page
     
Distributor sees order in /owner/orders
     │
     ▼
Owner\OrderController@updateStatus
     ├── pending → approved (+ auto-chat message)
     ├── approved → packed (+ packaging photos)
     ├── packed → shipped (creates Delivery record)
     │     └── Courier assigned, delivery lifecycle begins
     ├── OR packed → ready_for_pickup (for pickup orders)
     └── Each transition triggers OrderNotification to customer
     
Courier picks up & delivers
     │
     ▼
Courier\DeliveryController
     ├── accept → startPickup → confirmScan → confirmPickup → in_transit
     ├── confirmDelivery (with proof photo, signature, coordinates)
     │     ├── Order.status = 'delivered'
     │     ├── If COD → marks cod_collected_at
     │     └── Sends notification to customer & distributor
     └── OR reportFailure (with proof of attempt)
     
Customer confirms receipt
     │
     ▼
OrderController@confirmReceived
     ├── Order.status = 'completed', received_at = now()
     ├── Releases inventory reservation
     ├── Payment.releaseEscrow() → escrow_status = 'released'
     ├── Reconciles Invoice status
     └── Seller's available_payout_balance increases
```

---

## 9. Service Layer (17 Services)

| Service | Responsibility |
|---------|---------------|
| `PayMongoService` | PayMongo API integration (checkout sessions, webhooks, refunds) |
| `CartService` | Session-based cart operations with stock validation |
| `DssEngineService` | Inventory intelligence engine (alerts, recommendations, analytics sync) |
| `DssAlertService` | Alert generation and management |
| `DashboardAnalyticsService` | Revenue, order, and product analytics aggregation (684 lines) |
| `OrderInvoiceService` | Invoice generation and management |
| `OrderChatAutomationService` | Automated chat messages on order status changes |
| `OrderPrescriptionRefundService` | Prescription rejection → refund workflow |
| `ProductCatalogSyncService` | Product catalog synchronization |
| `ContentModerationService` | Profanity filtering (blocked/censored word lists) |
| `AdminModerationService` | Admin moderation actions (warnings, suspensions, bans) |
| `CustomerReliabilityService` | COD eligibility based on rejection history |
| `AutomatedPayoutService` | Seller payout automation |
| `ChatMessageNotifier` | Notification dispatch for chat messages |
| `PrescriptionChatService` | Prescription-related chat automation |
| `ShopConversationAutoReplyService` | Auto-reply for new shop conversations |
| `UnreadConversationMessageService` | Unread message count aggregation |

---

## 10. State Management & Real-Time Updates

### Backend State
- **Sessions:** Database driver (configurable)
- **Cache:** Database driver (configurable)
- **Queue:** Database driver — used for jobs, scheduled via `queue:work`
- **No Redis** in default config (available if needed)

### Frontend State
- **Inertia.js props** — all page data is passed server-side via controller responses
- **Shared props** via `HandleInertiaRequests` middleware:
  - `auth.user` (current user + role + distributor status)
  - `csrf_token`, `flash` messages
  - `unread_notifications_count`, `unread_chat_messages_count`
  - `open_reports_hub_count`, `pending_verifications_count` (admin)
  - `needsTermsAcceptance`

### Real-Time (Polling-Based)
- **Chat:** 12s polling interval
- **Notifications:** 45s polling interval
- **Dashboard:** 30s pulse endpoint
- **Cart count:** 10s polling

> ⚠️ **WebSockets not yet implemented.** Config file documents upgrade path: Laravel Reverb + Echo. Estimated effort: 4-8 hours.

---

## 11. Conventions & Patterns

### Coding Style
- **PHP:** PSR-12 / Laravel preset, enforced via `laravel/pint`
- **Naming:** PascalCase models, camelCase methods, snake_case DB columns
- **Controllers:** Resource-style + action methods; large controllers (OrderController: 42KB, InventoryController: 31KB)
- **Services:** Fat service layer for business logic; controllers delegate to services
- **Models:** Rich models with accessors, mutators, scopes, and business methods

### Error Handling
- **Flash messages** via Inertia session (`success`, `error`, `info`, `warning`)
- **Form validation:** Laravel Form Requests + inline `$request->validate()`
- **PayMongo errors:** Caught and logged with `Log::error`, re-thrown as `RuntimeException`
- **No global exception handler customization** (empty `withExceptions` in bootstrap)

### Logging
- **Channel:** Stack → Single file (`storage/logs/laravel.log`)
- **Convention:** `[ServiceName] Action description` (e.g., `[PayMongoService] Checkout session created`)
- **Mail logging:** Default to `log` mailer in dev (writes to laravel.log)

### API Response Structure
- **No REST API** — all responses are Inertia page renders or redirects with flash messages
- **AJAX endpoints:** JSON responses for polling (notifications, cart count, messages)

### Authentication & Authorization
- **Auth:** Laravel Breeze (session-based, email verification required)
- **RBAC:** Custom `RoleMiddleware` with role hierarchy (super_admin bypasses all)
- **Policies:** 3 policies for fine-grained authorization (Conversation, Delivery, Order)
- **OTP:** Email-based one-time password for admin access
- **Admin onboarding:** Invitation-based setup via `AdminInvitation` tokens

---

## 12. Testing

### Configuration
- **Framework:** PHPUnit 11.5
- **Database:** SQLite `:memory:` for tests
- **Queue:** Synchronous in tests
- **Bootstrap:** `tests/bootstrap.php`

### Test Coverage (Current)

| Suite | Files | Description |
|-------|-------|-------------|
| **Feature** | 7 tests | Order flow, chat, notifications, profile, report hub, shop conversations |
| **Unit** | 1 test | Example only |
| **Feature/Auth** | (Breeze defaults) | Registration, login, password reset |

### Running Tests
```bash
composer test
# or
php artisan test
```

> ⚠️ **Test coverage is minimal.** Critical paths like payment processing, DSS engine, inventory management, courier delivery flow, and admin moderation have no dedicated tests.

---

## 13. Environment & Local Development

### Prerequisites
- PHP 8.2+, Composer, Node.js + npm, XAMPP (or equivalent)
- SQLite (default) or MySQL

### Setup
```bash
composer setup          # install deps, copy .env, generate key, migrate, npm install + build
composer dev            # starts PHP server + queue worker + log watcher + Vite dev server (concurrent)
composer dev-account    # seeds test accounts
```

### Key Environment Variables
| Variable | Purpose | Default |
|----------|---------|---------|
| `DB_CONNECTION` | Database driver | `sqlite` |
| `QUEUE_CONNECTION` | Queue backend | `database` |
| `SESSION_DRIVER` | Session storage | `database` |
| `CACHE_STORE` | Cache backend | `database` |
| `MAIL_MAILER` | Mail transport | `log` (dev) |
| `PAYMONGO_SECRET_KEY` | PayMongo API key | — (required for payments) |
| `PAYMONGO_PUBLIC_KEY` | PayMongo public key | — |
| `PAYMONGO_WEBHOOK_SECRET` | Webhook signature verification | — |
| `CHAT_NOTIFY_MAIL` | Email on new chat messages | `false` |
| `COD_REJECTION_THRESHOLD_PERCENT` | COD disable threshold | `15` |
| `BROADCASTING_ENABLED` | WebSocket broadcasting | `false` |

### Production Notes (Hostinger)
- Web root must be Laravel's `public/` folder
- Storage symlink required (`php artisan storage:link`)
- `PublicStorageUrl` helper handles URL generation resilience
- SMTP configured via Hostinger's `smtp.hostinger.com`
- Admin has a "Repair Storage" utility for symlink issues

---

## 14. Database Schema Highlights

- **115 migrations** reflecting significant iterative development
- **Schema uses SQLite-compatible patterns** (careful with ENUM changes via migrations)
- **Soft deletes** on `users` and `products`
- **Escrow system** tracked on `payments` table (held → released → refunded)
- **COD tracking** on `deliveries` table (cod_collected_at, cod_remittance_pending, etc.)
- **Geolocation** (latitude/longitude) on distributors, deliveries, customer addresses
- **Document expiry tracking** on distributors (6 document types with expiry dates)
- **VAT compliance** fields on orders (vatable_sales, vat_amount, vat_exempt_sales)

---

## 15. Technical Debt & Observations

### High Priority

1. **Giant controllers:** `OrderController.php` (42KB), `InventoryController.php` (31KB), `ReportHubController.php` (35KB) need refactoring into smaller, focused controllers or moving logic to services.

2. **Minimal test coverage:** Only 7 feature tests for a complex multi-role marketplace. Payment flow, DSS, courier lifecycle, admin moderation, and inventory management are untested.

3. **Polling instead of WebSockets:** 4 separate polling loops (12s/45s/30s/10s) create unnecessary server load. Documented upgrade path to Laravel Reverb exists but is not implemented.

4. **115 migration files:** Many are incremental column additions. Consider squashing migrations for cleaner schema management.

5. **Loose scripts in project root:** `fix-modal.js`, `fix_pickup.php`, `fix_status.php`, `patch_backend.php`, `reset_admin.php` are ad-hoc fix scripts that should be removed or converted to proper Artisan commands.

### Medium Priority

6. **Order number generation uses `rand()`:** `Order::generateOrderNumber()` generates order numbers with `rand(1, 9999)` — collision-prone under high concurrency. Should use database sequences or UUIDs.

7. **N+1 query risk in `HandleInertiaRequests`:** Shared props compute counts (unread messages, open reports, pending verifications) on every request. These should be cached or batch-computed.

8. **Distributor `rating` accessor:** `getRatingAttribute()` eagerly loads all products with review averages on every access — expensive and uncached.

9. **No API versioning or REST API:** All interaction is Inertia-based. Mobile app support would require a separate API layer.

10. **Mixed concerns in `web.php`:** 503-line route file with inline closures. Consider splitting into route files per domain (admin, owner, courier, customer).

### Low Priority

11. **Alpine.js included but likely unused:** Breeze residual; Inertia + Vue handles all interactivity.

12. **`config/cavite.php` is 24KB:** Appears to be a geographic data file — should be moved to a database seeder or JSON resource file.

13. **Profanity word list is minimal:** Production deployment needs a more comprehensive blocked word list.

14. **No rate limiting on some sensitive endpoints:** While some routes have `throttle:60,1`, others (order placement, payment) lack rate limiting.

15. **No CI/CD configuration visible:** No GitHub Actions, GitLab CI, or deployment pipeline files found.

---

## 16. Key File Quick Reference

| What | Path |
|------|------|
| App bootstrap | [`bootstrap/app.php`](file:///c:/xampp/htdocs/medequip/bootstrap/app.php) |
| Web routes | [`routes/web.php`](file:///c:/xampp/htdocs/medequip/routes/web.php) |
| Auth routes | [`routes/auth.php`](file:///c:/xampp/htdocs/medequip/routes/auth.php) |
| Inertia middleware | [`app/Http/Middleware/HandleInertiaRequests.php`](file:///c:/xampp/htdocs/medequip/app/Http/Middleware/HandleInertiaRequests.php) |
| Role middleware | [`app/Http/Middleware/RoleMiddleware.php`](file:///c:/xampp/htdocs/medequip/app/Http/Middleware/RoleMiddleware.php) |
| Vue entry point | [`resources/js/app.js`](file:///c:/xampp/htdocs/medequip/resources/js/app.js) |
| App config | [`config/medequip.php`](file:///c:/xampp/htdocs/medequip/config/medequip.php) |
| Vite config | [`vite.config.js`](file:///c:/xampp/htdocs/medequip/vite.config.js) |
| PayMongo service | [`app/Services/PayMongoService.php`](file:///c:/xampp/htdocs/medequip/app/Services/PayMongoService.php) |
| DSS engine | [`app/Services/DssEngineService.php`](file:///c:/xampp/htdocs/medequip/app/Services/DssEngineService.php) |
| Dashboard analytics | [`app/Services/DashboardAnalyticsService.php`](file:///c:/xampp/htdocs/medequip/app/Services/DashboardAnalyticsService.php) |
| PHPUnit config | [`phpunit.xml`](file:///c:/xampp/htdocs/medequip/phpunit.xml) |
| Environment template | [`.env.example`](file:///c:/xampp/htdocs/medequip/.env.example) |
