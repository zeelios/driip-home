-- Migration: 0002_fulfillment
-- Fulfillment domain: shipments, status ledger, fee catalog, order fee lines
-- Also upgrades orders table with shipping/operational fee columns

-- ── Orders table upgrade ──────────────────────────────────────────────────────

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS shipping_fee_cents      BIGINT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS operational_fee_cents   BIGINT NOT NULL DEFAULT 0;

-- grand_total_cents = product total + shipping + operational fees
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS grand_total_cents BIGINT
        GENERATED ALWAYS AS (total_cents + shipping_fee_cents + operational_fee_cents) STORED;

-- ── Fee catalog ───────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS fee_catalog (
    id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name                 TEXT NOT NULL,
    description          TEXT,
    default_amount_cents BIGINT NOT NULL DEFAULT 0,
    is_active            BOOLEAN NOT NULL DEFAULT TRUE,
    created_by           UUID REFERENCES staff (id) ON DELETE SET NULL,
    created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_fee_catalog_active ON fee_catalog (is_active);

-- ── Order fee lines ───────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS order_fee_lines (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id        UUID NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    fee_catalog_id  UUID REFERENCES fee_catalog (id) ON DELETE SET NULL,
    label           TEXT NOT NULL,
    amount_cents    BIGINT NOT NULL,
    created_by      UUID REFERENCES staff (id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_order_fee_lines_order_id ON order_fee_lines (order_id);

-- ── Shipments ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS shipments (
    id                          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id                    UUID NOT NULL REFERENCES orders (id) ON DELETE RESTRICT,
    -- GHTK identifiers
    ghtk_order_id               TEXT UNIQUE,          -- label_id returned by GHTK
    ghtk_tracking_id            TEXT,                 -- tracking number shown to customer
    -- Status
    status                      TEXT NOT NULL DEFAULT 'pending'
                                    CHECK (status IN (
                                        'pending','booked','picking','delivering',
                                        'delivered','returned','cancelled','lost'
                                    )),
    -- Financial reconciliation
    customer_paid_shipping_cents BIGINT NOT NULL DEFAULT 0,
    ghtk_charged_cents           BIGINT NOT NULL DEFAULT 0,
    shipping_diff_cents          BIGINT GENERATED ALWAYS AS
                                    (ghtk_charged_cents - customer_paid_shipping_cents) STORED,
    -- Physical attributes
    weight_grams                 INT NOT NULL DEFAULT 500,
    pick_date                    DATE,
    -- Audit
    raw_ghtk_response            JSONB,
    booked_by                    UUID REFERENCES staff (id) ON DELETE SET NULL,
    cancelled_by                 UUID REFERENCES staff (id) ON DELETE SET NULL,
    cancel_reason                TEXT,
    created_at                   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at                   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_shipments_order_id       ON shipments (order_id);
CREATE INDEX idx_shipments_ghtk_order_id  ON shipments (ghtk_order_id);
CREATE INDEX idx_shipments_status         ON shipments (status);

-- ── Shipment status event ledger (immutable) ──────────────────────────────────

CREATE TABLE IF NOT EXISTS shipment_status_events (
    id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shipment_id    UUID NOT NULL REFERENCES shipments (id) ON DELETE CASCADE,
    ghtk_status_id INT,              -- numeric status code from GHTK webhook
    status_text    TEXT NOT NULL,    -- human-readable status
    reason         TEXT,             -- failure/return reason if any
    partner_id     TEXT,             -- GHTK partner_id from webhook
    occurred_at    TIMESTAMPTZ NOT NULL,
    received_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_shipment_events_shipment_id ON shipment_status_events (shipment_id);
CREATE INDEX idx_shipment_events_occurred_at ON shipment_status_events (occurred_at DESC);
