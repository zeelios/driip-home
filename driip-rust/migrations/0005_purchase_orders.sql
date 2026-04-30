-- Migration: 0005_purchase_orders
-- Purchase Order (PO) domain for import / restocking workflow

-- ── Purchase Orders ───────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS purchase_orders (
    id            UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    supplier_name TEXT        NOT NULL,
    status        TEXT        NOT NULL DEFAULT 'draft'
                  CHECK (status IN ('draft', 'ordered', 'partial', 'received', 'cancelled')),
    expected_date DATE,
    notes         TEXT,
    created_by    UUID        REFERENCES staff(id) ON DELETE SET NULL,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_po_status     ON purchase_orders (status);
CREATE INDEX idx_po_created_by ON purchase_orders (created_by);

-- ── Purchase Order Items ──────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id                  UUID    PRIMARY KEY DEFAULT gen_random_uuid(),
    purchase_order_id   UUID    NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
    product_id          UUID    NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    warehouse_id        UUID    NOT NULL REFERENCES warehouses(id) ON DELETE RESTRICT,
    ordered_qty         INT     NOT NULL CHECK (ordered_qty > 0),
    received_qty        INT     NOT NULL DEFAULT 0 CHECK (received_qty >= 0),
    unit_cost_cents     BIGINT  NOT NULL DEFAULT 0 CHECK (unit_cost_cents >= 0)
);

CREATE INDEX idx_po_items_po_id      ON purchase_order_items (purchase_order_id);
CREATE INDEX idx_po_items_product_id ON purchase_order_items (product_id);
