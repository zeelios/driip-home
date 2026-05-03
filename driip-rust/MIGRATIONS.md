# Database Migration Strategy for Production

## The Problem

When you deploy new code with schema changes, you have a race condition:

```
Old Code  ──►  New Migration  ──►  New Code
 (v1)          (schema v2)          (v2)
               ▲        ▲
               │        │
        Old code might  New code might
        fail on new     run before
        columns!        migration runs!
```

## The Solution: Zero-Downtime Migrations

Use the **Expand/Contract Pattern** — make changes in backward-compatible steps.

---

## Migration Categories

### ✅ Safe: Additive Changes (Backward Compatible)

These can run anytime, old code keeps working:

```sql
-- Add new table (old code doesn't use it)
CREATE TABLE IF NOT EXISTS coupons (...);

-- Add new column (with default or nullable)
ALTER TABLE products ADD COLUMN IF NOT EXISTS weight_grams INT;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_id UUID;  -- nullable

-- Add new index (doesn't affect reads)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_orders_status ON orders(status);

-- Add new enum value
ALTER TYPE order_status ADD VALUE IF NOT EXISTS 'on_hold';
```

### ⚠️  Risky: Destructive Changes (Breaking)

These require coordination between code and database:

| Change | Risk | Strategy |
|--------|------|----------|
| Rename column | Old code crashes | Add new, migrate data, drop old in later deploy |
| Change column type | Data loss risk | Add new column, migrate, switch code, drop old |
| Remove column | Old code crashes | Deprecation: stop using first, then remove |
| Add NOT NULL | Insert failures | Add as nullable, backfill, then add constraint |
| Drop table | Data loss | Rename to `_deprecated`, drop in later deploy |

---

## The Expand/Contract Pattern

### Example: Adding a Required Field

**Wrong (causes downtime):**
```sql
-- Migration 1
ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(50) NOT NULL;
-- ☠️ Old code INSERTs fail because tracking_number is missing
```

**Right (zero downtime):**

**Step 1 — Expand (Deploy 1):**
```sql
-- Migration: Add nullable column
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(50);
```
```rust
// Code: Handle both cases
let tracking = order.tracking_number.unwrap_or("pending".to_string());
```

**Step 2 — Backfill (Deploy 1 or background job):**
```sql
-- Backfill existing rows
UPDATE orders SET tracking_number = 'N/A' WHERE tracking_number IS NULL;
```

**Step 3 — Contract (Deploy 2 - days/weeks later):**
```sql
-- Migration: Add constraint after all data is valid
ALTER TABLE orders ALTER COLUMN tracking_number SET NOT NULL;
```

---

## Migration File Naming

Use timestamp prefix for ordering:

```
migrations/
├── 0001_identity.sql          -- First migration
├── 0002_core_tables.sql
├── 0003_fulfillment.sql
├── 0004_add_coupon_code.sql   -- New feature
└── 0005_add_order_weight.sql  -- Another feature
```

**After changing schema:**
```bash
# 1. Run migrations locally
make migrate

# 2. Generate offline cache (REQUIRED for CI/CD)
make prepare

# 3. Commit both:
git add migrations/000X_*.sql .sqlx/
git commit -m "feat: add order weight tracking"
```

---

## Practical Examples

### Example 1: Rename `price_cents` to `amount_cents`

**Migration 1 (Deploy with Code v1):**
```sql
-- Add new column
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS amount_cents INT;

-- Copy data (trigger or batch update)
UPDATE order_items SET amount_cents = price_cents WHERE amount_cents IS NULL;
```

**Code v1 (dual write):**
```rust
// Write to both columns
sqlx::query!(
    "INSERT INTO order_items (product_id, price_cents, amount_cents, ...)
     VALUES ($1, $2, $2, ...)",
    product_id, amount_cents
)
```

**Migration 2 (Deploy with Code v2 - days later):**
```sql
-- Now safe to drop old column
ALTER TABLE order_items DROP COLUMN IF EXISTS price_cents;
```

**Code v2 (read from new only):**
```rust
// Only use amount_cents
let amount: i32 = row.amount_cents;
```

---

### Example 2: Split `name` into `first_name` + `last_name`

**Migration 1:**
```sql
ALTER TABLE customers ADD COLUMN IF NOT EXISTS first_name VARCHAR(100);
ALTER TABLE customers ADD COLUMN IF NOT EXISTS last_name VARCHAR(100);

-- Parse and backfill
UPDATE customers 
SET first_name = split_part(name, ' ', 1),
    last_name = split_part(name, ' ', 2)
WHERE first_name IS NULL;
```

