# driip-rust — Quick Reference

> Coming from Laravel/Artisan? This is your cheatsheet.
> All commands assume you're in `driip-rust/`.

---

## First-time setup

```bash
# 1. Install tools (once)
make install-tools          # sqlx-cli, cargo-watch, cargo-lambda

# 2. Copy env
cp .env.example .env        # edit DATABASE_URL, JWT_SECRET, STRIPE_*, GHTK_*

# 3. Start DB + Redis
make db

# 4. Run migrations
make migrate

# 5. Seed admin + demo data
make seed

# 6. Generate sqlx offline cache
make prepare

# 7. Run
make dev                    # hot-reload via cargo-watch
# OR
cargo run                   # now defaults to driip-rust binary (see default-run in Cargo.toml)
```

> **Tip:** `cargo run` now works without `--bin` because `Cargo.toml` has `default-run = "driip-rust"`. Use `cargo run --bin seed_admin` for other binaries.

---

## Daily workflow

| What                      | Command     | Laravel equivalent              |
| ------------------------- | ----------- | ------------------------------- |
| Start server (hot-reload) | `make dev`  | `php artisan serve`             |
| Start DB only             | `make db`   | `docker compose up db`          |
| Stop everything           | `make stop` | —                               |
| View logs                 | `make logs` | `tail storage/logs/laravel.log` |

---

## Database

| What                         | Command                   | Laravel equivalent                 |
| ---------------------------- | ------------------------- | ---------------------------------- |
| Run pending migrations       | `make migrate`            | `php artisan migrate`              |
| Drop + re-run all migrations | `make migrate-fresh`      | `php artisan migrate:fresh`        |
| Drop + re-run + seed         | `make fresh`              | `php artisan migrate:fresh --seed` |
| Full Docker volume reset     | `make reset-db`           | — (nuclear option)                 |
| Create migration file        | `sqlx migrate add <name>` | `php artisan make:migration`       |
| Check migration status       | `sqlx migrate info`       | `php artisan migrate:status`       |
| Revert last migration        | `sqlx migrate revert`     | `php artisan migrate:rollback`     |

> **`make fresh` is the go-to command when you change the schema.** It drops the DB, re-runs all migrations, regenerates the sqlx cache, and re-seeds — ready to code in ~30 seconds.

Migrations live in `migrations/` as plain `.sql` files — numbered `0001_…`, `0002_…`, etc.

**After changing schema, always regenerate the offline query cache:**

```bash
make prepare                # = DATABASE_URL=... cargo sqlx prepare
```

Without this, `cargo build` fails with "relation does not exist" even if the DB is fine.

### Migration best practices

Make migrations **idempotent** to prevent "relation already exists" errors:

```sql
-- Good (safe to re-run)
CREATE TABLE IF NOT EXISTS users (...);
CREATE INDEX IF NOT EXISTS idx_users ON users(id);
DROP TRIGGER IF EXISTS my_trigger ON users;
CREATE TRIGGER my_trigger ...;

-- Bad (fails on second run)
CREATE TABLE users (...);  -- ERROR: relation "users" already exists
```

---

## Seeders

Seeders are standalone Rust binaries in `src/bin/`. Each runs independently.

| What            | Command                          | Laravel equivalent                        |
| --------------- | -------------------------------- | ----------------------------------------- |
| Seed admin user | `make seed-admin`                | `php artisan db:seed --class=AdminSeeder` |
| Seed demo data  | `make seed-demo`                 | `php artisan db:seed --class=DemoSeeder`  |
| Seed everything | `make seed`                      | `php artisan db:seed`                     |
| Force re-seed   | `SEED_FORCE=true make seed-demo` | `php artisan db:seed --force`             |

**Override seed values with env vars:**

```bash
SEED_ADMIN_EMAIL=you@example.com \
SEED_ADMIN_PASSWORD=secret123 \
make seed-admin
```

**Add a new seeder:**

1. Create `src/bin/seed_<name>.rs`
2. Add `seed-<name>` target to `Makefile`
3. Implement `#[tokio::main] async fn main()` with sqlx queries

---

## Build & deploy

