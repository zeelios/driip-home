# Driip Backend Development Plan (DDD Architecture)

**Status**: Plan APPROVED — Ready for Phase 1 coding
**Last Updated**: 2026-03-21 (Approved with gap analysis updates)

## Recent Updates (Approved Gap Analysis)

✅ **10 New Features Added to Plan**:
1. **Tax & VAT Domain** — VAT configs, tax invoices, proper order totaling
2. **Notification Domain** — Email templates, queue jobs (SMS/Zalo deferred)
3. **Sale Events / Flash Sales** — Time-based drops & pricing overrides (driip core feature)
4. **Waitlist Domain** — Customer notification on stock replenishment
5. **Document Generation** — Packing slips, shipping labels, tax invoices
6. **Webhook Security** — HMAC signature verification, idempotency (Redis)
7. **Bulk Operations** — Confirm/ship/cancel multiple orders at once
8. **Rate Limiting** — Middleware config per route group
9. **Meilisearch Indexes** — ProductVariant, Customer, Order search mapping
10. **Settings Domain** — Business rule configuration (points/VND, tax rate, etc.)

✅ **Phases Reordered** (optimized for launch):
- Phase 1: Add API versioning, rate limiting, webhook security, tax/VAT schema
- Phase 2: Moved Sales Events & Notifications UP (from later) — required for launch
- Phase 3-8: Restructured to reflect new domains

✅ **Deferred (post-launch)**:
- SMS, Zalo OA notifications (channel infrastructure ready, implementation later)
- Abandoned checkout tracking, Customer OTP login, Advanced reporting

✅ **Skipped** (out of scope):
- Multi-currency, Gift cards, POS, Marketplace, Multi-language API

---

## PART 0: CODING STANDARDS (Apply to every file)

### API Response Pattern (STRICT — no exceptions)

Every controller action returns either a typed API Resource or an ErrorResource. No plain arrays,
no `response()->json()` calls directly in controllers.

#### Success Response
```php
// Single resource
return StaffResource::make($user);

// Paginated collection
return StaffResource::collection($users->paginate(20));

// No content (delete/bulk)
return response()->noContent();
```

Shape (single):
```json
{ "data": { ...model fields... } }
```
Shape (collection):
```json
{ "data": [...], "links": {...}, "meta": { "total": 100, "per_page": 20 } }
```

#### Error Response — ErrorResource with request_code
Every `catch` block and every validation failure returns `ErrorResource` with a unique
`request_code` in the format `ACTION_NAME_XXXXXXXX` where `XXXXXXXX` is 8 uppercase hex chars.

```php
// In controller
try {
    $result = $this->createStaffAction->execute($dto);
    return StaffResource::make($result);
} catch (ValidationException $e) {
    return ErrorResource::fromException($e, 'CREATE_STAFF')->response()->setStatusCode(422);
} catch (\Throwable $e) {
    return ErrorResource::fromException($e, 'CREATE_STAFF')->response()->setStatusCode(500);
}
```

Error shape:
```json
{
    "success": false,
    "request_code": "CREATE_STAFF_A1B2C3D4",
    "message": "Human-readable error message",
    "errors": { "field": ["Validation error detail"] }
}
```

**request_code purpose**: Staff logs `request_code`, you search it in logs to find the exact
request payload, user, timestamp, and trace. Every failed request is uniquely addressable.

### Doc-Block Standard (REQUIRED on every class, method, property)
```php
/**
 * Create a new staff member with profile and initial salary record.
 *
 * @param  CreateStaffDto  $dto
 * @return User
 *
 * @throws StaffEmailAlreadyExistsException
 * @throws \Throwable
 */
public function execute(CreateStaffDto $dto): User
```

### File Size Rule
- Actions: max 80 lines
- Services: max 150 lines
- Controllers: max 60 lines (thin — delegate to Actions)
- Models: max 150 lines
- Migrations: no limit

### Action Naming Convention
All request_code action names follow this pattern (used in ErrorResource):
```
AUTH_LOGIN, AUTH_LOGOUT
LIST_STAFF, CREATE_STAFF, SHOW_STAFF, UPDATE_STAFF, DELETE_STAFF
LIST_CUSTOMERS, CREATE_CUSTOMER, ...
LIST_ORDERS, CREATE_ORDER, CONFIRM_ORDER, PACK_ORDER, SHIP_ORDER, CANCEL_ORDER
LIST_INVENTORY, ADJUST_INVENTORY, RESERVE_STOCK, RELEASE_STOCK
CREATE_SHIPMENT, TRACK_SHIPMENT, WEBHOOK_GHN, WEBHOOK_GHTK
GENERATE_PACKING_SLIP, GENERATE_TAX_INVOICE
...
```

### Test Standard (Feature Tests for every route)
```php
// tests/Feature/Api/V1/Staff/ListStaffTest.php
class ListStaffTest extends TestCase {
    use RefreshDatabase;

    public function test_admin_can_list_staff(): void { ... }
    public function test_returns_paginated_results(): void { ... }
    public function test_unauthenticated_gets_401(): void { ... }
    public function test_unauthorized_role_gets_403(): void { ... }
}
```

---

## PART 1: ARCHITECTURE & TECH STACK

### Core Decisions
- [ ] Framework: Laravel 12 (confirmed)
- [ ] Database: PostgreSQL (confirmed)
- [ ] Auth: Sanctum + Spatie Permission (confirmed)
- [ ] Architecture: Domain-Driven Design (DDD) (confirmed)
- [ ] Media Storage: Backblaze B2 (S3-compatible, no local files) (confirmed)
- [ ] Development: Docker containers (confirmed)
- [ ] File Organization: Small domain modules, thin controllers, fat domain logic
  - [ ] app/Domain/{DomainName}/ structure finalized
  - [ ] Decide: Actions, Services, Repositories, ValueObjects, Events, DTOs per domain
  - [ ] Max file size target: ~150-200 lines per file

### Packages to Install
- [ ] `laravel/sanctum` — API authentication
- [ ] `spatie/laravel-permission` — Role-based access control
- [ ] `spatie/laravel-activitylog` — Complete audit trail
- [ ] `spatie/laravel-query-builder` — Filterable API responses
- [ ] `laravel/queue` — Async jobs (COD remittance reconciliation, shipment tracking)
- [ ] `aws/aws-sdk-php` — B2 S3-compatible API
- [ ] `laravel/framework` queue drivers (database initially, Redis later)

