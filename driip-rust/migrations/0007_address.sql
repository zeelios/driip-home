-- Migration: 0007_address
-- Extract Address as a standalone domain with fraud-prevention blocking.
-- Replaces inline address text on customers and warehouses.

-- ── Addresses ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS addresses (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    recipient_name  TEXT NOT NULL,
    street          TEXT NOT NULL,
    ward            TEXT,
    district        TEXT,
    city            TEXT NOT NULL,
    province        TEXT,
    postal_code     TEXT,
    country         TEXT NOT NULL DEFAULT 'VN',
    phone           TEXT,
    metadata        JSONB,
    status          TEXT NOT NULL DEFAULT 'clean'
                        CHECK (status IN ('clean', 'flagged', 'blocked')),
    strike_count    INT  NOT NULL DEFAULT 0,
    block_reason    TEXT,
    blocked_by      UUID REFERENCES staff (id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_addresses_status ON addresses (status);
CREATE INDEX idx_addresses_strikes ON addresses (strike_count) WHERE status != 'clean';

-- ── Customer Addresses (1:N junction) ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS customer_addresses (
    customer_id     UUID NOT NULL REFERENCES customers (id) ON DELETE CASCADE,
    address_id      UUID NOT NULL REFERENCES addresses (id) ON DELETE CASCADE,
    is_default      BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (customer_id, address_id)
);

CREATE INDEX idx_customer_addresses_customer ON customer_addresses (customer_id);
CREATE INDEX idx_customer_addresses_default  ON customer_addresses (customer_id, is_default) WHERE is_default = TRUE;

-- ── Link warehouses to addresses (1:1) ───────────────────────────────────────

ALTER TABLE warehouses
    ADD COLUMN IF NOT EXISTS address_id UUID REFERENCES addresses (id);

-- Migrate warehouse addresses into standalone address rows
INSERT INTO addresses (recipient_name, street, city, province, country, created_at, updated_at)
SELECT
    w.name AS recipient_name,
    COALESCE(w.address, '') AS street,
    COALESCE(w.city, 'TP. Hồ Chí Minh') AS city,
    NULL AS province,
    'VN' AS country,
    NOW() AS created_at,
    NOW() AS updated_at
FROM warehouses w
WHERE w.address_id IS NULL;

-- Link migrated addresses back to warehouses
UPDATE warehouses w
SET address_id = a.id
FROM addresses a
WHERE w.address_id IS NULL
  AND a.recipient_name = w.name
  AND a.street = COALESCE(w.address, '');

-- Ensure every warehouse now has an address_id
ALTER TABLE warehouses
    ALTER COLUMN address_id SET NOT NULL;

-- Drop old inline warehouse address columns
ALTER TABLE warehouses
    DROP COLUMN IF EXISTS address,
    DROP COLUMN IF EXISTS city;

-- ── Link orders to a shipping address ────────────────────────────────────────

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS shipping_address_id UUID REFERENCES addresses (id);

-- ── Migrate customer addresses ───────────────────────────────────────────────

-- Create one address per customer from existing text fields
INSERT INTO addresses (recipient_name, street, city, province, country, phone, created_at, updated_at)
SELECT
    c.name AS recipient_name,
    COALESCE(c.address, '') AS street,
    COALESCE(c.province, 'TP. Hồ Chí Minh') AS city,
    c.province,
    'VN' AS country,
    c.phone,
    NOW() AS created_at,
    NOW() AS updated_at
FROM customers c
WHERE c.address IS NOT NULL OR c.province IS NOT NULL;

-- Link them as default customer addresses
INSERT INTO customer_addresses (customer_id, address_id, is_default, created_at)
SELECT
    c.id AS customer_id,
    a.id AS address_id,
    TRUE AS is_default,
    NOW() AS created_at
FROM customers c
JOIN addresses a ON a.recipient_name = c.name AND a.street = COALESCE(c.address, '')
WHERE c.address IS NOT NULL OR c.province IS NOT NULL;

-- Drop old inline customer address columns
ALTER TABLE customers
    DROP COLUMN IF EXISTS address,
    DROP COLUMN IF EXISTS province;