| What             | Command             | Notes                   |
| ---------------- | ------------------- | ----------------------- |
| Debug build      | `cargo build`       | Fast, no optimisation   |
| Release build    | `make build`        | Optimised + stripped    |
| Lambda build     | `make build-lambda` | Requires `cargo-lambda` |
| Check (no build) | `cargo check`       | Fastest type check      |
| Lint             | `cargo clippy`      | Like `phpstan` / ESLint |
| Format           | `cargo fmt`         | Like `php-cs-fixer`     |

---

## Testing

### Unit tests

```bash
make test                   # cargo test (with DB)
SQLX_OFFLINE=true cargo test  # test without DB
```

### API Integration Tests

Full end-to-end tests: login → JWT → permissioned endpoints.

```bash
cd tests
./run-tests.sh              # run all tests with health check
# OR
bun test                    # run directly

# Override API endpoint
API_URL=http://localhost:8000 bun test
```

**Covers:** Auth (login, refresh, logout), Dashboard stats, Products CRUD, Orders, Purchase Orders, Staff, Inventory.

See `tests/README.md` for details.

---

## Adding things

### New migration

```bash
sqlx migrate add create_coupons_table
# → creates migrations/000N_create_coupons_table.sql
# Edit the .sql file, then:
make migrate && make prepare
```

### New domain module

1. Create `src/domain/<name>/mod.rs` with route builder
2. Add `pub mod <name>;` in `src/domain/mod.rs`
3. Register routes in `domain::router()` or appropriate sub-router

### New seeder

```bash
touch src/bin/seed_<name>.rs
# Implement it, then add to Makefile:
#   seed-<name>:
#       DATABASE_URL=$(DB_URL) cargo run --bin seed_<name>
```

---

## SQLx cheatsheet

SQLx is the database layer — it checks SQL at **compile time** against a live DB (or cached `.sqlx/`).

```rust
// Simple query (no return)
sqlx::query!("UPDATE orders SET status=$1 WHERE id=$2", status, id)
    .execute(&state.db)
    .await?;

// Return a typed row
let row = sqlx::query!("SELECT name, email FROM customers WHERE id=$1", id)
    .fetch_optional(&state.db)   // .fetch_one() / .fetch_all()
    .await?;
// row.name, row.email are typed String

// Return a struct (must derive sqlx::FromRow)
let order = sqlx::query_as!(Order, "SELECT * FROM orders WHERE id=$1", id)
    .fetch_one(&state.db)
    .await?;

// Scalar (single value)
let count: i64 = sqlx::query_scalar!("SELECT COUNT(*) FROM orders")
    .fetch_one(&state.db)
    .await?
    .unwrap_or(0);
```

**Compile without a running DB:**

```bash
SQLX_OFFLINE=true cargo build
```

This uses `.sqlx/` cache. Run `make prepare` first to populate it.

---

## Auth flow

Two separate JWT token families — staff and customer. They use different claims structs and verify separately.

| Token           | Extractor      | Where it goes                         |
| --------------- | -------------- | ------------------------------------- |
| Staff access    | `AuthContext`  | Panel API (`/api/v1/...`)             |
| Customer access | `CustomerAuth` | Storefront API (`/api/v1/public/...`) |

```rust
// Extract staff auth + check permission
pub async fn my_handler(
    State(state): State<AppState>,
    auth: AuthContext,              // extracts + validates staff JWT
) -> Result<Json<Thing>, AppError> {
    auth.require(Permission::OrderRead)?;
    // ...
}

// Extract customer auth
pub async fn customer_handler(
    State(state): State<AppState>,
    claims: CustomerAuth,           // extracts + validates customer JWT
    Json(body): Json<MyBody>,       // ← body extractor MUST be last
) -> Result<Json<Thing>, AppError> {
    let cid = claims.customer_id;   // Uuid
    // ...
}
```

**Extractor order rule (Axum):** anything that reads the request body (`Json`, `Form`, `Bytes`) must be the **last** parameter.

---

## Error handling

Return `AppError` from handlers — it auto-converts to the right HTTP status + JSON body.

```rust
// 404
return Err(AppError::NotFound("Order not found".into()));

// 422
return Err(AppError::Validation("amount must be > 0".into()));

// 409
return Err(AppError::Conflict("Email already taken".into()));

// 401 / 403
return Err(AppError::Unauthorized("Invalid token".into()));
return Err(AppError::Forbidden);

// 500 (logs internally)
return Err(AppError::Internal("unexpected state".into()));

// Propagate DB errors with ?
let row = sqlx::query!(...).fetch_one(&state.db).await?;
```

