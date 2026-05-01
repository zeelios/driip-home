-- Migration: 0006_customer_auth
-- Customer authentication: password_hash + refresh token rotation

ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS password_hash TEXT;

-- Ensure email is NOT NULL for auth (existing rows may have NULL email)
-- Backfill: set email from phone or a placeholder if both are null
UPDATE customers
SET email = CONCAT('customer-', id, '@placeholder.driip.vn')
WHERE email IS NULL;

ALTER TABLE customers
    ALTER COLUMN email SET NOT NULL;

-- password_hash intentionally remains nullable so existing customer records can be migrated
-- safely. Public registration and staff-created customers always set it. Customers with NULL
-- password_hash cannot log in until activated/password is set.

CREATE INDEX idx_customers_auth ON customers (email) WHERE password_hash IS NOT NULL;

-- ── Customer refresh tokens ──────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS customer_refresh_tokens (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id  UUID NOT NULL REFERENCES customers (id) ON DELETE CASCADE,
    token_hash   TEXT NOT NULL UNIQUE,
    expires_at   TIMESTAMPTZ NOT NULL,
    revoked_at   TIMESTAMPTZ,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_customer_refresh_customer_id   ON customer_refresh_tokens (customer_id);
CREATE INDEX idx_customer_refresh_token_hash    ON customer_refresh_tokens (token_hash);
CREATE INDEX idx_customer_refresh_active        ON customer_refresh_tokens (customer_id)
    WHERE revoked_at IS NULL;