### Environment & Secrets
- [ ] PostgreSQL connection string (.env)
- [ ] B2 credentials (.env)
- [ ] Courier API keys (GHTK, GHN, SPX, Viettel) (.env)
- [ ] Sanctum session domain (.env)
- [ ] Redis password (.env)
- [ ] Meilisearch API key (.env)

---

## PART 1.5: DOCKER SETUP (Local Development)

### Docker Containers (docker-compose.yml)
- [ ] **Laravel App** (PHP 8.3 + Composer)
  - Port: 9000 (PHP-FPM)
  - Volume: `/app` → host `/backend`
  - Depends on: PostgreSQL, Redis

- [ ] **PostgreSQL 16** (Database)
  - Port: 5432
  - Volume: `postgres_data:/var/lib/postgresql/data` (persist)
  - User: `driip`
  - Password: (from .env)
  - Database: `driip_dev`

- [ ] **Redis 7** (Cache + Queue Driver)
  - Port: 6379
  - Volume: `redis_data:/data` (persist)
  - Used for: Sessions, Cache, Queue jobs, Rate limiting

- [ ] **Meilisearch 1.x** (Full-text Search)
  - Port: 7700
  - Volume: `meilisearch_data:/meili_data` (persist)
  - Used for: Product search, customer search, advanced filtering
  - API Key: (from .env)

- [ ] **Nginx** (Reverse Proxy / Web Server)
  - Port: 80 → `localhost:80` or `:8080`
  - Config: `docker/nginx/nginx.conf`
  - Proxies to PHP-FPM on `:9000`
  - Serves static files

- [ ] **MailHog** (Local Email Testing)
  - Port: 1025 (SMTP)
  - Port: 8025 (Web UI)
  - Used for: Capture order confirmations, password resets, etc.
  - View emails at `http://localhost:8025`

- [ ] **Minio** (Local S3 for B2 Testing)
  - Port: 9000 (API)
  - Port: 9001 (Console)
  - Volume: `minio_data:/data` (persist)
  - Used for: Test B2 upload/download without hitting real B2

### Docker Commands
- [ ] `docker-compose up -d` — start all services
- [ ] `docker-compose down` — stop all services
- [ ] `docker-compose logs -f app` — watch Laravel logs
- [ ] `docker exec driip-app php artisan migrate` — run migrations
- [ ] `docker exec driip-app php artisan seed` — seed database

### Files to Create
- [ ] `Dockerfile` — Laravel PHP-FPM image
- [ ] `docker-compose.yml` — all services
- [ ] `.dockerignore` — exclude node_modules, .git, etc.
- [ ] `docker/nginx/nginx.conf` — Nginx config
- [ ] `docker/php/Dockerfile` — custom PHP image (optional, for extra extensions)
- [ ] `.env.docker` — docker-specific env vars

### .env Variables (Docker)
```
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=driip_dev
DB_USERNAME=driip
DB_PASSWORD=...

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=...

QUEUE_CONNECTION=redis (not database)
CACHE_DRIVER=redis
SESSION_DRIVER=redis

MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=...

MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=no-reply@driip.local

AWS_ENDPOINT=http://minio:9000
AWS_BUCKET=driip-dev
AWS_KEY=minioadmin
AWS_SECRET=minioadmin
```

---

## PART 2: DOMAIN DESIGN REFINEMENT

### 2.1 Product & Variants Redesign
**Current Question**: Product attribute architecture

- [ ] **DECISION: Attribute Storage Method**
  - [ ] Option A: JSON array on variant (`attribute_values: {size_id: 1, color_id: 2}`)
    - Pros: Simpler, fewer tables, faster for small attribute counts
    - Cons: Harder to query "all black items"
  - [ ] Option B: Separate pivot table (`product_variant_attribute_values`)
    - Pros: Queryable, flexible, can add more attributes per product
    - Cons: More tables, slightly more complex
  - [ ] **Decision**: ___________

- [ ] **Variant as First-Class Product**
  - [ ] Confirm: Each variant is independently sellable, has own SKU, price, cost, inventory
  - [ ] Confirm: `products` table is grouping/metadata only
  - [ ] Confirm: All orders reference `product_variant_id`, never `product_id`

- [ ] **Multi-Supplier per Variant**
  - [ ] Table: `product_variant_suppliers` (variant can be sourced from multiple suppliers)
  - [ ] Decide: How to track which supplier fulfills which warehouse? (supplier → warehouse mapping needed?)

- [ ] **Product Images & Variants**
  - [ ] Can a variant have its own images? (e.g., "Black" variant shows black product, "White" shows white)
  - [ ] Or all images tied to product level?
  - [ ] Decide: ___________

### 2.2 Order & Payment Flow
- [ ] **Payment Status vs Order Status**
  - [ ] Clarify: Unpaid COD order can be "confirmed" (processing/packed) but "payment_status" still "unpaid" until delivered + collected?
  - [ ] Confirm flow: Order Pending → Confirmed (payment_status=unpaid) → Shipped → Delivered (payment_status=unpaid) → COD collected (payment_status=paid)

- [ ] **Order Cancellation Rules**
  - [ ] Can customer cancel after confirmed? (Y/N)
  - [ ] Can staff cancel after packed? (Y/N)
  - [ ] Auto-refund? Or manual review?

- [ ] **Shipping Address Validation**
  - [ ] Use Vietnam address DB? (province/district/ward)
  - [ ] Required integration? (Google Maps, or hardcoded list?)

### 2.3 Loyalty Program Scope
- [ ] **Tier System**
  - [ ] Lifetime points threshold or spending threshold?
  - [ ] Tier expiry? (annual reset or permanent once earned?)
  - [ ] Downgrade if spending drops below minimum?

- [ ] **Earn Rules**
  - [ ] Points per VND spent? (e.g., 1 point per 10,000 VND)
  - [ ] Category multipliers? (e.g., 2x points on premium products)
  - [ ] Birthday bonus multiplier?
  - [ ] First order bonus?

- [ ] **Redeem Rules**
  - [ ] 1 point = how much discount? (e.g., 1 point = 100 VND)
  - [ ] Min points to redeem? (e.g., min 10,000 points)
  - [ ] Max points per order? (unlimited or capped?)

- [ ] **Referral Program**
  - [ ] Each customer gets a referral code?
  - [ ] Referrer gets X points when referee makes first purchase?
  - [ ] Referee gets X points as welcome bonus?