**Code v1 (reads old, writes both):**
```rust
// Read: prefer new columns, fallback to parsing old
let first = customer.first_name 
    .or_else(|| customer.name.as_ref().map(|n| n.split_whitespace().next().unwrap_or("").to_string()))
    .unwrap_or_default();
```

**Migration 2 (later):**
```sql
ALTER TABLE customers DROP COLUMN name;
```

---

## The Deploy Script's Role

Our `deploy.sh` handles migrations safely:

```bash
# 1. Backup first (can restore if things go wrong)
backup_database

# 2. Check what migrations are pending
sqlx migrate info

# 3. Run migrations BEFORE deploying new code
# This ensures new code sees updated schema
run_migrations

# 4. Only then deploy the new code
build && deploy_lambda
```

**Key principle:** Database leads, code follows.

---

## Rollback Strategy

### If Migration Fails:

```bash
# 1. Check what failed
sqlx migrate info

# 2. Revert last migration (if safe)
sqlx migrate revert --source ./migrations

# 3. Restore from backup (if needed)
psql $DATABASE_URL < /tmp/backup_20240115_143022.sql
```

### If Code Fails (but migration succeeded):

```bash
# 1. Rollback Lambda to previous version
aws lambda update-function-code \
  --function-name driip-api \
  --zip-file fileb://previous-version.zip

# 2. Database stays at new schema (backward compatible)
# Old code should still work
```

---

## Automated Checks

### Pre-commit Hook

Add to `.git/hooks/pre-commit` or `.pre-commit-config.yaml`:

```bash
#!/bin/bash
# Check migrations are idempotent
if git diff --cached --name-only | grep -q "migrations/.*\.sql$"; then
    echo "🔍 Checking migration safety..."
    
    # Check for dangerous patterns
    if git diff --cached --name-only | xargs grep -l "DROP TABLE\|DROP COLUMN"; then
        echo "⚠️  WARNING: Destructive migration detected"
        echo "Ensure you've used expand/contract pattern"
        exit 1
    fi
    
    # Ensure sqlx cache is updated
    if ! cargo sqlx prepare --check 2>/dev/null; then
        echo "❌ sqlx offline cache outdated. Run: make prepare"
        exit 1
    fi
fi
```

---

## Neon-Specific Considerations

### 1. Branch-Based Development

Neon allows branch-based migrations for testing:

```bash
# Create branch for feature
createdb --template=main driip_feature_xyz

# Run migrations on branch
DATABASE_URL="postgres://.../driip_feature_xyz" sqlx migrate run

# Test, then merge to main
```

### 2. Connection Limits

Neon has connection limits. Migrations hold connections:

```sql
-- Run heavy migrations with increased timeout
SET statement_timeout = '5min';

-- Or run during low-traffic hours
```

### 3. Compute Suspension

If Neon suspends compute during long migration:

```bash
# Keep alive during migration
while true; do
  psql $DATABASE_URL -c "SELECT 1"
  sleep 30
done &

# Run migration
sqlx migrate run

kill %1  # Stop keepalive
```

---

## Migration Checklist

Before committing a migration:

- [ ] Is it backward compatible with current code?
- [ ] Does it use `IF EXISTS` / `IF NOT EXISTS`?
- [ ] Is the sqlx offline cache updated (`make prepare`)?
- [ ] Is there a data backfill plan if needed?
- [ ] Is the rollback path clear?
- [ ] Did you test locally with `make fresh`?

---

## Common Patterns

### Adding a New Feature Table

```sql
-- Always safe, no existing code references it
CREATE TABLE IF NOT EXISTS loyalty_points (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id UUID NOT NULL REFERENCES customers(id),
    points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_loyalty_customer ON loyalty_points(customer_id);
```

### Adding Status to Existing Table

```sql
-- Step 1: Add with default (backward compatible)
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS fulfillment_status VARCHAR(20) 
DEFAULT 'pending';

-- Step 2 (later): Remove default after all code sets it explicitly
ALTER TABLE orders ALTER COLUMN fulfillment_status DROP DEFAULT;
```

---

## Summary

| Principle | Why |
|-----------|-----|
 **Database first** | Run migrations before deploying code |
 **Additive only** | Add columns/tables, don't rename/remove immediately |
 **Nullable first** | Required fields start as nullable, add constraint later |
 **Dual write** | When renaming, write to both old and new temporarily |
 **Test locally** | `make fresh` validates everything works |
 **Cache is code** | Commit `.sqlx/` — it's required for builds |

This approach lets you deploy multiple times per day with zero downtime and zero data loss.