---

## Environment variables

| Variable                 | Required | Example                                 |
| ------------------------ | -------- | --------------------------------------- |
| `DATABASE_URL`           | ✅       | `postgres://driip:pass@localhost/driip` |
| `JWT_SECRET`             | ✅       | any long random string                  |
| `JWT_ACCESS_TTL_SECS`    | —        | `900` (15 min)                          |
| `JWT_REFRESH_TTL_SECS`   | —        | `604800` (7 days)                       |
| `STRIPE_SECRET_KEY`      | —        | `sk_test_…`                             |
| `STRIPE_WEBHOOK_SECRET`  | —        | `whsec_…`                               |
| `STRIPE_PUBLISHABLE_KEY` | —        | `pk_test_…`                             |
| `GHTK_TOKEN`             | —        | courier API key                         |
| `GHTK_SANDBOX`           | —        | `true` / `false`                        |
| `GHTK_WEBHOOK_SECRET`    | —        | HMAC secret                             |
| `GHTK_PICK_*`            | —        | pickup address fields                   |
| `PORT`                   | —        | `8000` (default)                        |
| `SQLX_OFFLINE`           | —        | `true` to skip DB at build time         |

---

## Stripe notes

The Stripe integration is disabled when `STRIPE_SECRET_KEY` is not set — all Stripe endpoints return `500 Stripe not configured`. The app boots fine without it.

After adding `STRIPE_SECRET_KEY`, run migrations to create the payment tables if you haven't already:

```bash
make migrate && make prepare
```

Webhook verification uses HMAC-SHA256 with a 5-minute timestamp tolerance. Register your webhook in the Stripe Dashboard and set `STRIPE_WEBHOOK_SECRET` to the `whsec_…` signing secret.

---

## Panel-v2 Integration

The `panel-v2/` Nuxt 3 frontend connects to this backend. Key integration points:

| Feature         | Backend Support                           | Endpoint                                                          |
| --------------- | ----------------------------------------- | ----------------------------------------------------------------- |
| SSR Auth        | Cookies (`panel_access`, `panel_refresh`) | `/auth/login`, `/auth/refresh`                                    |
| Dashboard Stats | `GET /orders/stats`                       | Returns `orders_today`, `pending`, `total`, `revenue_today_cents` |
| Products        | Full CRUD                                 | `/products`, `/products/:id`                                      |
| Purchase Orders | Detail view with items                    | `/purchase-orders/:id`                                            |

**CORS:** Configured for `localhost:3002` (panel-v2 dev server).

**Auth flow:**

1. Panel calls `/auth/login` → receives `{access_token, refresh_token}`
2. Tokens stored in cookies (SSR-safe, not localStorage)
3. API calls include `Authorization: Bearer <token>`
4. Token refresh via `/auth/refresh` using `refresh_token` cookie

See `panel-v2/app/composables/useApi.ts` for the client implementation.

---

## Project layout

```
src/
├── main.rs             # entry point, router assembly, AppState init
├── state.rs            # AppState struct
├── auth.rs             # JWT encode/decode, AuthContext, CustomerAuth extractors
├── config.rs           # Config::from_env()
├── errors.rs           # AppError → HTTP response
├── db.rs               # PgPool setup
├── health.rs           # GET /health
├── domain/             # one module per business domain
│   ├── mod.rs          # router assembly
│   ├── identity/       # staff auth + management
│   ├── payment/        # Stripe payments, subscriptions, refunds
│   ├── order/          # orders CRUD + actions + stats (/orders/stats)
│   ├── fulfillment/    # GHTK shipments
│   ├── customer/       # customer CRUD
│   ├── product/        # product CRUD
│   ├── inventory/      # stock management
│   ├── warehouse/      # warehouse CRUD
│   ├── address/        # address book + fraud
│   ├── public/         # customer-facing storefront routes
│   └── ...
├── integrations/
│   ├── ghtk/           # GHTK courier HTTP client
│   └── stripe/         # Stripe HTTP client + webhook verifier
├── middleware/         # rate limiting, security headers
└── bin/                # standalone executables (seeders, scripts)
    ├── seed_admin.rs   # cargo run --bin seed_admin
    ├── seed_demo.rs    # cargo run --bin seed_demo
    └── run_migration.rs
migrations/             # plain SQL files, numbered 0001_…
```