### 2.4 Inventory & Warehouse
- [ ] **Stock Reservation Flow**
  - [ ] Reserve on "order confirmed" or "payment confirmed"?
  - [ ] Release reservation if order cancelled?
  - [ ] Release if unpaid COD order not delivered after X days?

- [ ] **Multi-Warehouse Fulfillment**
  - [ ] Can orders be split across warehouses?
  - [ ] Or always fulfill from single warehouse?
  - [ ] Fulfillment logic: "pick from closest warehouse" or "pick from main warehouse"?

- [ ] **Stock Count Workflows**
  - [ ] Cycle count (partial) or full count?
  - [ ] How often? (daily, weekly, monthly?)
  - [ ] What triggers a recount? (variance threshold?)

- [ ] **Reorder Automation**
  - [ ] Auto-create PO when stock hits reorder point?
  - [ ] Or just send alert to buyer?

### 2.5 Shipping & COD Remittance
- [ ] **Courier Selection Logic**
  - [ ] Single courier per order or allow multiple?
  - [ ] Auto-select by rate/speed/availability or manual selection?
  - [ ] Rate comparison before shipment creation?

- [ ] **COD Remittance Reconciliation**
  - [ ] Which courier(s) will we use? GHTK, GHN, SPX, Viettel?
  - [ ] Which ones handle COD well? (GHTK and GHN are most popular)
  - [ ] Reconciliation frequency? (daily, weekly, per-remittance?)
  - [ ] What happens if remittance doesn't match delivered orders?

- [ ] **Failed Delivery Handling**
  - [ ] How many return attempts before giving up?
  - [ ] Auto-create return order or manual review?
  - [ ] Full refund or charge customer for return shipping?

- [ ] **Return Shipping Logistics**
  - [ ] Who pays for return shipping? (customer or us?)
  - [ ] Same courier or different?
  - [ ] Need separate reverse logistics partner?

### 2.6 Customer & CRM
- [ ] **Phone Number Uniqueness**
  - [ ] Phone as unique constraint or allow duplicates?
  - [ ] Important for COD verification - one phone = one account?
  - [ ] Guest orders with phone?

- [ ] **Customer Blocking**
  - [ ] Why block customers? (fraud, too many disputes, payment issues)
  - [ ] Reactivation process?

- [ ] **Interaction Logging**
  - [ ] Required? (call, chat, email interactions)
  - [ ] For CRM follow-up on inactive customers?

### 2.7 Staff & Permissions
- [ ] **Roles Needed**
  - [ ] List all roles: Admin, Manager, Sales, Warehouse Picker, Packer, Receiver, Customer Service, Accountant?
  - [ ] Permissions per role?

- [ ] **Staff Assignments**
  - [ ] Can staff be assigned to multiple warehouses?
  - [ ] Can orders be assigned to sales reps?
  - [ ] Performance tracking? (orders packed per day, etc?)

- [ ] **Salary Structure**
  - [ ] Monthly fixed + allowances + overtime + bonuses?
  - [ ] Overtime calculation rules?
  - [ ] Bonus triggers? (orders shipped, customer satisfaction, etc)

---

## PART 3: SCHEMA DESIGN FINALIZATION

### 3.1 Domains Overview (CONFIRM ALL)
- [ ] **Domain: Staff**
  - [ ] Tables: users, staff_profiles, salary_records
  - [ ] Models: User, StaffProfile, SalaryRecord
  - [ ] Actions: CreateStaffAction, UpdateStaffAction, ProcessPayrollAction

- [ ] **Domain: Customer**
  - [ ] Tables: customers, customer_addresses, customer_interactions
  - [ ] Models: Customer, CustomerAddress, CustomerInteraction
  - [ ] Actions: RegisterCustomerAction, UpdateCustomerAction, BlockCustomerAction

- [ ] **Domain: Product**
  - [ ] Tables: brands, categories, products, product_attributes, product_attribute_values, product_variants, product_variant_suppliers
  - [ ] Models: Product, ProductVariant, ProductAttribute, ProductAttributeValue, ProductVariantSupplier
  - [ ] Actions: CreateProductAction, CreateVariantAction, UpdateVariantAction

- [ ] **Domain: Coupon**
  - [ ] Tables: coupons, coupon_usages
  - [ ] Models: Coupon, CouponUsage
  - [ ] Actions: ValidateCouponAction, ApplyCouponAction

- [ ] **Domain: Order**
  - [ ] Tables: orders, order_items, order_status_history, order_claims, order_returns
  - [ ] Models: Order, OrderItem, OrderStatusHistory, OrderClaim, OrderReturn
  - [ ] Services: OrderStatusMachineService (state transitions)
  - [ ] Actions: CreateOrderAction, ConfirmOrderAction, PackOrderAction, CancelOrderAction, CreateClaimAction

- [ ] **Domain: Inventory**
  - [ ] Tables: inventory, inventory_transactions, suppliers, purchase_orders, purchase_order_items, purchase_order_receipts, stock_transfers, stock_transfer_items, stock_counts, stock_count_items
  - [ ] Models: Inventory, InventoryTransaction, PurchaseOrder, StockTransfer, StockCount
  - [ ] Services: StockCalculationService, ReservationService
  - [ ] Actions: ReserveStockAction, ReleaseStockAction, ReceivePOAction, CreateTransferAction, ApproveStockCountAction

- [ ] **Domain: Shipment**
  - [ ] Tables: shipments, shipment_tracking_events, courier_configs, courier_cod_remittances, courier_cod_remittance_items
  - [ ] Models: Shipment, ShipmentTrackingEvent, CourierConfig, CourierCODRemittance
  - [ ] Services: GHNService, GHTKService, SPXService, ViettelPostService
  - [ ] Actions: CreateShipmentAction, ProcessWebhookAction, ReconcileRemittanceAction

- [ ] **Domain: Warehouse**
  - [ ] Tables: warehouses, warehouse_staff
  - [ ] Models: Warehouse, WarehouseStaff
  - [ ] Actions: CreateWarehouseAction

- [ ] **Domain: Loyalty**
  - [ ] Tables: loyalty_tiers, loyalty_accounts, loyalty_transactions, loyalty_campaigns, referral_codes
  - [ ] Models: LoyaltyTier, LoyaltyAccount, LoyaltyTransaction, LoyaltyCampaign
  - [ ] Services: LoyaltyPointsService
  - [ ] Actions: EarnPointsAction, RedeemPointsAction, UpgradeTierAction

- [ ] **Domain: Media**
  - [ ] No database tables (B2 handles storage)
  - [ ] Services: B2StorageService, SignedUrlService
  - [ ] Actions: UploadImageAction, DeleteImageAction, GenerateSignedUrlAction
  - [ ] DTOs: MediaDto

- [ ] **Shared**
  - [ ] Tables: settings, activity_log (Spatie), personal_access_tokens (Sanctum), jobs, failed_jobs

### 3.2 NEW DOMAINS ADDED (Approved)

#### Domain: Tax & VAT (CRITICAL for Vietnam)
```
tax_configs
  id, name, rate DECIMAL(5,2), applies_to ENUM(all, category, product),
  applies_to_ids JSONB, effective_from DATE, effective_to DATE NULL,
  is_active, created_at

tax_invoices
  id uuid PK, order_id uuid FK → orders, invoice_number VARCHAR UNIQUE,
  invoice_type ENUM(retail, vat), buyer_name VARCHAR NULL,
  buyer_tax_code VARCHAR NULL, buyer_address TEXT NULL,
  issued_at TIMESTAMP, file_url VARCHAR NULL (PDF in B2),
  created_by uuid FK → users, created_at

orders (add columns)
  + tax_code VARCHAR NULL         -- buyer's tax ID (B2B)
  + vat_rate DECIMAL(5,2)        -- snapshot: 0.08 or 0.10
  + vat_amount BIGINT             -- computed
  + total_before_tax BIGINT        -- before VAT
  + total_after_tax BIGINT         -- after VAT (the final total)
```

**Rationale**: VAT rates changed in Vietnam (10% → 8% → 10%). Need history. Every invoice must
show VAT explicitly. All invoices and financial reports depend on this.

#### Domain: Notification (CRITICAL for order flow)
```
notification_templates
  id uuid PK, slug VARCHAR UNIQUE, name, channel ENUM(email, sms, zalo_oa),
  subject VARCHAR, body_html TEXT, variables JSONB,
  locale VARCHAR DEFAULT 'vi', is_active, created_at, updated_at

notification_logs
  id uuid PK, channel, recipient (email/phone), template_id uuid FK,
  subject, payload JSONB, status ENUM(pending, sent, failed),
  attempts INT DEFAULT 0, sent_at NULL, failed_at NULL, error TEXT NULL,
  notifiable_type VARCHAR NULL, notifiable_id uuid NULL, created_at
```

**MVP**: Email only (order_confirmed, order_shipped, order_delivered, staff alerts).
**Defer**: SMS, Zalo OA (same domain, different channel, add later).
**Architecture**: Action queues job → template renders → email sent (via MailHog in dev, SES in prod).

#### Domain: Sale Events / Drop Scheduling (CORE to driip business model)
```
sale_events
  id uuid PK, name, slug, description TEXT NULL,
  type ENUM(flash_sale, drop_launch, clearance, bundle),
  status ENUM(draft, scheduled, active, ended, cancelled),
  starts_at TIMESTAMP, ends_at TIMESTAMP NULL,
  max_orders_total INT NULL (cap orders per event),
  is_public BOOLEAN DEFAULT false, banner_url VARCHAR NULL,
  created_by uuid FK → users, created_at, updated_at

sale_event_items
  id uuid PK, sale_event_id uuid FK → sale_events,
  product_variant_id uuid FK → product_variants,
  sale_price BIGINT (override selling_price), compare_price BIGINT NULL,
  max_qty_per_customer INT NULL, max_qty_total INT NULL,
  sold_count INT DEFAULT 0, is_active BOOLEAN DEFAULT true

product_variants (add columns)
  + sale_price BIGINT NULL         -- live override if event active
  + sale_event_id uuid NULL FK → sale_events
```

**Rationale**: driip is a drop brand. Flash pricing and timed launches are core, not optional.
**Job**: `ActivateSaleEventJob` runs every minute: flips status, populates `sale_price` on variants.

#### Domain: Waitlist (High value for drops)
```
waitlist_entries
  id uuid PK, product_id uuid FK → products,
  product_variant_id uuid NULL FK → product_variants,
  customer_id uuid NULL FK → customers,
  email VARCHAR NULL, phone VARCHAR NULL,
  source VARCHAR NULL (homepage, product_page),
  notified_at TIMESTAMP NULL, created_at
```

**Job**: When sale_event activates or inventory replenished → `NotifyWaitlistJob` → email notification.

#### Domain: Documents (API only, PDF generation)
No new tables. PDFs generated on-demand or fetched from courier APIs.
- Packing slip (what's in the box — printed in warehouse)
- Shipping label (fetched from GHN/GHTK API, URL stored in `shipments.label_url`)
- Tax invoice (generated, stored in B2, URL on `tax_invoices.file_url`)

**Endpoints:**
```
GET /api/v1/orders/{id}/packing-slip          → PDF stream
GET /api/v1/shipments/{id}/label              → redirect to label URL
POST /api/v1/orders/{id}/tax-invoice          → generate & store invoice
GET /api/v1/tax-invoices/{id}                 → PDF stream or B2 redirect
```

#### Domain: Webhook Security (Middleware, no new tables)
```
courier_configs (add column)
  + webhook_secret TEXT NULL    -- HMAC secret for incoming webhook verification
```

**Middleware**: `VerifyCourierWebhookSignature.php`
- Validates HMAC-SHA256 signature header from GHN/GHTK/SPX/Viettel
- Redis idempotency key (reject duplicates within 5 min): `webhook:{courier}:{event_id}`
- Reject 401 if signature mismatch

**Rationale**: Anyone can POST fake webhooks without signature verification. Critical security hole.

### 3.2.1 Updated Schema Summary

| Domain | New Tables | Updated Tables | Purpose |
|--------|-----------|----------------|---------|
| Tax | tax_configs, tax_invoices | orders | VAT support, invoicing |
| Notification | notification_templates, notification_logs | — | Order emails, alerts |
| Sale Events | sale_events, sale_event_items, waitlist_entries | product_variants | Drops, flash sales, waitlist |
| Documents | — (API only) | shipments (label_url), orders (invoice_url) | PDF generation |
| Security | — (middleware) | courier_configs (webhook_secret) | Webhook verification |

### 3.2 Table Specifics (CONFIRM FIELDS)
- [x] **orders** — added: tax_code, vat_rate, vat_amount, total_before_tax, total_after_tax
- [x] **product_variants** — added: sale_price, sale_event_id
- [x] **courier_configs** — added: webhook_secret
- [x] **All new tables** — defined and in schema

---

## PART 4: API ENDPOINTS PLANNING

**NOTE**: All endpoints MUST use `/api/v1/` prefix from day one. This allows backward-compatible
evolution of the API as panel and other consumers depend on it. Do not use unversioned `/api/` routes.

### 4.1 Auth APIs
- [ ] `POST /api/auth/login` — staff login (Sanctum)
- [ ] `POST /api/auth/logout` — staff logout
- [ ] `GET /api/auth/me` — current user profile
- [ ] `POST /api/auth/refresh-token` — refresh access token

### 4.2 Staff CRUD
- [ ] `GET /api/staff` — list (with filters: department, status)
- [ ] `POST /api/staff` — create (requires admin)
- [ ] `GET /api/staff/{id}` — detail
- [ ] `PATCH /api/staff/{id}` — update
- [ ] `DELETE /api/staff/{id}` — soft delete (terminate)
- [ ] `POST /api/staff/{id}/salary/pay` — process salary payment

### 4.3 Customer CRUD
- [ ] `GET /api/customers` — list
- [ ] `POST /api/customers` — create
- [ ] `GET /api/customers/{id}` — detail + stats
- [ ] `PATCH /api/customers/{id}` — update
- [ ] `POST /api/customers/{id}/block` — block customer
- [ ] `GET /api/customers/{id}/orders` — customer order history
- [ ] `GET /api/customers/{id}/loyalty` — loyalty balance
- [ ] `POST /api/customers/{id}/interactions` — log CRM interaction

### 4.4 Product CRUD
- [ ] `GET /api/products` — list (with filters: brand, category, status)
- [ ] `POST /api/products` — create
- [ ] `GET /api/products/{id}` — detail + variants
- [ ] `PATCH /api/products/{id}` — update
- [ ] `POST /api/products/{id}/variants` — create variant
- [ ] `PATCH /api/products/{id}/variants/{variantId}` — update variant
- [ ] `DELETE /api/products/{id}/variants/{variantId}` — archive variant
- [ ] `GET /api/products/{id}/variants/{variantId}/inventory` — variant stock across warehouses

### 4.5 Coupon CRUD
- [ ] `GET /api/coupons` — list
- [ ] `POST /api/coupons` — create
- [ ] `PATCH /api/coupons/{id}` — update
- [ ] `DELETE /api/coupons/{id}` — deactivate
- [ ] `POST /api/coupons/validate` — validate coupon (pre-order check)

### 4.6 Order CRUD
- [ ] `GET /api/v1/orders` — list (filters: status, date range, customer)
- [ ] `POST /api/v1/orders` — create manual order
- [ ] `GET /api/v1/orders/{id}` — detail + items + timeline
- [ ] `PATCH /api/v1/orders/{id}` — update (notes, assignments)
- [ ] `POST /api/v1/orders/{id}/confirm` — confirm payment
- [ ] `POST /api/v1/orders/{id}/pack` — mark as packed
- [ ] `POST /api/v1/orders/{id}/ship` — create shipment + hand to courier
- [ ] `POST /api/v1/orders/{id}/cancel` — cancel order
- [ ] `GET /api/v1/orders/{id}/timeline` — status history
- [ ] `POST /api/v1/orders/{id}/claim` — create claim/dispute

### 4.6.5 Bulk Operations (NEW)
- [ ] `POST /api/v1/orders/bulk/confirm` — body: {order_ids: [...]}
- [ ] `POST /api/v1/orders/bulk/ship` — body: {order_ids: [...], courier_code: "ghn"}
- [ ] `POST /api/v1/orders/bulk/cancel` — body: {order_ids: [...], reason: "..."}
- [ ] `POST /api/v1/orders/bulk/export` — body: {filters: {...}} → queued job
- [ ] `GET /api/v1/inventory/export` — query: {warehouse_id?, low_stock_only?} → CSV download

### 4.6.7 Documents & Invoicing (NEW)
- [ ] `GET /api/v1/orders/{id}/packing-slip` — PDF stream
- [ ] `POST /api/v1/orders/{id}/tax-invoice` — generate & store invoice
- [ ] `GET /api/v1/tax-invoices/{id}` — PDF stream or B2 redirect
- [ ] `GET /api/v1/shipments/{id}/label` — redirect to courier label URL

### 4.7 Shipment & Tracking
- [ ] `GET /api/shipments/{id}` — shipment detail + tracking events
- [ ] `POST /api/shipments/{id}/track` — manually sync tracking with courier
- [ ] `POST /api/webhooks/ghn` — GHN webhook handler
- [ ] `POST /api/webhooks/ghtk` — GHTK webhook handler
- [ ] `POST /api/webhooks/spx` — SPX webhook handler
- [ ] `POST /api/webhooks/viettel` — Viettel webhook handler

### 4.8 Inventory Management
- [ ] `GET /api/inventory` — stock levels across all warehouses
- [ ] `GET /api/inventory/{variantId}` — stock levels for one variant
- [ ] `POST /api/inventory/reserve` — manual reservation (for non-order reserves)
- [ ] `POST /api/inventory/release` — release reservation
- [ ] `POST /api/inventory/adjust` — adjustment entry
- [ ] `GET /api/inventory/movements` — transaction history

### 4.9 Purchase Orders (Import)
- [ ] `GET /api/purchase-orders` — list
- [ ] `POST /api/purchase-orders` — create PO
- [ ] `PATCH /api/purchase-orders/{id}` — update
- [ ] `POST /api/purchase-orders/{id}/approve` — approve (trigger order to supplier)
- [ ] `POST /api/purchase-orders/{id}/receive` — receive shipment
- [ ] `POST /api/purchase-orders/{id}/receive/items` — receive partial items

### 4.10 Stock Transfers (Export)
- [ ] `GET /api/stock-transfers` — list
- [ ] `POST /api/stock-transfers` — create transfer request
- [ ] `PATCH /api/stock-transfers/{id}` — update
- [ ] `POST /api/stock-transfers/{id}/approve` — approve
- [ ] `POST /api/stock-transfers/{id}/dispatch` — dispatch (mark as sent)
- [ ] `POST /api/stock-transfers/{id}/receive` — receive at destination

### 4.11 Stock Counts (Audit)
- [ ] `GET /api/stock-counts` — list
- [ ] `POST /api/stock-counts` — create count task
- [ ] `POST /api/stock-counts/{id}/items/{itemId}/count` — log physical count
- [ ] `POST /api/stock-counts/{id}/complete` — mark count complete
- [ ] `POST /api/stock-counts/{id}/approve` — approve (auto-create adjustments)

### 4.12 Warehouse Management
- [ ] `GET /api/warehouses` — list
- [ ] `POST /api/warehouses` — create
- [ ] `PATCH /api/warehouses/{id}` — update
- [ ] `GET /api/warehouses/{id}/inventory` — stock at this warehouse
- [ ] `POST /api/warehouses/{id}/staff` — assign staff

### 4.13 Loyalty Management
- [ ] `GET /api/loyalty/tiers` — list tiers
- [ ] `POST /api/loyalty/tiers` — create tier
- [ ] `GET /api/loyalty/accounts/{customerId}` — loyalty balance
- [ ] `POST /api/loyalty/accounts/{customerId}/earn` — manual earn
- [ ] `POST /api/loyalty/accounts/{customerId}/redeem` — redeem points
- [ ] `GET /api/loyalty/campaigns` — active campaigns
- [ ] `POST /api/loyalty/campaigns` — create campaign

### 4.14 COD Remittance
- [ ] `GET /api/courier-remittances` — list remittances
- [ ] `GET /api/courier-remittances/{id}` — detail + items
- [ ] `POST /api/courier-remittances/{id}/reconcile` — reconcile (match vs delivered)
- [ ] `POST /api/courier-remittances/{id}/confirm` — mark as received

### 4.15 Reports & Analytics (Phase 2)
- [ ] Dashboard KPIs (revenue, orders, AOV, conversion)
- [ ] Inventory reports (low stock, overstock, aging)
- [ ] Staff performance (orders processed, packing speed)
- [ ] Courier performance (delivery rate, cost per order)
- [ ] Customer analytics (repeat rate, LTV, churn)

---

## PART 5: FILE STRUCTURE (DDD Layout)

```
app/
├── Domain/
│   ├── Staff/
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   └── SalaryRecord.php
│   │   ├── Actions/
│   │   │   ├── CreateStaffAction.php
│   │   │   └── ProcessPayrollAction.php
│   │   ├── Repositories/
│   │   │   └── StaffRepository.php
│   │   ├── Services/
│   │   │   └── PayrollService.php
│   │   ├── Events/
│   │   │   └── StaffCreatedEvent.php
│   │   ├── Exceptions/
│   │   │   └── StaffNotFoundException.php
│   │   └── Data/
│   │       └── CreateStaffDto.php
│   ├── Customer/
│   │   ├── Models/ ...
│   │   ├── Actions/ ...
│   │   └── ...
│   ├── Product/
│   │   ├── Models/ ...
│   │   ├── Actions/ ...
│   │   └── ...
│   ├── Order/
│   │   ├── Models/ ...
│   │   ├── Actions/ ...
│   │   ├── Services/
│   │   │   └── OrderStatusMachineService.php
│   │   └── ...
│   ├── Inventory/
│   │   ├── Models/ ...
│   │   ├── Actions/ ...
│   │   ├── Services/
│   │   │   ├── StockCalculationService.php
│   │   │   └── ReservationService.php
│   │   └── ...
│   ├── Shipment/
│   │   ├── Models/ ...
│   │   ├── Actions/ ...
│   │   ├── Services/
│   │   │   ├── GHNService.php
│   │   │   ├── GHTKService.php
│   │   │   ├── SPXService.php
│   │   │   └── ViettelPostService.php
│   │   └── ...
│   ├── Warehouse/
│   │   ├── Models/ ...
│   │   └── ...
│   ├── Loyalty/
│   │   ├── Models/ ...
│   │   ├── Actions/ ...
│   │   ├── Services/
│   │   │   └── LoyaltyPointsService.php
│   │   └── ...
│   └── Media/
│       ├── Services/
│       │   ├── B2StorageService.php
│       │   └── SignedUrlService.php
│       ├── Actions/
│       │   ├── UploadImageAction.php
│       │   ├── DeleteImageAction.php
│       │   └── GenerateSignedUrlAction.php
│       └── Data/
│           └── MediaDto.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── StaffController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── OrderController.php
│   │   │   ├── ProductController.php
│   │   │   ├── InventoryController.php
│   │   │   ├── ShipmentController.php
│   │   │   └── ...
│   │   └── WebhookController.php
│   ├── Requests/ (Form validation)
│   ├── Resources/ (JSON responses)
│   └── Middleware/
├── Jobs/ (Async tasks)
│   ├── SyncShipmentTrackingJob.php
│   ├── ReconcileCODRemittanceJob.php
│   └── ...
├── Events/ (Domain events)
├── Listeners/ (Event handlers)
├── Exceptions/ (Custom exceptions)
└── Console/ (CLI commands)

database/
├── migrations/
│   ├── 0001_create_users_table.php
│   ├── 0002_create_staff_profiles_table.php
│   ├── 0003_create_customers_table.php
│   ├── ...
│   └── 9999_create_activity_log_table.php
└── seeders/

routes/
├── api.php (main API routes)
└── webhooks.php (courier webhooks)

config/
├── media.php (B2 config)
├── courier.php (API keys)
└── loyalty.php (tier/points config)
```

---

## PART 6: IMPLEMENTATION PHASES

### Phase 1: Foundation & Core Schema (WEEK 1)
**Add to plan**: API versioning, Rate limiting, Webhook security, Tax/VAT domain
- [ ] Laravel 12 scaffold with all packages
- [ ] **API versioning**: All routes use `/api/v1/` prefix
- [ ] **Rate limiting config**: Auth 5/min, Admin 120/min, Webhook unlimited (signature-verified)
- [ ] PostgreSQL schema: **all migrations including Tax, Notification, Sale Events, Waitlist**
- [ ] Base Models with relationships
- [ ] Sanctum auth setup + Spatie Permission
- [ ] B2 Storage service
- [ ] **Webhook security middleware**: `VerifyCourierWebhookSignature.php`
- [ ] Staff domain (users, salary, auth)
- [ ] Staff CRUD API endpoints (`/api/v1/staff`)
- [ ] Settings domain (define all configuration keys)
- [ ] Test with Postman/Insomnia

### Phase 2: Core Commerce & Notifications (WEEK 2)
**Moved up**: Sale Events, Notifications (no longer phases 3 & 5)
- [ ] Product domain (products, variants, attributes)
- [ ] **Sale Events domain** (flash_sales, drops) ← MOVED from Phase 5
- [ ] **Notification domain** (email templates, queue, jobs) ← MOVED from Phase 5
- [ ] **Document generation setup** (packing slip, label URLs, invoice structure)
- [ ] Customer domain (customers, addresses, CRM interactions)
- [ ] Order domain (orders, items, status machine, claims, returns)
- [ ] Coupon domain
- [ ] Product/Customer/Order CRUD APIs (`/api/v1/products`, `/api/v1/customers`, `/api/v1/orders`)
- [ ] **Bulk order operations** (`/api/v1/orders/bulk/confirm`, `/bulk/ship`, etc.)
- [ ] Order creation flow + stock reservation
- [ ] **Email notification jobs**: order_confirmed, order_shipped, order_delivered
- [ ] **Meilisearch indexing** (ProductVariant, Customer, Order models with Searchable trait)

### Phase 3: Inventory & Tax (WEEK 3)
**Add Tax domain**: Complete VAT support
- [ ] Inventory domain (stock levels, transactions)
- [ ] **Tax & VAT domain** (configs, invoices, tax calculations)
- [ ] Purchase order domain
- [ ] Stock transfer domain
- [ ] Stock count domain
- [ ] All inventory APIs
- [ ] Stock reservation/release integration with orders
- [ ] **Tax invoice generation** on order completion
- [ ] **Stock alert notification** job (low inventory → email to managers)

### Phase 4: Shipping & Documents (WEEK 4)
**Add Document generation**: PDFs, labels, invoices
- [ ] Shipment domain
- [ ] Courier service implementations (GHN, GHTK)
- [ ] **Webhook signature verification** ← critical security
- [ ] Courier webhook handlers (with idempotency via Redis)
- [ ] COD remittance domain & reconciliation
- [ ] Shipment APIs
- [ ] **Packing slip PDF** generation endpoint
- [ ] **Shipping label** URL retrieval from courier API
- [ ] **Tax invoice PDF** generation & B2 storage
- [ ] (Defer: SPX + Viettel to month 1 post-launch)

### Phase 5: Loyalty & Waitlist (WEEK 5)
**Add Waitlist domain**: Notification on stock replenishment
- [ ] Loyalty domain (tiers, accounts, transactions, campaigns)
- [ ] **Waitlist domain** (entries, notification job) ← NEW
- [ ] Points earning/redemption logic
- [ ] Loyalty APIs
- [ ] Referral program
- [ ] **Waitlist notification job** (notify on sale event activation)

### Phase 6: Panel Frontend Integration (WEEK 6)
- [ ] Nuxt panel app connects to backend APIs (`/api/v1/...`)
- [ ] Dashboard with KPIs (today's revenue, orders by status, low stock alerts)
- [ ] Order management UI (**bulk operations**: confirm, ship, cancel)
- [ ] Inventory management UI (**exports**: CSV download)
- [ ] Staff management UI
- [ ] **Basic reporting endpoints** (orders, inventory, sales by date range)

### Phase 7: Testing & Optimization (WEEK 7)
- [ ] Unit tests (Actions, Services, ValueObjects)
- [ ] Integration tests (API endpoints)
- [ ] Database tests (with transactions/rollback)
- [ ] Feature tests (full order flow end-to-end)
- [ ] Performance testing (database queries, N+1 problems, indexes)
- [ ] Load testing (concurrent orders, inventory updates)
- [ ] Test coverage target: 80%+

### Phase 8: Deployment & Launch (WEEK 8)
- [ ] Environment setup (staging, production)
- [ ] Database backup strategy
- [ ] Error monitoring (Sentry)
- [ ] Logging setup
- [ ] Deployment scripts + CD pipeline
- [ ] **Google Sheets data migration**: Artisan command to import existing orders
- [ ] **Cutover plan**: Run both in parallel 1 week, then flip to backend
- [ ] Final security audit (rate limiting, webhook signatures, input validation)

---

## PART 6.5: SECURITY CHECKLIST

Before Phase 8 deployment, verify:

- [ ] **Rate limiting** configured per route group
  - Auth endpoints: 5 req/min per IP (lockout after)
  - Admin API: 120 req/min per token
  - Webhooks: unlimited (verified by signature instead)

- [ ] **Webhook signature verification** in place
  - HMAC-SHA256 validation for GHN/GHTK/SPX/Viettel
  - Redis idempotency check (reject duplicates within 5 min)
  - Returns 401 for invalid signature

- [ ] **Input validation** on all public/user-facing endpoints
  - Phone format, email format, VND amount ranges
  - SQL injection prevention (Eloquent parameterized queries used)
  - CSRF protection on state-changing endpoints

- [ ] **API versioning** (`/api/v1/`) enforced from start
  - No unversioned `/api/` routes
  - Allows future backward-compatible evolution

- [ ] **B2 storage security**
  - API keys never logged
  - Signed URLs expire after 24h
  - Private objects never cached

---

## PART 6.6: DEFER & SKIP DECISIONS

### Deferred (Build after launch)
| Feature | Phase | Reason |
|---------|-------|--------|
| SMS Notifications | Phase 5.5 | Email covers MVP; SMS adds cost & integration |
| Zalo OA Notifications | Phase 5.5 | High Vietnam value, needs OA account setup |
| Abandoned Checkout Tracking | Later | Requires public session tracking, adds complexity |
| Customer OTP Login | Later | No customer portal in v1 |
| Invoice PDF auto-rendering | Phase 4.5 | Schema ready at launch; rendering implementation after |
| SPX + Viettel Couriers | Month 1 post-launch | GHN + GHTK cover 90% of Vietnam volume; others later |
| Advanced Analytics Dashboard | Phase 6.5 | Basic KPIs at launch; full analytics/BI later |
| Waitlist email notifications | Phase 5 | Structure in v1, email job added later if needed |

### Skipped (Out of scope, do not add)
| Feature | Why skip |
|---------|----------|
| Multi-currency | 100% Vietnam market, VND only for foreseeable future |
| Gift cards | No demand signal, adds payment processing complexity |
| Bundle product type | Flash sales + sale_events already cover multi-item deals |
| Subscription/recurring orders | Not part of driip's drop business model |
| Point-of-sale (POS) system | No physical store |
| Marketplace / multi-vendor | Single brand, not a marketplace |
| Multi-language API responses | Internal admin panel, Vietnamese staff only |
| Customer loyalty gamification | Loyalty program + points sufficient for engagement |

---

## PART 7: COURIER INTEGRATIONS

### 7.1 GHN (Giao Hang Nhanh)
- [ ] Account setup (API key)
- [ ] API endpoints mapped:
  - [ ] Create order (shipment)
  - [ ] Cancel order
  - [ ] Get order detail
  - [ ] Get tracking status
- [ ] Webhook setup (shipment status updates)
- [ ] COD handling (track collected amount)
- [ ] Remittance schedule (check docs)

### 7.2 GHTK (Giaohangnhanh)
- [ ] Account setup (API key)
- [ ] API endpoints mapped
- [ ] Webhook setup
- [ ] COD handling
- [ ] Remittance schedule

### 7.3 SPX (Shopee Express)
- [ ] Account setup
- [ ] API endpoints
- [ ] Webhook setup
- [ ] COD handling

### 7.4 Viettel Post
- [ ] Account setup
- [ ] API endpoints
- [ ] Webhook setup
- [ ] COD handling

### 7.5 Courier Service Architecture
- [ ] `CourierServiceInterface` — shared contract
- [ ] `GHNService` implements interface
- [ ] `GHTKService` implements interface
- [ ] etc.
- [ ] `CourierFactory` to select courier by code
- [ ] Retry logic for failed API calls
- [ ] Rate limiting (if APIs have limits)

---

## PART 7.5: DOCKER SETUP CHECKLIST

### Docker Files Created
- [x] `Dockerfile` — Laravel PHP 8.3 FPM image
- [x] `docker-compose.yml` — orchestration (10 services)
- [x] `backend/.env.docker` — environment example
- [x] `backend/.dockerignore` — exclude unnecessary files
- [x] `docker/nginx/nginx.conf` — web server config
- [x] `docker/README.md` — comprehensive usage guide
- [x] `Makefile` — convenient command shortcuts

### Services Included
- [x] **Nginx** (port 80) — reverse proxy to PHP-FPM
- [x] **Laravel App** (PHP 8.3 FPM) — main application
- [x] **PostgreSQL 16** (port 5432) — database with persist volume
- [x] **Redis 7** (port 6379) — cache + queue driver
- [x] **Meilisearch** (port 7700) — full-text search
- [x] **MailHog** (ports 1025, 8025) — local email testing
- [x] **Minio** (ports 9000, 9001) — S3-compatible for B2 testing
- [x] **Queue Worker** — background jobs (Redis-based)
- [x] **Scheduler** — cron job runner
- [x] **Adminer** (port 8080) — database GUI (optional)

### Development Workflow
- [ ] `cp backend/.env.docker backend/.env` — setup env
- [ ] `docker-compose up -d` — start all services
- [ ] `docker exec driip-app composer install` — install dependencies
- [ ] `docker exec driip-app php artisan migrate` — create database schema
- [ ] `make bash` — enter app container shell for development
- [ ] `make logs` — watch container logs in real-time

### Docker Usage Documentation
- [x] README at `docker/README.md` covers all commands
- [x] Makefile shortcuts for common operations
- [x] Troubleshooting guide for common issues
- [x] Database backup/restore instructions
- [x] Redis CLI access for queue debugging

---

## PART 8: TESTING STRATEGY

- [ ] Unit tests (Actions, Services, ValueObjects)
- [ ] Integration tests (API endpoints)
- [ ] Database tests (with transactions/rollback)
- [ ] Feature tests (full order flow end-to-end)
- [ ] Test coverage target: 80%+
- [ ] Run tests in Docker: `make test`

---

## PART 9: DOMAIN OPTIMIZATION CHECKLIST

### Before Coding Starts, CONFIRM:
- [ ] All domain boundaries clear? (Customer domain shouldn't handle Orders directly)
- [ ] All Actions identified and scoped?
- [ ] All Services identified? (OrderStatusMachine, StockCalculation, B2Storage, etc.)
- [ ] All ValueObjects identified? (EmployeeCode, OrderNumber, CustomerCode, etc.)
- [ ] All Events identified? (OrderCreated, OrderShipped, StockReserved, etc.)
- [ ] All Exceptions identified? (OrderCannotBePacked, InsufficientStock, etc.)
- [ ] All DTOs identified? (CreateOrderDto, UpdateCustomerDto, etc.)
- [ ] All Repositories identified? (OrderRepository, InventoryRepository, etc.)
- [ ] Circular dependencies checked? (Domain A → Domain B ← Domain A is bad)
- [ ] Event listeners planned? (OrderCreated → trigger? Notify? Queue job?)

### Unanswered Questions (TO BE DISCUSSED):
- [ ] Question: Attribute storage for variants (JSON vs pivot table)?
- [ ] Question: Multi-supplier per variant - how to assign to warehouse?
- [ ] Question: Product images - variant-specific or product-level?
- [ ] Question: Phone uniqueness in customers?
- [ ] Question: Stock reservation timing (on order created or confirmed)?
- [ ] Question: Multi-warehouse fulfillment - how to auto-select warehouse?
- [ ] Question: Staff bonus calculation rules?
- [ ] Question: Loyalty points expiry - does it expire?
- [ ] Question: Customer return shipping - paid by us or customer?
- [ ] Question: Which couriers will actually be used in production?

---

## NOTES & DECISIONS LOG

**2026-03-21**:
- Decided: DDD architecture, B2 storage, Sanctum auth, PostgreSQL
- Decided: Variants as first-class products (not just attributes)
- Pending: Attribute storage method (JSON or pivot)
- Pending: Courier selection and remittance workflows
- Pending: Loyalty tier expiry and point expiry rules

---

## NEXT STEPS

1. **Review & finalize this plan** - check all boxes, answer all questions
2. **Optimize domain structure** - make sure boundaries are clean
3. **Finalize product variant design** - JSON vs pivot table decision
4. **Review API endpoint list** - make sure nothing is missing
5. **Once approved** → Start Phase 1 code implementation

---

**DO NOT START CODING UNTIL ALL CHECKBOXES IN PART 2 & 3 ARE CHECKED AND ALL UNANSWERED QUESTIONS ARE RESOLVED.**
